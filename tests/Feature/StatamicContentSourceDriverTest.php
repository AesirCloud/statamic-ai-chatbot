<?php

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Support\Sources\StatamicContentSourceDriver;
use Illuminate\Support\Collection;

it('normalizes site filters without requiring a collection instance', function () {
    $driver = new class extends StatamicContentSourceDriver
    {
        public function resolveForTest(array $config, BotProfile $profile): Collection
        {
            return $this->resolveSites($config, $profile);
        }
    };

    $profile = new BotProfile([
        'handle' => 'default',
        'name' => 'Default Bot',
        'site' => null,
    ]);

    $sites = $driver->resolveForTest([], $profile);

    expect($sites)->toBeInstanceOf(Collection::class)
        ->and($sites->all())->toBe([]);
});

it('indexes taxonomy terms using statamic 6 queryTerms localizations', function () {
    $term = new class
    {
        public function id(): string
        {
            return 'vendors::ram-mount';
        }

        public function localizations(): Collection
        {
            return collect([
                'default' => new class
                {
                    public function data(): Collection
                    {
                        return collect(['title' => 'RAM Mounts', 'summary' => 'Rugged mounting solutions']);
                    }

                    public function title(): string
                    {
                        return 'RAM Mounts';
                    }

                    public function slug(): string
                    {
                        return 'ram-mounts';
                    }

                    public function absoluteUrl(): string
                    {
                        return 'https://example.test/vendors/ram-mounts';
                    }
                },
            ]);
        }
    };

    $taxonomy = new class($term)
    {
        public function __construct(protected object $term)
        {
        }

        public function handle(): string
        {
            return 'vendors';
        }

        public function queryTerms(): object
        {
            return new class($this->term)
            {
                public function __construct(protected object $term)
                {
                }

                public function get(): Collection
                {
                    return collect([$this->term]);
                }
            };
        }
    };

    $driver = new class($taxonomy) extends StatamicContentSourceDriver
    {
        public function __construct(protected object $taxonomy)
        {
        }

        public function syncTaxonomiesForTest(array $config, Collection $sites): Collection
        {
            return $this->syncTaxonomies($config, $sites);
        }

        protected function taxonomies(): Collection
        {
            return collect([$this->taxonomy]);
        }
    };

    $documents = $driver->syncTaxonomiesForTest([
        'taxonomies' => ['vendors'],
    ], collect(['default']));

    expect($documents)->toHaveCount(1)
        ->and($documents->first())->toMatchArray([
            'external_id' => 'taxonomy:vendors:vendors::ram-mount:default',
            'site' => 'default',
            'locale' => 'default',
            'title' => 'RAM Mounts',
            'url' => 'https://example.test/vendors/ram-mounts',
            'metadata' => [
                'handle' => 'vendors',
                'slug' => 'ram-mounts',
                'type' => 'taxonomy',
            ],
        ]);
});

it('indexes taxonomy terms when statamic 6 queryTerms returns localized terms directly', function () {
    $localizedTerm = new class
    {
        public function id(): string
        {
            return 'vendors::ram-mount';
        }

        public function locale(): string
        {
            return 'default';
        }

        public function data(): Collection
        {
            return collect(['title' => 'RAM Mounts', 'summary' => 'Rugged mounting systems']);
        }

        public function title(): string
        {
            return 'RAM Mounts';
        }

        public function slug(): string
        {
            return 'ram-mounts';
        }

        public function absoluteUrl(): string
        {
            return 'https://example.test/vendors/ram-mounts';
        }
    };

    $taxonomy = new class($localizedTerm)
    {
        public function __construct(protected object $term)
        {
        }

        public function handle(): string
        {
            return 'vendors';
        }

        public function queryTerms(): object
        {
            return new class($this->term)
            {
                public function __construct(protected object $term)
                {
                }

                public function get(): Collection
                {
                    return collect([$this->term]);
                }
            };
        }
    };

    $driver = new class($taxonomy) extends StatamicContentSourceDriver
    {
        public function __construct(protected object $taxonomy)
        {
        }

        public function syncTaxonomiesForTest(array $config, Collection $sites): Collection
        {
            return $this->syncTaxonomies($config, $sites);
        }

        protected function taxonomies(): Collection
        {
            return collect([$this->taxonomy]);
        }
    };

    $documents = $driver->syncTaxonomiesForTest([
        'taxonomies' => ['vendors'],
    ], collect(['default']));

    expect($documents)->toHaveCount(1)
        ->and($documents->first())->toMatchArray([
            'external_id' => 'taxonomy:vendors:vendors::ram-mount:default',
            'site' => 'default',
            'locale' => 'default',
            'title' => 'RAM Mounts',
            'url' => 'https://example.test/vendors/ram-mounts',
            'metadata' => [
                'handle' => 'vendors',
                'slug' => 'ram-mounts',
                'type' => 'taxonomy',
            ],
        ]);
});
