<?php

use AesirCloud\StatamicAiChatbot\Models\ChatbotSetting;
use AesirCloud\StatamicAiChatbot\Support\Config\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists runtime settings while leaving provider credentials in env', function () {
    config([
        'ai.providers.anthropic.key' => 'env-anthropic-secret',
        'ai.providers.openai.key' => 'env-openai-secret',
    ]);

    $base = require __DIR__.'/../../config/statamic-ai-chatbot.php';

    $payload = array_replace_recursive($base, [
        'default_profile_handle' => 'sales',
        'providers' => [
            'text' => [
                'driver' => 'anthropic',
                'model' => 'claude-sonnet-4-5',
            ],
        ],
        'ai' => [
            'default' => 'anthropic',
            'default_for_embeddings' => 'openai',
            'default_for_reranking' => 'cohere',
        ],
    ]);

    $settings = app(SettingsRepository::class)->save($payload);
    $stored = ChatbotSetting::query()->firstOrFail()->payload;

    expect(ChatbotSetting::query()->count())->toBe(1)
        ->and($settings['default_profile_handle'])->toBe('sales')
        ->and(config('statamic-ai-chatbot.providers.text.driver'))->toBe('anthropic')
        ->and(config('ai.default'))->toBe('anthropic')
        ->and(config('ai.providers.anthropic.key'))->toBe('env-anthropic-secret')
        ->and(config('ai.providers.openai.key'))->toBe('env-openai-secret')
        ->and(data_get($stored, 'ai.providers'))->toBeNull()
        ->and(data_get($settings, 'ai.providers'))->toBeNull();
});

it('ignores legacy stored provider secrets so env credentials win at runtime', function () {
    config([
        'ai.providers.openai.key' => 'fresh-env-openai-key',
    ]);

    ChatbotSetting::query()->create([
        'key' => 'global',
        'payload' => [
            'default_profile_handle' => 'default',
            'ai' => [
                'default' => 'openai',
                'providers' => [
                    'openai' => [
                        'driver' => 'openai',
                        'key' => 'stale-database-key',
                    ],
                ],
            ],
        ],
    ]);

    app(SettingsRepository::class)->apply();

    expect(config('ai.providers.openai.key'))->toBe('fresh-env-openai-key');
});
