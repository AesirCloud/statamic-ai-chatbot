<?php

namespace AesirCloud\StatamicAiChatbot\Http\Controllers\Cp;

use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\Concerns\RespondsWithDashboardData;
use AesirCloud\StatamicAiChatbot\Support\Config\SettingsRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class SaveSettingsController
{
    use RespondsWithDashboardData;

    public function __invoke(Request $request, SettingsRepository $settingsRepository): JsonResponse
    {
        $providerKeys = collect($settingsRepository->providerCatalog())->pluck('key')->all();
        $payload = $this->normalizePayload($request, $settingsRepository);

        $validated = Validator::make($payload, [
            'default_profile_handle' => ['nullable', 'string', 'max:255'],
            'providers' => ['required', 'array'],
            'providers.text' => ['required', 'array'],
            'providers.text.driver' => ['required', 'string', Rule::in($providerKeys)],
            'providers.text.model' => ['nullable', 'string', 'max:255'],
            'providers.embeddings' => ['required', 'array'],
            'providers.embeddings.driver' => ['required', 'string', Rule::in($providerKeys)],
            'providers.embeddings.model' => ['nullable', 'string', 'max:255'],
            'providers.embeddings.dimensions' => ['required', 'integer', 'min:1'],
            'providers.embeddings.enabled' => ['required', 'boolean'],
            'providers.reranking' => ['required', 'array'],
            'providers.reranking.driver' => ['nullable', 'string', Rule::in($providerKeys)],
            'providers.reranking.model' => ['nullable', 'string', 'max:255'],
            'providers.reranking.enabled' => ['required', 'boolean'],
            'retention' => ['required', 'array'],
            'retention.mode' => ['required', Rule::in(['conversations_and_leads', 'leads_only'])],
            'retention.conversation_days' => ['required', 'integer', 'min:1'],
            'retention.lead_days' => ['required', 'integer', 'min:1'],
            'queue' => ['required', 'array'],
            'queue.connection' => ['nullable', 'string', 'max:255'],
            'queue.queue' => ['nullable', 'string', 'max:255'],
            'widget' => ['required', 'array'],
            'widget.position' => ['required', Rule::in(['bottom-right', 'bottom-left', 'top-right', 'top-left'])],
            'widget.width' => ['nullable', 'string', 'max:255'],
            'widget.eyebrow_label' => ['nullable', 'string', 'max:255'],
            'widget.launcher_label' => ['nullable', 'string', 'max:255'],
            'widget.welcome_title' => ['nullable', 'string', 'max:255'],
            'widget.welcome_message' => ['nullable', 'string', 'max:2000'],
            'widget.primary_color' => ['nullable', 'string', 'max:32'],
            'widget.accent_color' => ['nullable', 'string', 'max:32'],
            'widget.button_text_color' => ['nullable', 'string', 'max:32'],
            'widget.surface_color' => ['nullable', 'string', 'max:32'],
            'widget.surface_text_color' => ['nullable', 'string', 'max:32'],
            'widget.border_color' => ['nullable', 'string', 'max:32'],
            'widget.support_hours' => ['nullable', 'string', 'max:255'],
            'widget.privacy_notice' => ['nullable', 'string', 'max:2000'],
            'widget.logo_url' => ['nullable', 'string', 'max:2000'],
            'knowledge' => ['required', 'array'],
            'knowledge.max_chunks' => ['required', 'integer', 'min:1'],
            'knowledge.max_chunk_characters' => ['required', 'integer', 'min:100'],
            'knowledge.chunk_overlap_characters' => ['required', 'integer', 'min:0'],
            'knowledge.min_similarity' => ['required', 'numeric', 'min:0', 'max:1'],
            'knowledge.rerank_top_n' => ['required', 'integer', 'min:1'],
            'lead_destinations' => ['required', 'array'],
            'lead_destinations.database' => ['required', 'boolean'],
            'lead_destinations.email' => ['required', 'array'],
            'lead_destinations.email.enabled' => ['required', 'boolean'],
            'lead_destinations.email.to' => ['nullable', 'string', 'max:255'],
            'lead_destinations.webhook' => ['required', 'array'],
            'lead_destinations.webhook.enabled' => ['required', 'boolean'],
            'lead_destinations.webhook.url' => ['nullable', 'string', 'max:2000'],
            'lead_destinations.webhook.secret' => ['nullable', 'string', 'max:1000'],
            'youtube' => ['required', 'array'],
            'youtube.enabled' => ['required', 'boolean'],
            'youtube.timeout' => ['required', 'integer', 'min:1'],
            'ai' => ['required', 'array'],
            'ai.default' => ['required', 'string', Rule::in($providerKeys)],
            'ai.default_for_embeddings' => ['required', 'string', Rule::in($providerKeys)],
            'ai.default_for_reranking' => ['nullable', 'string', Rule::in($providerKeys)],
        ])->validate();

        $settingsRepository->save($validated);

        return $this->dashboardResponse($request, 'Settings saved.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizePayload(Request $request, SettingsRepository $settingsRepository): array
    {
        $payload = array_replace_recursive(
            $settingsRepository->all(),
            $request->all()
        );

        $payload['ai'] = array_replace_recursive([
            'default' => 'openai',
            'default_for_embeddings' => 'openai',
            'default_for_reranking' => 'cohere',
        ], Arr::get($payload, 'ai', []));

        $payload['ai']['default'] = filled($payload['ai']['default'] ?? null)
            ? $payload['ai']['default']
            : 'openai';
        $payload['ai']['default_for_embeddings'] = filled($payload['ai']['default_for_embeddings'] ?? null)
            ? $payload['ai']['default_for_embeddings']
            : 'openai';
        $payload['ai']['default_for_reranking'] = filled($payload['ai']['default_for_reranking'] ?? null)
            ? $payload['ai']['default_for_reranking']
            : 'cohere';

        foreach (['text', 'embeddings', 'reranking'] as $channel) {
            $driver = Str::lower((string) Arr::get($payload, "providers.{$channel}.driver", ''));
            $model = Arr::get($payload, "providers.{$channel}.model");

            if ($this->shouldNormalizeModelIdentifier($driver) && is_string($model) && trim($model) !== '') {
                Arr::set($payload, "providers.{$channel}.model", Str::lower(trim($model)));
            }
        }

        return $payload;
    }

    protected function shouldNormalizeModelIdentifier(string $driver): bool
    {
        return in_array($driver, [
            'openai',
            'anthropic',
            'gemini',
            'xai',
            'groq',
            'openrouter',
            'cohere',
            'voyageai',
            'deepseek',
            'mistral',
        ], true);
    }
}
