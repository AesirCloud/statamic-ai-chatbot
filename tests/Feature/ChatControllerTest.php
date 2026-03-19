<?php

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Models\FaqItem;
use AesirCloud\StatamicAiChatbot\Support\Chat\SupportAssistant;
use AesirCloud\StatamicAiChatbot\Support\Config\ProviderManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns curated faq answers without invoking the ai assistant path', function () {
    $this->withoutMiddleware();

    app()->instance(SupportAssistant::class, new class(app(ProviderManager::class)) extends SupportAssistant
    {
        protected function generateResponse(BotProfile $profile, string $prompt, array $candidate): mixed
        {
            throw new RuntimeException('The AI assistant should not be called for curated FAQ answers.');
        }
    });

    $profile = BotProfile::query()->create([
        'handle' => 'default',
        'name' => 'Default Bot',
        'is_default' => true,
        'active' => true,
        'branding' => [],
        'provider_overrides' => [],
        'widget_settings' => [],
        'support_settings' => [],
        'lead_settings' => [],
    ]);

    FaqItem::query()->create([
        'bot_profile_id' => $profile->id,
        'question' => 'What are your hours?',
        'answer' => 'We are open Monday through Friday from 9 AM to 5 PM.',
        'priority' => 100,
        'active' => true,
    ]);

    $this->postJson('/aesircloud/statamic-ai-chatbot/chat', [
        'profile' => 'default',
        'message' => 'What are your hours?',
    ])
        ->assertOk()
        ->assertJsonPath('intent', 'faq')
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('message', 'We are open Monday through Friday from 9 AM to 5 PM.');
});

it('returns a degraded 200 response with support actions when the assistant cannot answer', function () {
    $this->withoutMiddleware();

    app()->instance(SupportAssistant::class, new class(app(ProviderManager::class)) extends SupportAssistant
    {
        public function respond(BotProfile $profile, string $message, \Illuminate\Support\Collection $chunks): array
        {
            return [
                'message' => 'I am having trouble reaching the AI assistant right now. You can still contact support or leave your details for a follow-up.',
                'intent' => 'support',
                'confidence' => 24,
                'citations' => [],
                'next_actions' => [],
                'lead_capture_fields' => [],
                'status' => 'degraded',
                'error_code' => 'ai_provider_unavailable',
            ];
        }
    });

    BotProfile::query()->create([
        'handle' => 'default',
        'name' => 'Default Bot',
        'is_default' => true,
        'active' => true,
        'branding' => [],
        'provider_overrides' => [],
        'widget_settings' => [],
        'support_settings' => [
            'contact_url' => '/contact',
            'email' => 'support@example.test',
        ],
        'lead_settings' => [],
    ]);

    $this->postJson('/aesircloud/statamic-ai-chatbot/chat', [
        'profile' => 'default',
        'message' => 'Tell me about your services.',
    ])
        ->assertOk()
        ->assertJsonPath('status', 'degraded')
        ->assertJsonPath('error_code', 'ai_provider_unavailable')
        ->assertJsonPath('next_actions.0.type', 'link')
        ->assertJsonPath('next_actions.1.type', 'email')
        ->assertJsonPath('next_actions.2.type', 'lead_capture');
});
