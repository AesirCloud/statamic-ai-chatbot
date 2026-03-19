<?php

namespace AesirCloud\StatamicAiChatbot\Support\Sources;

use AesirCloud\StatamicAiChatbot\Contracts\KnowledgeSourceDriver;
use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Models\SourceConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Statamic\Facades\Entry;
use Statamic\Facades\GlobalSet;
use Statamic\Facades\Nav;
use Statamic\Facades\Taxonomy;

class StatamicContentSourceDriver implements KnowledgeSourceDriver
{
    public function key(): string
    {
        return 'statamic';
    }

    public function label(): string
    {
        return 'Statamic Content';
    }

    public function sync(SourceConnection $source, BotProfile $profile): iterable
    {
        $config = $source->config ?? [];
        $sites = $this->resolveSites($config, $profile);

        return collect()
            ->merge($this->syncEntries($config, $sites))
            ->merge($this->syncGlobals($config, $sites))
            ->merge($this->syncNav($config, $sites))
            ->merge($this->syncTaxonomies($config, $sites))
            ->values()
            ->all();
    }

    protected function resolveSites(array $config, BotProfile $profile): Collection
    {
        return collect(Arr::wrap($config['sites'] ?? [$profile->site]))->filter();
    }

    /**
     * @param  Collection<int, string>  $sites
     * @return Collection<int, array<string, mixed>>
     */
    protected function syncEntries(array $config, Collection $sites): Collection
    {
        $collections = collect(Arr::wrap($config['collections'] ?? []));

        return Entry::query()
            ->when($collections->isNotEmpty(), fn ($query) => $query->whereIn('collection', $collections->all()))
            ->get()
            ->filter(fn ($entry) => $sites->isEmpty() || $sites->contains($entry->site()->handle()))
            ->map(function ($entry) {
                return [
                    'external_id' => 'entry:'.$entry->id(),
                    'site' => $entry->site()->handle(),
                    'locale' => $entry->locale(),
                    'title' => $entry->value('title') ?: $entry->slug(),
                    'excerpt' => Str::limit(strip_tags((string) $entry->value('excerpt')), 220),
                    'url' => $entry->absoluteUrl(),
                    'content' => json_encode($entry->data()->all(), JSON_PRETTY_PRINT) ?: '',
                    'metadata' => [
                        'collection' => $entry->collectionHandle(),
                        'slug' => $entry->slug(),
                        'type' => 'entry',
                    ],
                ];
            })
            ->values();
    }

    /**
     * @param  Collection<int, string>  $sites
     * @return Collection<int, array<string, mixed>>
     */
    protected function syncGlobals(array $config, Collection $sites): Collection
    {
        $handles = collect(Arr::wrap($config['globals'] ?? []));

        return GlobalSet::all()
            ->filter(fn ($globalSet) => $handles->isEmpty() || $handles->contains($globalSet->handle()))
            ->flatMap(function ($globalSet) use ($sites) {
                return collect($globalSet->sites())
                    ->filter(fn ($variables, $site) => $sites->isEmpty() || $sites->contains($site))
                    ->map(function ($variables, $site) use ($globalSet) {
                        return [
                            'external_id' => 'global:'.$globalSet->handle().':'.$site,
                            'site' => $site,
                            'locale' => $site,
                            'title' => $globalSet->title(),
                            'excerpt' => Str::limit(json_encode($variables, JSON_PRETTY_PRINT) ?: '', 220),
                            'url' => null,
                            'content' => json_encode($variables, JSON_PRETTY_PRINT) ?: '',
                            'metadata' => [
                                'handle' => $globalSet->handle(),
                                'type' => 'global',
                            ],
                        ];
                    });
            })
            ->values();
    }

    /**
     * @param  Collection<int, string>  $sites
     * @return Collection<int, array<string, mixed>>
     */
    protected function syncNav(array $config, Collection $sites): Collection
    {
        $handles = collect(Arr::wrap($config['navs'] ?? []));

        return Nav::all()
            ->filter(fn ($nav) => $handles->isEmpty() || $handles->contains($nav->handle()))
            ->flatMap(function ($nav) use ($sites) {
                return collect($nav->trees())
                    ->filter(fn ($tree, $site) => $sites->isEmpty() || $sites->contains($site))
                    ->map(function ($tree, $site) use ($nav) {
                        $items = collect($tree->tree())
                            ->map(fn ($item) => implode(' ', array_filter([
                                $item['title'] ?? null,
                                $item['url'] ?? null,
                            ])))
                            ->implode("\n");

                        return [
                            'external_id' => 'nav:'.$nav->handle().':'.$site,
                            'site' => $site,
                            'locale' => $site,
                            'title' => $nav->title(),
                            'excerpt' => Str::limit($items, 220),
                            'url' => null,
                            'content' => $items,
                            'metadata' => [
                                'handle' => $nav->handle(),
                                'type' => 'nav',
                            ],
                        ];
                    });
            })
            ->values();
    }

    /**
     * @param  Collection<int, string>  $sites
     * @return Collection<int, array<string, mixed>>
     */
    protected function syncTaxonomies(array $config, Collection $sites): Collection
    {
        $handles = collect(Arr::wrap($config['taxonomies'] ?? []));

        return Taxonomy::all()
            ->filter(fn ($taxonomy) => $handles->isEmpty() || $handles->contains($taxonomy->handle()))
            ->flatMap(fn ($taxonomy) => $taxonomy->queryTaxables()->get()->map(function ($term) use ($taxonomy, $sites) {
                $site = method_exists($term, 'site') ? $term->site()?->handle() : null;

                if ($sites->isNotEmpty() && $site && ! $sites->contains($site)) {
                    return null;
                }

                return [
                    'external_id' => 'taxonomy:'.$taxonomy->handle().':'.$term->id(),
                    'site' => $site,
                    'locale' => $site,
                    'title' => $term->title(),
                    'excerpt' => Str::limit(json_encode($term->data()->all(), JSON_PRETTY_PRINT) ?: '', 220),
                    'url' => method_exists($term, 'absoluteUrl') ? $term->absoluteUrl() : null,
                    'content' => json_encode($term->data()->all(), JSON_PRETTY_PRINT) ?: '',
                    'metadata' => [
                        'handle' => $taxonomy->handle(),
                        'type' => 'taxonomy',
                    ],
                ];
            }))
            ->filter()
            ->values();
    }
}
