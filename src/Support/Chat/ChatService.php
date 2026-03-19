<?php

namespace AesirCloud\StatamicAiChatbot\Support\Chat;

use AesirCloud\StatamicAiChatbot\Contracts\SupportHandoffResolver;
use AesirCloud\StatamicAiChatbot\Models\ChatConversation;
use AesirCloud\StatamicAiChatbot\Models\ChatMessage;
use AesirCloud\StatamicAiChatbot\Support\Knowledge\FaqMatcher;
use AesirCloud\StatamicAiChatbot\Support\Knowledge\KnowledgeRetriever;
use AesirCloud\StatamicAiChatbot\Support\Profiles\BotProfileResolver;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Statamic\Facades\Site;

class ChatService
{
    public function __construct(
        protected BotProfileResolver $profileResolver,
        protected FaqMatcher $faqMatcher,
        protected KnowledgeRetriever $knowledgeRetriever,
        protected SupportAssistant $assistant,
        protected SupportHandoffResolver $handoffResolver,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function handle(array $payload): array
    {
        $profile = $this->profileResolver->resolve(
            handle: Arr::get($payload, 'profile'),
            site: Arr::get($payload, 'site'),
            locale: Arr::get($payload, 'locale')
        );

        $conversation = $this->conversation($profile->id, $payload);
        $question = (string) Arr::get($payload, 'message', '');
        $site = $this->resolveSiteHandle(Arr::get($payload, 'site') ?: $profile->site);
        $locale = $this->resolveLocaleHandle(Arr::get($payload, 'locale') ?: $profile->locale, $site);

        $this->storeMessage($conversation, 'user', $question);

        $faq = $this->faqMatcher->match($profile, $question, $site, $locale);

        if ($faq) {
            $response = [
                'message' => $faq->answer,
                'intent' => 'faq',
                'confidence' => 96,
                'status' => 'ok',
                'error_code' => null,
                'citations' => [[
                    'title' => $faq->question,
                    'type' => 'faq',
                ]],
                'next_actions' => $faq->cta_actions ?? [],
                'lead_capture_fields' => $faq->lead_capture_fields ?? [],
            ];
        } else {
            $chunks = $this->knowledgeRetriever->search($profile, $question, $site, $locale);
            $response = $this->assistant->respond($profile, $question, $chunks);
            $response['citations'] = $response['citations'] ?: $chunks->map(function ($chunk) {
                return [
                    'title' => data_get($chunk->metadata, 'title', 'Knowledge base'),
                    'url' => data_get($chunk->metadata, 'url'),
                    'score' => round((float) $chunk->score, 4),
                    'driver' => data_get($chunk->metadata, 'driver'),
                ];
            })->values()->all();
        }

        $response['next_actions'] = $response['next_actions'] ?: $this->handoffResolver->resolve(
            $profile,
            $response['intent'],
            (int) $response['confidence'],
            ['site' => $site, 'locale' => $locale]
        );
        $response['next_actions'] = $this->normalizeActions($response['next_actions']);

        $this->storeMessage($conversation, 'assistant', (string) $response['message'], $response);

        return [
            'conversation_id' => $conversation->id,
            'message' => $response['message'],
            'intent' => $response['intent'],
            'confidence' => $response['confidence'],
            'status' => $response['status'] ?? 'ok',
            'error_code' => $response['error_code'] ?? null,
            'citations' => $response['citations'],
            'next_actions' => $response['next_actions'],
            'lead_capture_fields' => $response['lead_capture_fields'],
            'widget' => array_merge(
                config('statamic-ai-chatbot.widget'),
                array_filter($profile->widget_settings ?? [], fn ($value) => $value !== null),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function conversation(int $profileId, array $payload): ChatConversation
    {
        return ChatConversation::firstOrCreate(
            [
                'bot_profile_id' => $profileId,
                'session_id' => (string) Arr::get($payload, 'session_id', (string) Str::uuid()),
            ],
            [
                'site' => Arr::get($payload, 'site'),
                'locale' => Arr::get($payload, 'locale'),
                'visitor_name' => Arr::get($payload, 'visitor.name'),
                'visitor_email' => Arr::get($payload, 'visitor.email'),
                'metadata' => Arr::only($payload, ['path', 'user_agent']),
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $structured
     */
    protected function storeMessage(ChatConversation $conversation, string $role, string $content, array $structured = []): ChatMessage
    {
        if (config('statamic-ai-chatbot.retention.mode') === 'leads_only') {
            return new ChatMessage();
        }

        return $conversation->messages()->create([
            'role' => $role,
            'content' => $content,
            'structured_output' => $structured ?: null,
            'citations' => $structured['citations'] ?? null,
            'metadata' => Arr::only($structured, ['intent', 'confidence', 'status', 'error_code', 'next_actions']),
        ]);
    }

    protected function resolveSiteHandle(?string $site): ?string
    {
        if ($site && ! str_contains($site, '.')) {
            return $site;
        }

        return rescue(
            fn () => ($site && Site::get($site))
                ? $site
                : (Site::current()?->handle() ?? Site::default()?->handle() ?? $site),
            $site,
            false
        );
    }

    protected function resolveLocaleHandle(?string $locale, ?string $site): ?string
    {
        if ($locale && ! str_contains($locale, '.')) {
            return $locale;
        }

        return rescue(
            fn () => ($locale && Site::get($locale)) ? $locale : $site,
            $site,
            false
        );
    }

    /**
     * @param  mixed  $actions
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeActions(mixed $actions): array
    {
        return collect(Arr::wrap($actions))
            ->map(fn ($action) => is_array($action) ? $this->normalizeAction($action) : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $action
     * @return array<string, mixed>|null
     */
    protected function normalizeAction(array $action): ?array
    {
        $type = Str::lower((string) ($action['type'] ?? ''));
        $label = trim((string) ($action['label'] ?? $action['cta_label'] ?? 'Continue'));
        $url = $this->filledString($action['url'] ?? null);
        $value = $this->filledString($action['value'] ?? null);
        $formId = $this->filledString($action['form_id'] ?? $action['form'] ?? null);
        $payload = $this->filledString($action['payload'] ?? $action['instructions'] ?? null);

        if ($type === 'url' && $value) {
            $url = $value;
            $type = 'link';
        }

        if (! $formId && in_array($type, ['form', 'form_fill'], true) && $value) {
            $formId = $value;
            $value = $payload;
        }

        if ($url) {
            return [
                'type' => 'link',
                'label' => $label,
                'url' => $url,
            ];
        }

        if ($type === 'email' && $value) {
            return [
                'type' => 'email',
                'label' => $label,
                'value' => $value,
            ];
        }

        if ($type === 'phone' && $value) {
            return [
                'type' => 'phone',
                'label' => $label,
                'value' => $value,
            ];
        }

        if ($type === 'ask' && $payload) {
            return [
                'type' => 'prompt',
                'label' => $label,
                'value' => $payload,
            ];
        }

        if ($type === 'lead_capture' || $formId || in_array($type, ['form', 'form_fill', 'schedule_call', 'support_request', 'human_handoff'], true)) {
            return array_filter([
                'type' => 'lead_capture',
                'label' => $label,
                'form_id' => $formId,
                'value' => $payload,
            ], fn ($item) => $item !== null && $item !== '');
        }

        return null;
    }

    protected function filledString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return filled($value) ? $value : null;
    }
}
