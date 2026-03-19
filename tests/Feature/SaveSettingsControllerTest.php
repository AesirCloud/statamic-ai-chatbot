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
    $payload['widget']['button_text_color'] = '#101820';
    $payload['widget']['surface_color'] = '#f5f5f5';
    $payload['widget']['surface_text_color'] = '#4b5563';
    $payload['widget']['border_color'] = '#d1d5db';

    $this->postJson('/cp/aesircloud/statamic-ai-chatbot/settings/save', $payload)
        ->assertOk()
        ->assertJsonPath('data.settings.widget.eyebrow_label', 'Peak Support AI')
        ->assertJsonPath('data.settings.widget.launcher_label', 'Ask AesirCloud')
        ->assertJsonPath('data.settings.widget.button_text_color', '#101820')
        ->assertJsonPath('data.settings.widget.surface_color', '#f5f5f5')
        ->assertJsonPath('data.settings.widget.surface_text_color', '#4b5563')
        ->assertJsonPath('data.settings.widget.border_color', '#d1d5db');

    $payload['widget']['launcher_label'] = '';

    $this->postJson('/cp/aesircloud/statamic-ai-chatbot/settings/save', $payload)
        ->assertOk()
        ->assertJsonPath('data.settings.widget.launcher_label', '');

    $stored = ChatbotSetting::query()->firstOrFail()->payload;

    expect(ChatbotSetting::query()->count())->toBe(1)
        ->and(data_get($stored, 'widget.launcher_label'))->toBe('')
        ->and(data_get($stored, 'widget.button_text_color'))->toBe('#101820')
        ->and(data_get($stored, 'widget.surface_color'))->toBe('#f5f5f5')
        ->and(data_get($stored, 'widget.surface_text_color'))->toBe('#4b5563')
        ->and(data_get($stored, 'widget.border_color'))->toBe('#d1d5db')
        ->and(data_get($stored, 'ai.providers'))->toBeNull();
});

it('normalizes known provider model ids when saving settings', function () {
    $this->withoutMiddleware();

    $payload = app(SettingsRepository::class)->all();
    $payload['providers']['text']['driver'] = 'openai';
    $payload['providers']['text']['model'] = 'GPT-5.4-NANO';
    $payload['providers']['embeddings']['driver'] = 'openai';
    $payload['providers']['embeddings']['model'] = 'TEXT-EMBEDDING-3-SMALL';

    $this->postJson('/cp/aesircloud/statamic-ai-chatbot/settings/save', $payload)
        ->assertOk()
        ->assertJsonPath('data.settings.providers.text.model', 'gpt-5.4-nano')
        ->assertJsonPath('data.settings.providers.embeddings.model', 'text-embedding-3-small');

    $stored = ChatbotSetting::query()->firstOrFail()->payload;

    expect(data_get($stored, 'providers.text.model'))->toBe('gpt-5.4-nano')
        ->and(data_get($stored, 'providers.embeddings.model'))->toBe('text-embedding-3-small');
});
