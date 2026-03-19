<?php

namespace AesirCloud\StatamicAiChatbot\Support\Chat;

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Models\KnowledgeChunk;
use AesirCloud\StatamicAiChatbot\Support\Config\ProviderManager;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Exceptions\FailoverableException;
use Throwable;

use function Laravel\Ai\agent;

class SupportAssistant
{
    public function __construct(protected ProviderManager $providerManager)
    {
    }

    /**
     * @param  Collection<int, KnowledgeChunk>  $chunks
     * @return array<string, mixed>
     */
    public function respond(BotProfile $profile, string $message, Collection $chunks): array
    {
        $context = $this->context($chunks);
        $prompt = $this->prompt($message, $context);
        $candidates = $this->providerManager->forTextCandidates($profile);
        $totalCandidates = count($candidates);
        $lastException = null;

        foreach ($candidates as $index => $candidate) {
            $attempt = $index + 1;

            Log::info('Statamic AI chatbot provider attempt starting.', [
                'profile_id' => $profile->id,
                'driver' => $candidate['driver'],
                'model' => $candidate['model'],
                'attempt' => $attempt,
                'attempts_total' => $totalCandidates,
            ]);

            try {
                $response = $this->generateResponse($profile, $prompt, $candidate);

                Log::info('Statamic AI chatbot provider attempt succeeded.', [
                    'profile_id' => $profile->id,
                    'driver' => $candidate['driver'],
                    'model' => $candidate['model'],
                    'attempt' => $attempt,
                    'attempts_total' => $totalCandidates,
                ]);

                return $this->normalizeResponse($response);
            } catch (Throwable $exception) {
                $retryable = $this->isRetryableException($exception);
                $lastException = $exception;

                Log::log(
                    $retryable ? 'warning' : 'error',
                    'Statamic AI chatbot provider attempt failed.',
                    array_merge($this->exceptionContext($exception), [
                        'profile_id' => $profile->id,
                        'driver' => $candidate['driver'],
                        'model' => $candidate['model'],
                        'attempt' => $attempt,
                        'attempts_total' => $totalCandidates,
                        'retryable' => $retryable,
                    ])
                );

                if (! $retryable) {
                    return $this->degradedResponse('ai_provider_misconfigured');
                }
            }
        }

        if ($lastException) {
            Log::warning('Statamic AI chatbot exhausted all provider candidates.', array_merge(
                $this->exceptionContext($lastException),
                [
                    'profile_id' => $profile->id,
                    'attempts_total' => $totalCandidates,
                ],
            ));
        }

        return $this->degradedResponse('ai_provider_unavailable');
    }

    /**
     * @param  array{driver:string,model:?string}  $candidate
     */
    protected function generateResponse(BotProfile $profile, string $prompt, array $candidate): mixed
    {
        return agent(
            instructions: $this->instructions($profile),
            schema: fn (JsonSchema $schema) => $this->responseSchema($schema),
        )->prompt(
            $prompt,
            provider: $candidate['driver'],
            model: $candidate['model'],
        );
    }

    protected function instructions(BotProfile $profile): string
    {
        $branding = $profile->branding ?? [];
        $brandVoice = Arr::get($branding, 'voice');

        return Str::of(implode("\n", array_filter([
            'You are the site support assistant for a Statamic-powered website.',
            'Only answer using the supplied FAQ or knowledge context.',
            'If the answer is not clearly supported by the context, be honest and recommend support or lead capture.',
            'Keep the tone aligned with the site brand.',
            'Return machine-friendly JSON strings for citations_json, next_actions_json, and lead_capture_fields_json.',
            'Valid intents include: faq, support, sales, lead_capture, human_handoff.',
            filled($profile->system_prompt) ? $profile->system_prompt : null,
            filled($brandVoice) ? 'Brand voice: '.$brandVoice : null,
        ])))->trim()->value();
    }

    protected function prompt(string $message, string $context): string
    {
        return implode("\n\n", [
            'User message:',
            $message,
            'Knowledge context:',
            filled($context) ? $context : 'No supporting knowledge was found.',
            'Respond with concise markdown in message, and JSON arrays encoded as strings for citations_json, next_actions_json, and lead_capture_fields_json.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeResponse(mixed $response): array
    {
        return [
            'message' => (string) ($response['message'] ?? ''),
            'intent' => (string) ($response['intent'] ?? 'support'),
            'confidence' => (int) ($response['confidence'] ?? 50),
            'citations' => $this->decodeJsonArray($response['citations_json'] ?? '[]'),
            'next_actions' => $this->decodeJsonArray($response['next_actions_json'] ?? '[]'),
            'lead_capture_fields' => $this->decodeJsonArray($response['lead_capture_fields_json'] ?? '[]'),
            'status' => 'ok',
            'error_code' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function degradedResponse(string $errorCode): array
    {
        return [
            'message' => match ($errorCode) {
                'ai_provider_misconfigured' => 'I am having trouble with the AI setup right now. You can still contact support or leave your details for a follow-up.',
                default => 'I am having trouble reaching the AI assistant right now. You can still contact support or leave your details for a follow-up.',
            },
            'intent' => 'support',
            'confidence' => 24,
            'citations' => [],
            'next_actions' => [],
            'lead_capture_fields' => [],
            'status' => 'degraded',
            'error_code' => $errorCode,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function exceptionContext(Throwable $exception): array
    {
        $previous = $exception->getPrevious();

        return [
            'exception_class' => $exception::class,
            'exception_message' => $exception->getMessage(),
            'previous_exception_class' => $previous ? $previous::class : null,
            'previous_exception_message' => $previous?->getMessage(),
        ];
    }

    protected function isRetryableException(Throwable $exception): bool
    {
        if ($exception instanceof FailoverableException || $exception instanceof ConnectionException) {
            return true;
        }

        if ($exception instanceof RequestException) {
            $status = $exception->response?->status();

            return $status === 408 || $status === 429 || ($status >= 500 && $status < 600);
        }

        $message = Str::lower($exception->getMessage().' '.$exception->getPrevious()?->getMessage());

        return Str::contains($message, [
            'timeout',
            'timed out',
            'temporarily unavailable',
            'connection refused',
            'could not resolve host',
            'service unavailable',
            'gateway timeout',
        ]);
    }

    /**
     * @param  Collection<int, KnowledgeChunk>  $chunks
     */
    protected function context(Collection $chunks): string
    {
        return $chunks
            ->map(function (KnowledgeChunk $chunk, int $index) {
                return implode("\n", [
                    'Source #'.($index + 1),
                    'Title: '.data_get($chunk->metadata, 'title', 'Untitled'),
                    'URL: '.(data_get($chunk->metadata, 'url') ?: 'N/A'),
                    'Snippet: '.$chunk->content_plain,
                ]);
            })
            ->implode("\n\n");
    }

    /**
     * @return array<string, mixed>
     */
    protected function responseSchema(JsonSchema $schema): array
    {
        return [
            'message' => $schema->string()->required(),
            'intent' => $schema->string()->required(),
            'confidence' => $schema->integer()->min(0)->max(100)->required(),
            'citations_json' => $schema->string()->required(),
            'next_actions_json' => $schema->string()->required(),
            'lead_capture_fields_json' => $schema->string()->required(),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected function decodeJsonArray(string $value): array
    {
        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }
}
