<?php

use AesirCloud\StatamicAiChatbot\Contracts\KnowledgeSourceDriver;
use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Models\SourceConnection;
use AesirCloud\StatamicAiChatbot\Support\Sources\DriverManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('syncs knowledge from the cp even when embeddings credentials are not configured', function () {
    $this->withoutMiddleware();

    config([
        'ai.providers.openai.key' => null,
        'statamic-ai-chatbot.providers.embeddings.enabled' => true,
        'statamic-ai-chatbot.providers.embeddings.driver' => 'openai',
        'statamic-ai-chatbot.providers.embeddings.model' => 'text-embedding-3-small',
    ]);

    app()->instance(DriverManager::class, new DriverManager([
        new class implements KnowledgeSourceDriver
        {
            public function key(): string
            {
                return 'fake';
            }

            public function label(): string
            {
                return 'Fake';
            }

            public function sync(SourceConnection $source, BotProfile $profile): iterable
            {
                return [[
                    'external_id' => 'fake:1',
                    'site' => 'default',
                    'locale' => 'en',
                    'title' => 'Fake doc',
                    'excerpt' => 'Excerpt',
                    'url' => '/fake-doc',
                    'content' => 'This is fake synced content for testing.',
                    'metadata' => ['type' => 'fake'],
                ]];
            }
        },
    ]));

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
        'driver' => 'fake',
        'name' => 'Fake source',
        'config' => [],
        'active' => true,
        'status' => 'pending',
    ]);

    $this->postJson('/cp/aesircloud/statamic-ai-chatbot/sync', [
        'profile' => 'default',
    ])->assertOk();

    expect($source->fresh())
        ->status->toBe('ready')
        ->last_error->toBeNull()
        ->knowledgeDocuments()->count()->toBe(1);
});
