<?php

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('stores authored resources through cp endpoints', function () {
    $this->withoutMiddleware();

    $profileResponse = $this->postJson('/cp/aesircloud/statamic-ai-chatbot/profiles/save', [
        'handle' => 'support',
        'name' => 'Support Bot',
        'site' => 'default',
        'locale' => 'en',
        'is_default' => true,
        'active' => true,
        'branding' => ['voice' => 'Warm and direct'],
        'provider_overrides' => [
            'text' => ['driver' => 'anthropic', 'model' => 'claude-sonnet-4-5'],
            'embeddings' => ['enabled' => false],
        ],
        'widget_settings' => [
            'position' => 'bottom-right',
            'launcher_label' => 'Ask support',
        ],
        'support_settings' => [
            'contact_url' => '/contact',
            'email' => 'support@example.test',
        ],
        'lead_settings' => [
            'enabled' => true,
            'headline' => 'Need a hand?',
        ],
        'system_prompt' => 'Answer with support-first language.',
    ]);

    $profileResponse
        ->assertOk()
        ->assertJsonPath('data.profiles.0.handle', 'support')
        ->assertJsonPath('data.settings.default_profile_handle', 'support');

    $profile = BotProfile::query()->firstOrFail();

    $this->postJson('/cp/aesircloud/statamic-ai-chatbot/faqs/save', [
        'bot_profile_id' => $profile->id,
        'question' => 'How do I contact support?',
        'question_variants' => ['Need support', 'Talk to support'],
        'answer' => 'Use the contact page or leave your details here.',
        'priority' => 90,
        'cta_actions' => [
            ['type' => 'link', 'label' => 'Contact support', 'url' => '/contact'],
        ],
        'lead_capture_fields' => ['name', 'email', 'message'],
        'active' => true,
    ])
        ->assertOk()
        ->assertJsonPath('data.faqs.0.question', 'How do I contact support?');

    $this->postJson('/cp/aesircloud/statamic-ai-chatbot/sources/save', [
        'bot_profile_id' => $profile->id,
        'driver' => 'statamic',
        'name' => 'Site content',
        'config' => [
            'collections' => ['pages'],
            'navs' => ['main'],
        ],
        'active' => true,
    ])
        ->assertOk()
        ->assertJsonPath('data.sources.0.driver', 'statamic');

    $this->postJson('/cp/aesircloud/statamic-ai-chatbot/leads/save', [
        'bot_profile_id' => $profile->id,
        'name' => 'Jane Example',
        'email' => 'jane@example.test',
        'message' => 'Need pricing help.',
        'status' => 'new',
    ])
        ->assertOk()
        ->assertJsonPath('data.leads.0.email', 'jane@example.test');

    $this->postJson('/cp/aesircloud/statamic-ai-chatbot/profiles/save', [
        'id' => $profile->id,
        'handle' => 'support',
        'name' => 'Support Bot',
        'site' => 'default',
        'locale' => 'en',
        'is_default' => true,
        'active' => true,
        'branding' => ['voice' => 'Warm and direct'],
        'provider_overrides' => [
            'text' => ['driver' => 'anthropic', 'model' => 'claude-sonnet-4-5'],
            'embeddings' => ['enabled' => false],
        ],
        'widget_settings' => [
            'position' => 'bottom-right',
            'eyebrow_label' => null,
            'launcher_label' => '',
            'primary_color' => null,
        ],
        'support_settings' => [
            'contact_url' => '/contact',
            'email' => 'support@example.test',
        ],
        'lead_settings' => [
            'enabled' => true,
            'headline' => 'Need a hand?',
        ],
        'system_prompt' => 'Answer with support-first language.',
    ])
        ->assertOk()
        ->assertJsonPath('data.profiles.0.widget_settings.launcher_label', '');

    $savedProfile = BotProfile::query()->firstOrFail();

    expect($savedProfile->widget_settings['launcher_label'])->toBe('')
        ->and(array_key_exists('eyebrow_label', $savedProfile->widget_settings))->toBeFalse()
        ->and(array_key_exists('primary_color', $savedProfile->widget_settings))->toBeFalse();
});
