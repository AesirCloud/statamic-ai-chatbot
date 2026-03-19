<?php

namespace AesirCloud\StatamicAiChatbot\Support\Config;

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use Illuminate\Support\Collection;

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
            'model' => $this->nullableString($overrides['model'] ?? config('statamic-ai-chatbot.providers.text.model')),
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
            'model' => $this->nullableString($overrides['model'] ?? config('statamic-ai-chatbot.providers.embeddings.model')),
            'dimensions' => (int) ($overrides['dimensions'] ?? config('statamic-ai-chatbot.providers.embeddings.dimensions', 1536)),
            'enabled' => (bool) ($overrides['enabled'] ?? config('statamic-ai-chatbot.providers.embeddings.enabled', true)),
        ];
    }

    /**
     * @return array<int, array{driver:string,model:?string}>
     */
    public function forTextCandidates(?BotProfile $profile = null): array
    {
        $fallbacks = config('statamic-ai-chatbot.providers.text_fallbacks', []);

        return (new Collection([$this->forText($profile), ...$fallbacks]))
            ->map(fn (mixed $candidate) => is_array($candidate) ? $this->normalizeTextCandidate($candidate) : null)
            ->filter()
            ->unique(fn (array $candidate) => $candidate['driver'].'::'.($candidate['model'] ?? ''))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return array{driver:string,model:?string}|null
     */
    protected function normalizeTextCandidate(array $candidate): ?array
    {
        if (($candidate['enabled'] ?? true) === false) {
            return null;
        }

        $driver = $this->nullableString($candidate['driver'] ?? null);

        if (! filled($driver)) {
            return null;
        }

        return [
            'driver' => $driver,
            'model' => $this->nullableString($candidate['model'] ?? null),
        ];
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
