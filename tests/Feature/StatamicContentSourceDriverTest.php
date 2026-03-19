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
