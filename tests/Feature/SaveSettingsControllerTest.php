<?php

use AesirCloud\StatamicAiChatbot\Models\ChatbotSetting;
use AesirCloud\StatamicAiChatbot\Support\Config\SettingsRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('saves settings when the launcher label is updated or cleared', function () {
    $this->withoutMiddleware();

    $payload = app(SettingsRepository::class)->all();
    $payload['widget']['eyebrow_label'] = 'Peak Support AI';
    $payload['widget']['launcher_label'] = 'Ask AesirCloud';

    $this->postJson('/cp/aesircloud/statamic-ai-chatbot/settings/save', $payload)
        ->assertOk()
        ->assertJsonPath('data.settings.widget.eyebrow_label', 'Peak Support AI')
        ->assertJsonPath('data.settings.widget.launcher_label', 'Ask AesirCloud');

    $payload['widget']['launcher_label'] = '';

    $this->postJson('/cp/aesircloud/statamic-ai-chatbot/settings/save', $payload)
        ->assertOk()
        ->assertJsonPath('data.settings.widget.launcher_label', '');

    $stored = ChatbotSetting::query()->firstOrFail()->payload;

    expect(ChatbotSetting::query()->count())->toBe(1)
        ->and(data_get($stored, 'widget.launcher_label'))->toBe('')
        ->and(data_get($stored, 'ai.providers'))->toBeNull();
});
