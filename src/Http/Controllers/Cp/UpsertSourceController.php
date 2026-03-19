<?php

namespace AesirCloud\StatamicAiChatbot\Http\Controllers\Cp;

use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\Concerns\RespondsWithDashboardData;
use AesirCloud\StatamicAiChatbot\Models\SourceConnection;
use AesirCloud\StatamicAiChatbot\Support\Sources\DriverManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class UpsertSourceController
{
    use RespondsWithDashboardData;

    public function __invoke(Request $request, DriverManager $driverManager): JsonResponse
    {
        $sourceId = $request->integer('id') ?: null;
        $driverKeys = collect($driverManager->options())->pluck('key')->all();

        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:source_connections,id'],
            'bot_profile_id' => ['required', 'integer', 'exists:bot_profiles,id'],
            'driver' => ['required', 'string', Rule::in($driverKeys)],
            'name' => ['required', 'string', 'max:255'],
            'config' => ['nullable', 'array'],
            'config.sites' => ['nullable', 'array'],
            'config.sites.*' => ['nullable', 'string', 'max:255'],
            'config.collections' => ['nullable', 'array'],
            'config.collections.*' => ['nullable', 'string', 'max:255'],
            'config.globals' => ['nullable', 'array'],
            'config.globals.*' => ['nullable', 'string', 'max:255'],
            'config.navs' => ['nullable', 'array'],
            'config.navs.*' => ['nullable', 'string', 'max:255'],
            'config.taxonomies' => ['nullable', 'array'],
            'config.taxonomies.*' => ['nullable', 'string', 'max:255'],
            'config.items' => ['nullable', 'array'],
            'config.items.*' => ['nullable', 'array'],
            'config.items.*.url' => ['nullable', 'string', 'max:2000'],
            'config.items.*.timestamp' => ['nullable', 'string', 'max:255'],
            'config.items.*.transcript' => ['nullable', 'string', 'max:50000'],
            'active' => ['required', 'boolean'],
        ]);

        SourceConnection::query()->updateOrCreate(
            ['id' => $sourceId],
            [
                'bot_profile_id' => $validated['bot_profile_id'],
                'driver' => $validated['driver'],
                'name' => $validated['name'],
                'config' => $this->normalizeConfig($validated['driver'], $validated['config'] ?? []),
                'active' => $validated['active'],
                'status' => $sourceId ? SourceConnection::query()->findOrFail($sourceId)->status : 'pending',
            ]
        );

        return $this->dashboardResponse($request, $sourceId ? 'Source updated.' : 'Source created.');
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected function normalizeConfig(string $driver, array $config): array
    {
        if ($driver === 'youtube') {
            return [
                'items' => collect($config['items'] ?? [])
                    ->filter(fn ($item) => filled($item['url'] ?? null))
                    ->map(fn ($item) => [
                        'url' => $item['url'],
                        'timestamp' => $item['timestamp'] ?? null,
                        'transcript' => $item['transcript'] ?? '',
                    ])
                    ->values()
                    ->all(),
            ];
        }

        return [
            'sites' => $this->cleanList($config['sites'] ?? []),
            'collections' => $this->cleanList($config['collections'] ?? []),
            'globals' => $this->cleanList($config['globals'] ?? []),
            'navs' => $this->cleanList($config['navs'] ?? []),
            'taxonomies' => $this->cleanList($config['taxonomies'] ?? []),
        ];
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    protected function cleanList(array $values): array
    {
        return Collection::make($values)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();
    }
}
