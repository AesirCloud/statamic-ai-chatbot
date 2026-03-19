<?php

namespace AesirCloud\StatamicAiChatbot\Support\Chat;

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Models\KnowledgeChunk;
use AesirCloud\StatamicAiChatbot\Support\Config\ProviderManager;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        $provider = $this->providerManager->forText($profile);

        $context = $chunks
            ->map(function (KnowledgeChunk $chunk, int $index) {
                return implode("\n", [
                    'Source #'.($index + 1),
                    'Title: '.data_get($chunk->metadata, 'title', 'Untitled'),
                    'URL: '.(data_get($chunk->metadata, 'url') ?: 'N/A'),
                    'Snippet: '.$chunk->content_plain,
                ]);
            })
            ->implode("\n\n");

        $response = agent(
            instructions: $this->instructions($profile),
            schema: fn (JsonSchema $schema) => [
                'message' => $schema->string()->required(),
                'intent' => $schema->string()->required(),
                'confidence' => $schema->integer()->min(0)->max(100)->required(),
                'citations_json' => $schema->string()->required(),
                'next_actions_json' => $schema->string()->required(),
                'lead_capture_fields_json' => $schema->string()->required(),
            ],
        )->prompt(
            $this->prompt($message, $context),
            provider: $provider['driver'],
            model: $provider['model'],
        );

        return [
            'message' => (string) ($response['message'] ?? ''),
            'intent' => (string) ($response['intent'] ?? 'support'),
            'confidence' => (int) ($response['confidence'] ?? 50),
            'citations' => $this->decodeJsonArray($response['citations_json'] ?? '[]'),
            'next_actions' => $this->decodeJsonArray($response['next_actions_json'] ?? '[]'),
            'lead_capture_fields' => $this->decodeJsonArray($response['lead_capture_fields_json'] ?? '[]'),
        ];
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
     * @return array<int, mixed>
     */
    protected function decodeJsonArray(string $value): array
    {
        $decoded = json_decode($value, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }
}
