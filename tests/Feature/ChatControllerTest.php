<?php

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Models\FaqItem;
use AesirCloud\StatamicAiChatbot\Models\KnowledgeChunk;
use AesirCloud\StatamicAiChatbot\Models\KnowledgeDocument;
use AesirCloud\StatamicAiChatbot\Models\SourceConnection;
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

it('falls back to a valid statamic site when a stored profile site is stale', function () {
    $this->withoutMiddleware();

    app()->instance(SupportAssistant::class, new class(app(ProviderManager::class)) extends SupportAssistant
    {
        public function respond(BotProfile $profile, string $message, \Illuminate\Support\Collection $chunks): array
        {
            return [
                'message' => 'Matched: '.$chunks->pluck('metadata.title')->implode(', '),
                'intent' => 'knowledge',
                'confidence' => 88,
                'citations' => [],
                'next_actions' => [],
                'lead_capture_fields' => [],
                'status' => 'ok',
                'error_code' => null,
            ];
        }
    });

    config()->set('statamic-ai-chatbot.providers.embeddings.enabled', false);

    $profile = BotProfile::query()->create([
        'handle' => 'default',
        'name' => 'Default Bot',
        'site' => 'www.3eyetech.com',
        'is_default' => true,
        'active' => true,
        'branding' => [],
        'provider_overrides' => [],
        'widget_settings' => [],
        'support_settings' => [],
        'lead_settings' => [],
    ]);

    $source = SourceConnection::query()->create([
        'bot_profile_id' => $profile->id,
        'name' => 'Site content',
        'driver' => 'statamic',
        'active' => true,
        'status' => 'ready',
    ]);

    $document = KnowledgeDocument::query()->create([
        'bot_profile_id' => $profile->id,
        'source_connection_id' => $source->id,
        'driver' => 'statamic',
        'external_id' => 'taxonomy:vendors:ram-mounts:default',
        'site' => 'default',
        'locale' => 'default',
        'title' => 'RAM Mounts',
        'url' => 'https://example.test/vendors/ram-mounts',
        'metadata' => ['type' => 'taxonomy', 'handle' => 'vendors', 'slug' => 'ram-mounts'],
        'content_hash' => sha1('ram mounts'),
    ]);

    KnowledgeChunk::query()->create([
        'knowledge_document_id' => $document->id,
        'bot_profile_id' => $profile->id,
        'site' => 'default',
        'locale' => 'default',
        'position' => 0,
        'content' => 'RAM Mounts rugged mounting systems for mobile teams.',
        'content_plain' => 'RAM Mounts rugged mounting systems for mobile teams.',
        'metadata' => [
            'title' => 'RAM Mounts',
            'url' => 'https://example.test/vendors/ram-mounts',
            'driver' => 'statamic',
            'type' => 'taxonomy',
            'handle' => 'vendors',
            'slug' => 'ram-mounts',
        ],
    ]);

    $this->postJson('/aesircloud/statamic-ai-chatbot/chat', [
        'profile' => 'default',
        'site' => 'default',
        'message' => 'Do you work with RAM?',
    ])
        ->assertOk()
        ->assertJsonPath('intent', 'knowledge')
        ->assertJsonPath('message', 'Matched: RAM Mounts');
});

it('normalizes assistant action payloads into clickable widget actions', function () {
    $this->withoutMiddleware();

    app()->instance(SupportAssistant::class, new class(app(ProviderManager::class)) extends SupportAssistant
    {
        public function respond(BotProfile $profile, string $message, \Illuminate\Support\Collection $chunks): array
        {
            return [
                'message' => 'Here are some options.',
                'intent' => 'knowledge',
                'confidence' => 92,
                'citations' => [],
                'next_actions' => [
                    [
                        'label' => 'View RAM page',
                        'type' => 'url',
                        'value' => 'https://example.test/ram',
                    ],
                    [
                        'label' => 'Contact us about RAM',
                        'type' => 'form',
                        'value' => 'ram_mounts_contact_us',
                    ],
                    [
                        'label' => 'Clarify your request',
                        'type' => 'ask',
                        'payload' => 'Tell me which RAM Mounts product you need help with.',
                    ],
                ],
                'lead_capture_fields' => [],
                'status' => 'ok',
                'error_code' => null,
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
        'support_settings' => [],
        'lead_settings' => [],
    ]);

    $this->postJson('/aesircloud/statamic-ai-chatbot/chat', [
        'profile' => 'default',
        'message' => 'Do you work with RAM?',
    ])
        ->assertOk()
        ->assertJsonPath('next_actions.0.type', 'link')
        ->assertJsonPath('next_actions.0.url', 'https://example.test/ram')
        ->assertJsonPath('next_actions.1.type', 'lead_capture')
        ->assertJsonPath('next_actions.1.form_id', 'ram_mounts_contact_us')
        ->assertJsonPath('next_actions.2.type', 'prompt')
        ->assertJsonPath('next_actions.2.value', 'Tell me which RAM Mounts product you need help with.');
});
