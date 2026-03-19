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

it('normalizes ai citation payloads into titled links before rendering them to the widget', function () {
    $this->withoutMiddleware();

    config()->set('statamic-ai-chatbot.providers.embeddings.enabled', false);

    app()->instance(SupportAssistant::class, new class(app(ProviderManager::class)) extends SupportAssistant
    {
        public function respond(BotProfile $profile, string $message, \Illuminate\Support\Collection $chunks): array
        {
            return [
                'message' => 'Here are our MDM vendors.',
                'intent' => 'knowledge',
                'confidence' => 91,
                'citations' => [
                    'https://example.test/vendors/safeuem',
                    ['source' => 2],
                    [],
                ],
                'next_actions' => [],
                'lead_capture_fields' => [],
                'status' => 'ok',
                'error_code' => null,
            ];
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

    $source = SourceConnection::query()->create([
        'bot_profile_id' => $profile->id,
        'name' => 'Vendors',
        'driver' => 'statamic',
        'active' => true,
        'status' => 'ready',
    ]);

    $safeuem = KnowledgeDocument::query()->create([
        'bot_profile_id' => $profile->id,
        'source_connection_id' => $source->id,
        'driver' => 'statamic',
        'external_id' => 'entry:safeuem',
        'site' => 'default',
        'locale' => 'default',
        'title' => 'SafeUEM',
        'url' => 'https://example.test/vendors/safeuem',
        'metadata' => ['type' => 'entry', 'slug' => 'safeuem'],
        'content_hash' => sha1('safeuem'),
    ]);

    $fleet = KnowledgeDocument::query()->create([
        'bot_profile_id' => $profile->id,
        'source_connection_id' => $source->id,
        'driver' => 'statamic',
        'external_id' => 'entry:fleet-device-management',
        'site' => 'default',
        'locale' => 'default',
        'title' => 'Fleet Device Management',
        'url' => 'https://example.test/vendors/fleet-device-management',
        'metadata' => ['type' => 'entry', 'slug' => 'fleet-device-management'],
        'content_hash' => sha1('fleet'),
    ]);

    KnowledgeChunk::query()->create([
        'knowledge_document_id' => $safeuem->id,
        'bot_profile_id' => $profile->id,
        'site' => 'default',
        'locale' => 'default',
        'position' => 0,
        'content' => 'SafeUEM is one of our MDM vendors.',
        'content_plain' => 'SafeUEM is one of our MDM vendors.',
        'metadata' => [
            'title' => 'SafeUEM',
            'url' => 'https://example.test/vendors/safeuem',
            'driver' => 'statamic',
            'type' => 'entry',
            'slug' => 'safeuem',
        ],
    ]);

    KnowledgeChunk::query()->create([
        'knowledge_document_id' => $fleet->id,
        'bot_profile_id' => $profile->id,
        'site' => 'default',
        'locale' => 'default',
        'position' => 0,
        'content' => 'Fleet Device Management is one of our MDM vendors.',
        'content_plain' => 'Fleet Device Management is one of our MDM vendors.',
        'metadata' => [
            'title' => 'Fleet Device Management',
            'url' => 'https://example.test/vendors/fleet-device-management',
            'driver' => 'statamic',
            'type' => 'entry',
            'slug' => 'fleet-device-management',
        ],
    ]);

    $this->postJson('/aesircloud/statamic-ai-chatbot/chat', [
        'profile' => 'default',
        'site' => 'default',
        'message' => 'Do you have any MDM vendors?',
    ])
        ->assertOk()
        ->assertJsonPath('citations.0.title', 'SafeUEM')
        ->assertJsonPath('citations.0.url', 'https://example.test/vendors/safeuem')
        ->assertJsonPath('citations.1.title', 'Fleet Device Management')
        ->assertJsonPath('citations.1.url', 'https://example.test/vendors/fleet-device-management');
});

it('prefers brand landing page urls over taxonomy term urls in citations when both are indexed', function () {
    $this->withoutMiddleware();

    config()->set('statamic-ai-chatbot.providers.embeddings.enabled', false);

    app()->instance(SupportAssistant::class, new class(app(ProviderManager::class)) extends SupportAssistant
    {
        public function respond(BotProfile $profile, string $message, \Illuminate\Support\Collection $chunks): array
        {
            return [
                'message' => 'Here are our MDM vendors.',
                'intent' => 'knowledge',
                'confidence' => 90,
                'citations' => [
                    'https://example.test/vendors/safeuem',
                    'https://example.test/vendors/fleet-device-management',
                ],
                'next_actions' => [],
                'lead_capture_fields' => [],
                'status' => 'ok',
                'error_code' => null,
            ];
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

    $source = SourceConnection::query()->create([
        'bot_profile_id' => $profile->id,
        'name' => 'Vendors',
        'driver' => 'statamic',
        'active' => true,
        'status' => 'ready',
    ]);

    $safeuemPage = KnowledgeDocument::query()->create([
        'bot_profile_id' => $profile->id,
        'source_connection_id' => $source->id,
        'driver' => 'statamic',
        'external_id' => 'entry:safeuem-brand',
        'site' => 'default',
        'locale' => 'default',
        'title' => 'SafeUEM',
        'url' => 'https://example.test/safeuem-brand',
        'metadata' => ['type' => 'entry', 'collection' => 'pages', 'slug' => 'safeuem-brand'],
        'content_hash' => sha1('safeuem-brand'),
    ]);

    $fleetPage = KnowledgeDocument::query()->create([
        'bot_profile_id' => $profile->id,
        'source_connection_id' => $source->id,
        'driver' => 'statamic',
        'external_id' => 'entry:fleet-device-management-brand',
        'site' => 'default',
        'locale' => 'default',
        'title' => 'Fleet Device Management',
        'url' => 'https://example.test/fleet-device-management-brand',
        'metadata' => ['type' => 'entry', 'collection' => 'pages', 'slug' => 'fleet-device-management-brand'],
        'content_hash' => sha1('fleet-device-management-brand'),
    ]);

    $safeuemTerm = KnowledgeDocument::query()->create([
        'bot_profile_id' => $profile->id,
        'source_connection_id' => $source->id,
        'driver' => 'statamic',
        'external_id' => 'taxonomy:vendors:safeuem',
        'site' => 'default',
        'locale' => 'default',
        'title' => 'SafeUEM',
        'url' => 'https://example.test/vendors/safeuem',
        'metadata' => ['type' => 'taxonomy', 'handle' => 'vendors', 'slug' => 'safeuem'],
        'content_hash' => sha1('safeuem-term'),
    ]);

    $fleetTerm = KnowledgeDocument::query()->create([
        'bot_profile_id' => $profile->id,
        'source_connection_id' => $source->id,
        'driver' => 'statamic',
        'external_id' => 'taxonomy:vendors:fleet-device-management',
        'site' => 'default',
        'locale' => 'default',
        'title' => 'Fleet Device Management',
        'url' => 'https://example.test/vendors/fleet-device-management',
        'metadata' => ['type' => 'taxonomy', 'handle' => 'vendors', 'slug' => 'fleet-device-management'],
        'content_hash' => sha1('fleet-term'),
    ]);

    KnowledgeChunk::query()->create([
        'knowledge_document_id' => $safeuemPage->id,
        'bot_profile_id' => $profile->id,
        'site' => 'default',
        'locale' => 'default',
        'position' => 0,
        'content' => 'SafeUEM landing page.',
        'content_plain' => 'SafeUEM landing page.',
        'metadata' => [
            'title' => 'SafeUEM',
            'url' => 'https://example.test/safeuem-brand',
            'driver' => 'statamic',
            'type' => 'entry',
            'slug' => 'safeuem-brand',
        ],
    ]);

    KnowledgeChunk::query()->create([
        'knowledge_document_id' => $fleetPage->id,
        'bot_profile_id' => $profile->id,
        'site' => 'default',
        'locale' => 'default',
        'position' => 0,
        'content' => 'Fleet landing page.',
        'content_plain' => 'Fleet landing page.',
        'metadata' => [
            'title' => 'Fleet Device Management',
            'url' => 'https://example.test/fleet-device-management-brand',
            'driver' => 'statamic',
            'type' => 'entry',
            'slug' => 'fleet-device-management-brand',
        ],
    ]);

    KnowledgeChunk::query()->create([
        'knowledge_document_id' => $safeuemTerm->id,
        'bot_profile_id' => $profile->id,
        'site' => 'default',
        'locale' => 'default',
        'position' => 0,
        'content' => 'SafeUEM is an MDM vendor.',
        'content_plain' => 'SafeUEM is an MDM vendor.',
        'metadata' => [
            'title' => 'SafeUEM',
            'url' => 'https://example.test/vendors/safeuem',
            'driver' => 'statamic',
            'type' => 'taxonomy',
            'handle' => 'vendors',
            'slug' => 'safeuem',
        ],
    ]);

    KnowledgeChunk::query()->create([
        'knowledge_document_id' => $fleetTerm->id,
        'bot_profile_id' => $profile->id,
        'site' => 'default',
        'locale' => 'default',
        'position' => 0,
        'content' => 'Fleet Device Management is an MDM vendor.',
        'content_plain' => 'Fleet Device Management is an MDM vendor.',
        'metadata' => [
            'title' => 'Fleet Device Management',
            'url' => 'https://example.test/vendors/fleet-device-management',
            'driver' => 'statamic',
            'type' => 'taxonomy',
            'handle' => 'vendors',
            'slug' => 'fleet-device-management',
        ],
    ]);

    $this->postJson('/aesircloud/statamic-ai-chatbot/chat', [
        'profile' => 'default',
        'site' => 'default',
        'message' => 'Do you have any MDM vendors?',
    ])
        ->assertOk()
        ->assertJsonPath('citations.0.title', 'SafeUEM')
        ->assertJsonPath('citations.0.url', 'https://example.test/safeuem-brand')
        ->assertJsonPath('citations.1.title', 'Fleet Device Management')
        ->assertJsonPath('citations.1.url', 'https://example.test/fleet-device-management-brand');
});

it('preserves related supporting citations from retrieved chunks when the assistant only returns one', function () {
    $this->withoutMiddleware();

    config()->set('statamic-ai-chatbot.providers.embeddings.enabled', false);

    app()->instance(SupportAssistant::class, new class(app(ProviderManager::class)) extends SupportAssistant
    {
        public function respond(BotProfile $profile, string $message, \Illuminate\Support\Collection $chunks): array
        {
            return [
                'message' => 'Yes, we work with RAM Mounts.',
                'intent' => 'knowledge',
                'confidence' => 93,
                'citations' => [
                    ['title' => 'RAM Mounts', 'url' => 'https://example.test/ram-mounts-brand'],
                ],
                'next_actions' => [],
                'lead_capture_fields' => [],
                'status' => 'ok',
                'error_code' => null,
            ];
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

    $source = SourceConnection::query()->create([
        'bot_profile_id' => $profile->id,
        'name' => 'RAM content',
        'driver' => 'statamic',
        'active' => true,
        'status' => 'ready',
    ]);

    $documents = [
        ['title' => 'RAM Mounts', 'url' => 'https://example.test/ram-mounts-brand', 'slug' => 'ram-mounts-brand'],
        ['title' => 'RAM Mounts Laptop Replacement with Kyle Lonzak', 'url' => 'https://example.test/beyond-the-device/ram-mounts-laptop-replacement-with-kyle-lonzak', 'slug' => 'ram-mounts-laptop-replacement-with-kyle-lonzak'],
        ['title' => 'RAM Mounts and AINA Push to Talk', 'url' => 'https://example.test/beyond-the-device/ram-mounts-and-aina-push-to-talk', 'slug' => 'ram-mounts-and-aina-push-to-talk'],
    ];

    foreach ($documents as $index => $documentData) {
        $document = KnowledgeDocument::query()->create([
            'bot_profile_id' => $profile->id,
            'source_connection_id' => $source->id,
            'driver' => 'statamic',
            'external_id' => 'entry:'.$documentData['slug'],
            'site' => 'default',
            'locale' => 'default',
            'title' => $documentData['title'],
            'url' => $documentData['url'],
            'metadata' => ['type' => 'entry', 'slug' => $documentData['slug']],
            'content_hash' => sha1($documentData['slug']),
        ]);

        KnowledgeChunk::query()->create([
            'knowledge_document_id' => $document->id,
            'bot_profile_id' => $profile->id,
            'site' => 'default',
            'locale' => 'default',
            'position' => 0,
            'content' => $documentData['title'].' RAM related resource.',
            'content_plain' => $documentData['title'].' RAM related resource.',
            'metadata' => [
                'title' => $documentData['title'],
                'url' => $documentData['url'],
                'driver' => 'statamic',
                'type' => 'entry',
                'slug' => $documentData['slug'],
            ],
        ]);
    }

    $this->postJson('/aesircloud/statamic-ai-chatbot/chat', [
        'profile' => 'default',
        'site' => 'default',
        'message' => 'Do you work with RAM?',
    ])
        ->assertOk()
        ->assertJsonCount(3, 'citations')
        ->assertJsonPath('citations.0.title', 'RAM Mounts')
        ->assertJsonPath('citations.1.title', 'RAM Mounts Laptop Replacement with Kyle Lonzak')
        ->assertJsonPath('citations.2.title', 'RAM Mounts and AINA Push to Talk');
});
