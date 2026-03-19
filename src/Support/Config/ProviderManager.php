<?php

namespace AesirCloud\StatamicAiChatbot\Support\Config;

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProviderManager
{
    /**
     * @return array{driver:string,model:?string,dimensions?:int}
     */
    public function forText(?BotProfile $profile = null): array
    {
        $overrides = data_get($profile?->provider_overrides, 'text', []);
        $driver = $this->normalizeDriver($overrides['driver'] ?? config('statamic-ai-chatbot.providers.text.driver'));

        return [
            'driver' => $driver,
            'model' => $this->normalizeModelIdentifier(
                $driver,
                $overrides['model'] ?? config('statamic-ai-chatbot.providers.text.model')
            ),
        ];
    }

    /**
     * @return array{driver:string,model:?string,dimensions:int,enabled:bool}
     */
    public function forEmbeddings(?BotProfile $profile = null): array
    {
        $overrides = data_get($profile?->provider_overrides, 'embeddings', []);
        $driver = $this->normalizeDriver($overrides['driver'] ?? config('statamic-ai-chatbot.providers.embeddings.driver'));

        return [
            'driver' => $driver,
            'model' => $this->normalizeModelIdentifier(
                $driver,
                $overrides['model'] ?? config('statamic-ai-chatbot.providers.embeddings.model')
            ),
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
            'model' => $this->normalizeModelIdentifier($driver, $candidate['model'] ?? null),
        ];
    }

    protected function normalizeDriver(mixed $value): string
    {
        return Str::lower((string) ($this->nullableString($value) ?? ''));
    }

    protected function normalizeModelIdentifier(string $driver, mixed $value): ?string
    {
        $model = $this->nullableString($value);

        if ($model === null) {
            return null;
        }

        if (in_array($driver, $this->caseInsensitiveModelDrivers(), true)) {
            return Str::lower($model);
        }

        return $model;
    }

    /**
     * @return array<int, string>
     */
    protected function caseInsensitiveModelDrivers(): array
    {
        return [
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
