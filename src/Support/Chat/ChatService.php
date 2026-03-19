<?php

namespace AesirCloud\StatamicAiChatbot\Support\Chat;

use AesirCloud\StatamicAiChatbot\Contracts\SupportHandoffResolver;
use AesirCloud\StatamicAiChatbot\Models\ChatConversation;
use AesirCloud\StatamicAiChatbot\Models\KnowledgeDocument;
use AesirCloud\StatamicAiChatbot\Models\ChatMessage;
use AesirCloud\StatamicAiChatbot\Support\Knowledge\FaqMatcher;
use AesirCloud\StatamicAiChatbot\Support\Knowledge\KnowledgeRetriever;
use AesirCloud\StatamicAiChatbot\Support\Profiles\BotProfileResolver;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
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
            $response['citations'] = $this->mergeCitations(
                $this->normalizeCitations($response['citations'] ?? [], $chunks),
                $this->fallbackCitations($chunks)
            );
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

    /**
     * @param  mixed  $citations
     * @param  Collection<int, \AesirCloud\StatamicAiChatbot\Models\KnowledgeChunk>  $chunks
     * @return array<int, array<string, mixed>>
     */
    protected function normalizeCitations(mixed $citations, Collection $chunks): array
    {
        return collect(Arr::wrap($citations))
            ->map(fn ($citation) => $this->normalizeCitation($citation, $chunks))
            ->filter()
            ->unique(fn (array $citation) => ($citation['title'] ?? '').'|'.($citation['url'] ?? ''))
            ->values()
            ->all();
    }

    /**
     * @param  mixed  $citation
     * @param  Collection<int, \AesirCloud\StatamicAiChatbot\Models\KnowledgeChunk>  $chunks
     * @return array<string, mixed>|null
     */
    protected function normalizeCitation(mixed $citation, Collection $chunks): ?array
    {
        if (is_string($citation)) {
            return $this->normalizeCitationString($citation, $chunks);
        }

        if (! is_array($citation)) {
            return null;
        }

        $chunk = $this->resolveCitationChunk($citation, $chunks);
        $url = $this->filledString(
            Arr::get($citation, 'url')
            ?? Arr::get($citation, 'href')
            ?? Arr::get($citation, 'link')
            ?? Arr::get($citation, 'value')
        );

        if ($url && ! filter_var($url, FILTER_VALIDATE_URL)) {
            $url = null;
        }

        $title = $this->filledString(
            Arr::get($citation, 'title')
            ?? Arr::get($citation, 'label')
            ?? Arr::get($citation, 'name')
        );

        $title ??= $chunk ? $this->citationTitleFromChunk($chunk) : null;
        $url ??= $chunk ? $this->citationUrlFromChunk($chunk) : null;
        $title ??= $url ? $this->humanizeCitationUrl($url) : null;

        if (! $title && ! $url) {
            return null;
        }

        return array_filter([
            'title' => $title,
            'url' => $url,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  Collection<int, \AesirCloud\StatamicAiChatbot\Models\KnowledgeChunk>  $chunks
     * @return array<string, mixed>|null
     */
    protected function normalizeCitationString(string $citation, Collection $chunks): ?array
    {
        $citation = trim($citation);

        if ($citation === '') {
            return null;
        }

        if (Str::startsWith($citation, ['{', '['])) {
            $decoded = json_decode($citation, true);

            if (is_array($decoded)) {
                return $this->normalizeCitation($decoded, $chunks);
            }
        }

        if (filter_var($citation, FILTER_VALIDATE_URL)) {
            $chunk = $this->resolveCitationChunk(['url' => $citation], $chunks);

            return array_filter([
                'title' => $chunk ? $this->citationTitleFromChunk($chunk) : $this->humanizeCitationUrl($citation),
                'url' => $chunk ? ($this->citationUrlFromChunk($chunk) ?? $citation) : $citation,
            ], fn ($value) => $value !== null && $value !== '');
        }

        return ['title' => $citation];
    }

    /**
     * @param  array<string, mixed>  $citation
     * @param  Collection<int, \AesirCloud\StatamicAiChatbot\Models\KnowledgeChunk>  $chunks
     */
    protected function resolveCitationChunk(array $citation, Collection $chunks): ?object
    {
        $url = $this->filledString(
            Arr::get($citation, 'url')
            ?? Arr::get($citation, 'href')
            ?? Arr::get($citation, 'link')
            ?? Arr::get($citation, 'value')
        );

        if ($url && filter_var($url, FILTER_VALIDATE_URL)) {
            $normalizedUrl = rtrim($url, '/');

            $match = $chunks->first(function ($chunk) use ($normalizedUrl) {
                $chunkUrl = rtrim((string) data_get($chunk->metadata, 'url', ''), '/');

                return $chunkUrl !== '' && $chunkUrl === $normalizedUrl;
            });

            if ($match) {
                return $match;
            }
        }

        $sourceIndex = Arr::get($citation, 'source')
            ?? Arr::get($citation, 'source_number')
            ?? Arr::get($citation, 'source_index')
            ?? Arr::get($citation, 'index')
            ?? Arr::get($citation, 'ref');

        if (is_numeric($sourceIndex)) {
            $numericIndex = (int) $sourceIndex;

            return $chunks->values()->get($numericIndex > 0 ? $numericIndex - 1 : $numericIndex);
        }

        return null;
    }

    /**
     * @param  Collection<int, \AesirCloud\StatamicAiChatbot\Models\KnowledgeChunk>  $chunks
     * @return array<int, array<string, mixed>>
     */
    protected function fallbackCitations(Collection $chunks): array
    {
        return $chunks->map(function ($chunk) {
            return array_filter([
                'title' => $this->citationTitleFromChunk($chunk) ?? 'Knowledge base',
                'url' => $this->citationUrlFromChunk($chunk),
                'score' => round((float) $chunk->score, 4),
                'driver' => data_get($chunk->metadata, 'driver'),
            ], fn ($value) => $value !== null && $value !== '');
        })
            ->unique(fn (array $citation) => ($citation['title'] ?? '').'|'.($citation['url'] ?? ''))
            ->take((int) config('statamic-ai-chatbot.knowledge.max_chunks', 6))
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $preferred
     * @param  array<int, array<string, mixed>>  $fallback
     * @return array<int, array<string, mixed>>
     */
    protected function mergeCitations(array $preferred, array $fallback): array
    {
        return collect([...$preferred, ...$fallback])
            ->filter(fn ($citation) => is_array($citation) && ($citation['title'] ?? null || $citation['url'] ?? null))
            ->unique(fn (array $citation) => ($citation['title'] ?? '').'|'.($citation['url'] ?? ''))
            ->take((int) config('statamic-ai-chatbot.knowledge.max_chunks', 6))
            ->values()
            ->all();
    }

    protected function citationTitleFromChunk(object $chunk): ?string
    {
        if ($canonical = $this->canonicalCitationDocument($chunk)) {
            return $this->filledString($canonical->title);
        }

        return $this->filledString(
            data_get($chunk->metadata, 'title')
            ?? data_get($chunk, 'document.title')
        );
    }

    protected function citationUrlFromChunk(object $chunk): ?string
    {
        if ($canonical = $this->canonicalCitationDocument($chunk)) {
            return $this->filledString($canonical->url);
        }

        return $this->filledString(
            data_get($chunk->metadata, 'url')
            ?? data_get($chunk, 'document.url')
        );
    }

    protected function canonicalCitationDocument(object $chunk): ?KnowledgeDocument
    {
        $type = $this->filledString((string) data_get($chunk->metadata, 'type', ''));

        if ($type !== 'taxonomy') {
            return null;
        }

        $title = $this->filledString(
            data_get($chunk->metadata, 'title')
            ?? data_get($chunk, 'document.title')
        );

        if (! $title) {
            return null;
        }

        return KnowledgeDocument::query()
            ->where('bot_profile_id', data_get($chunk, 'bot_profile_id'))
            ->where('site', data_get($chunk, 'site'))
            ->where('locale', data_get($chunk, 'locale'))
            ->where('title', $title)
            ->get()
            ->first(function (KnowledgeDocument $document) {
                return data_get($document->metadata, 'type') === 'entry' && filled($document->url);
            });
    }

    protected function humanizeCitationUrl(string $url): string
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');

        if ($path === '') {
            return $url;
        }

        return Str::headline(str_replace('/', ' ', Str::afterLast($path, '/')));
    }
}
