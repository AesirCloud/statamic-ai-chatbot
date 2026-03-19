<?php

namespace AesirCloud\StatamicAiChatbot\Support\Config;

use AesirCloud\StatamicAiChatbot\Models\BotProfile;

class ProviderManager
{
    /**
     * @return array{driver:string,model:?string,dimensions?:int}
     */
    public function forText(?BotProfile $profile = null): array
    {
        $overrides = data_get($profile?->provider_overrides, 'text', []);

        return [
            'driver' => (string) ($overrides['driver'] ?? config('statamic-ai-chatbot.providers.text.driver')),
            'model' => (string) ($overrides['model'] ?? config('statamic-ai-chatbot.providers.text.model')),
        ];
    }

    /**
     * @return array{driver:string,model:?string,dimensions:int,enabled:bool}
     */
    public function forEmbeddings(?BotProfile $profile = null): array
    {
        $overrides = data_get($profile?->provider_overrides, 'embeddings', []);

        return [
            'driver' => (string) ($overrides['driver'] ?? config('statamic-ai-chatbot.providers.embeddings.driver')),
            'model' => (string) ($overrides['model'] ?? config('statamic-ai-chatbot.providers.embeddings.model')),
            'dimensions' => (int) ($overrides['dimensions'] ?? config('statamic-ai-chatbot.providers.embeddings.dimensions', 1536)),
            'enabled' => (bool) ($overrides['enabled'] ?? config('statamic-ai-chatbot.providers.embeddings.enabled', true)),
        ];
    }
}
