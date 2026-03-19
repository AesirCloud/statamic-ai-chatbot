<?php

use AesirCloud\StatamicAiChatbot\Models\ChatbotSetting;
use AesirCloud\StatamicAiChatbot\Support\Config\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists runtime settings and ai credentials', function () {
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
            'providers' => [
                'anthropic' => [
                    'driver' => 'anthropic',
                    'key' => 'anthropic-secret',
                    'version' => '2023-06-01',
                ],
                'openai' => [
                    'driver' => 'openai',
                    'key' => 'openai-secret',
                ],
            ],
        ],
    ]);

    $settings = app(SettingsRepository::class)->save($payload);

    expect(ChatbotSetting::query()->count())->toBe(1)
        ->and($settings['default_profile_handle'])->toBe('sales')
        ->and(config('statamic-ai-chatbot.providers.text.driver'))->toBe('anthropic')
        ->and(config('ai.default'))->toBe('anthropic')
        ->and(config('ai.providers.anthropic.key'))->toBe('anthropic-secret')
        ->and(config('ai.providers.openai.key'))->toBe('openai-secret');
});
