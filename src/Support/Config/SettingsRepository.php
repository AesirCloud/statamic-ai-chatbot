<?php

namespace AesirCloud\StatamicAiChatbot\Support\Config;

use AesirCloud\StatamicAiChatbot\Models\ChatbotSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SettingsRepository
{
    protected const GLOBAL_KEY = 'global';

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return [
            ...config('statamic-ai-chatbot', []),
            'ai' => [
                'default' => config('ai.default'),
                'default_for_embeddings' => config('ai.default_for_embeddings'),
                'default_for_reranking' => config('ai.default_for_reranking'),
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function save(array $payload): array
    {
        $payload = $this->sanitizePayload($payload);

        if (! $this->tableExists()) {
            $this->apply($payload);

            return $this->all();
        }

        ChatbotSetting::query()->updateOrCreate(
            ['key' => self::GLOBAL_KEY],
            ['payload' => $payload]
        );

        $this->apply($payload);

        return $this->all();
    }

    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function apply(?array $payload = null): void
    {
        $this->applyOverrides($payload ?? $this->stored());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function providerCatalog(): array
    {
        return [
            [
                'key' => 'openai',
                'label' => 'OpenAI / ChatGPT',
                'fields' => [
                    ['key' => 'key', 'label' => 'API key', 'secret' => true],
                    ['key' => 'url', 'label' => 'Base URL'],
                    ['key' => 'organization', 'label' => 'Organization'],
                    ['key' => 'project', 'label' => 'Project'],
                ],
            ],
            [
                'key' => 'anthropic',
                'label' => 'Anthropic / Claude',
                'fields' => [
                    ['key' => 'key', 'label' => 'API key', 'secret' => true],
                    ['key' => 'url', 'label' => 'Base URL'],
                    ['key' => 'version', 'label' => 'API version'],
                    ['key' => 'anthropic_beta', 'label' => 'Beta headers'],
                ],
            ],
            [
                'key' => 'gemini',
                'label' => 'Google Gemini',
                'fields' => [
                    ['key' => 'key', 'label' => 'API key', 'secret' => true],
                    ['key' => 'url', 'label' => 'Base URL'],
                ],
            ],
            [
                'key' => 'xai',
                'label' => 'xAI / Grok',
                'fields' => [
                    ['key' => 'key', 'label' => 'API key', 'secret' => true],
                    ['key' => 'url', 'label' => 'Base URL'],
                ],
            ],
            [
                'key' => 'groq',
                'label' => 'Groq',
                'fields' => [
                    ['key' => 'key', 'label' => 'API key', 'secret' => true],
                    ['key' => 'url', 'label' => 'Base URL'],
                ],
            ],
            [
                'key' => 'openrouter',
                'label' => 'OpenRouter',
                'fields' => [
                    ['key' => 'key', 'label' => 'API key', 'secret' => true],
                    ['key' => 'url', 'label' => 'Base URL'],
                    ['key' => 'site.http_referer', 'label' => 'HTTP referer'],
                    ['key' => 'site.x_title', 'label' => 'Site title'],
                ],
            ],
            [
                'key' => 'cohere',
                'label' => 'Cohere',
                'fields' => [
                    ['key' => 'key', 'label' => 'API key', 'secret' => true],
                ],
            ],
            [
                'key' => 'voyageai',
                'label' => 'Voyage AI',
                'fields' => [
                    ['key' => 'key', 'label' => 'API key', 'secret' => true],
                    ['key' => 'url', 'label' => 'Base URL'],
                ],
            ],
            [
                'key' => 'azure',
                'label' => 'Azure OpenAI',
                'fields' => [
                    ['key' => 'key', 'label' => 'API key', 'secret' => true],
                    ['key' => 'url', 'label' => 'Base URL'],
                    ['key' => 'api_version', 'label' => 'API version'],
                    ['key' => 'deployment', 'label' => 'Text deployment'],
                    ['key' => 'embedding_deployment', 'label' => 'Embeddings deployment'],
                ],
            ],
            [
                'key' => 'deepseek',
                'label' => 'DeepSeek',
                'fields' => [
                    ['key' => 'key', 'label' => 'API key', 'secret' => true],
                    ['key' => 'url', 'label' => 'Base URL'],
                ],
            ],
            [
                'key' => 'mistral',
                'label' => 'Mistral',
                'fields' => [
                    ['key' => 'key', 'label' => 'API key', 'secret' => true],
                    ['key' => 'url', 'label' => 'Base URL'],
                ],
            ],
            [
                'key' => 'ollama',
                'label' => 'Ollama',
                'fields' => [
                    ['key' => 'key', 'label' => 'API key', 'secret' => true],
                    ['key' => 'url', 'label' => 'Base URL'],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function applyOverrides(array $payload): void
    {
        if ($payload === []) {
            return;
        }

        $chatbotOverrides = Arr::except($payload, ['ai']);

        if ($chatbotOverrides !== []) {
            config([
                'statamic-ai-chatbot' => array_replace_recursive(
                    config('statamic-ai-chatbot', []),
                    $chatbotOverrides
                ),
            ]);
        }

        $aiOverrides = $payload['ai'] ?? [];

        if ($aiOverrides === []) {
            return;
        }

        $aiConfig = array_replace_recursive(
            config('ai', []),
            Arr::except($aiOverrides, ['providers'])
        );

        config(['ai' => $aiConfig]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function stored(): array
    {
        if (! $this->tableExists()) {
            return [];
        }

        return ChatbotSetting::query()
            ->where('key', self::GLOBAL_KEY)
            ->value('payload') ?? [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function sanitizePayload(array $payload): array
    {
        Arr::forget($payload, 'ai.providers');

        return $payload;
    }

    protected function tableExists(): bool
    {
        try {
            return Schema::hasTable('statamic_ai_chatbot_settings');
        } catch (Throwable) {
            return false;
        }
    }
}
