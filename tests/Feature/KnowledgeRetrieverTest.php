<?php

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Models\KnowledgeChunk;
use AesirCloud\StatamicAiChatbot\Models\KnowledgeDocument;
use AesirCloud\StatamicAiChatbot\Models\SourceConnection;
use AesirCloud\StatamicAiChatbot\Support\Knowledge\KnowledgeRetriever;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prefers whole-word ram matches over substring matches', function () {
    $profile = BotProfile::query()->create([
        'handle' => 'default',
        'name' => 'Default Bot',
        'site' => 'default',
        'locale' => 'default',
        'active' => true,
        'is_default' => true,
    ]);

    $source = SourceConnection::query()->create([
        'bot_profile_id' => $profile->id,
        'name' => 'Site content',
        'driver' => 'statamic',
        'active' => true,
        'status' => 'ready',
    ]);

    $programDocument = KnowledgeDocument::query()->create([
        'bot_profile_id' => $profile->id,
        'source_connection_id' => $source->id,
        'driver' => 'statamic',
        'external_id' => 'entry:program',
        'site' => 'default',
        'locale' => 'default',
        'title' => 'Program Terms',
        'url' => 'https://example.test/program-terms',
        'metadata' => ['type' => 'entry', 'slug' => 'program-terms'],
        'content_hash' => sha1('program terms'),
    ]);

    KnowledgeChunk::query()->create([
        'knowledge_document_id' => $programDocument->id,
        'bot_profile_id' => $profile->id,
        'site' => 'default',
        'locale' => 'default',
        'position' => 0,
        'content' => 'This program helps partners with onboarding.',
        'content_plain' => 'This program helps partners with onboarding.',
        'metadata' => [
            'title' => 'Program Terms',
            'url' => 'https://example.test/program-terms',
            'driver' => 'statamic',
            'type' => 'entry',
            'slug' => 'program-terms',
        ],
    ]);

    $ramDocument = KnowledgeDocument::query()->create([
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
        'knowledge_document_id' => $ramDocument->id,
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

    config()->set('statamic-ai-chatbot.providers.embeddings.enabled', false);

    $results = app(KnowledgeRetriever::class)->search($profile, 'Do you work with RAM?', 'default', 'default');

    expect($results)->not->toBeEmpty()
        ->and($results->first()->metadata['title'])->toBe('RAM Mounts')
        ->and($results->pluck('metadata.title')->take(2)->all())->not->toContain('Program Terms');
});

it('boosts vendor taxonomy chunks for vendor questions', function () {
    $profile = BotProfile::query()->create([
        'handle' => 'default',
        'name' => 'Default Bot',
        'site' => 'default',
        'locale' => 'default',
        'active' => true,
        'is_default' => true,
    ]);

    $source = SourceConnection::query()->create([
        'bot_profile_id' => $profile->id,
        'name' => 'Site content',
        'driver' => 'statamic',
        'active' => true,
        'status' => 'ready',
    ]);

    $aboutDocument = KnowledgeDocument::query()->create([
        'bot_profile_id' => $profile->id,
        'source_connection_id' => $source->id,
        'driver' => 'statamic',
        'external_id' => 'entry:about-us',
        'site' => 'default',
        'locale' => 'default',
        'title' => 'About us',
        'url' => 'https://example.test/about-us',
        'metadata' => ['type' => 'entry', 'slug' => 'about-us'],
        'content_hash' => sha1('about us'),
    ]);

    KnowledgeChunk::query()->create([
        'knowledge_document_id' => $aboutDocument->id,
        'bot_profile_id' => $profile->id,
        'site' => 'default',
        'locale' => 'default',
        'position' => 0,
        'content' => '3Eye works with mobile teams across logistics and field services.',
        'content_plain' => '3Eye works with mobile teams across logistics and field services.',
        'metadata' => [
            'title' => 'About us',
            'url' => 'https://example.test/about-us',
            'driver' => 'statamic',
            'type' => 'entry',
            'slug' => 'about-us',
        ],
    ]);

    $vendorDocument = KnowledgeDocument::query()->create([
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
        'knowledge_document_id' => $vendorDocument->id,
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

    config()->set('statamic-ai-chatbot.providers.embeddings.enabled', false);

    $results = app(KnowledgeRetriever::class)->search($profile, 'Can you tell me what vendors 3eye works with?', 'default', 'default');

    expect($results)->not->toBeEmpty()
        ->and($results->first()->metadata['handle'])->toBe('vendors')
        ->and($results->first()->metadata['title'])->toBe('RAM Mounts');
});

it('treats provider and reseller wording like vendor intent', function () {
    $profile = BotProfile::query()->create([
        'handle' => 'default',
        'name' => 'Default Bot',
        'site' => 'default',
        'locale' => 'default',
        'active' => true,
        'is_default' => true,
    ]);

    $source = SourceConnection::query()->create([
        'bot_profile_id' => $profile->id,
        'name' => 'Site content',
        'driver' => 'statamic',
        'active' => true,
        'status' => 'ready',
    ]);

    $aboutDocument = KnowledgeDocument::query()->create([
        'bot_profile_id' => $profile->id,
        'source_connection_id' => $source->id,
        'driver' => 'statamic',
        'external_id' => 'entry:about-us',
        'site' => 'default',
        'locale' => 'default',
        'title' => 'About us',
        'url' => 'https://example.test/about-us',
        'metadata' => ['type' => 'entry', 'slug' => 'about-us'],
        'content_hash' => sha1('about us'),
    ]);

    KnowledgeChunk::query()->create([
        'knowledge_document_id' => $aboutDocument->id,
        'bot_profile_id' => $profile->id,
        'site' => 'default',
        'locale' => 'default',
        'position' => 0,
        'content' => '3Eye is a solution provider for mobility teams.',
        'content_plain' => '3Eye is a solution provider for mobility teams.',
        'metadata' => [
            'title' => 'About us',
            'url' => 'https://example.test/about-us',
            'driver' => 'statamic',
            'type' => 'entry',
            'slug' => 'about-us',
        ],
    ]);

    $safeuemVendorDocument = KnowledgeDocument::query()->create([
        'bot_profile_id' => $profile->id,
        'source_connection_id' => $source->id,
        'driver' => 'statamic',
        'external_id' => 'taxonomy:vendors:safeuem:default',
        'site' => 'default',
        'locale' => 'default',
        'title' => 'SafeUEM',
        'url' => 'https://example.test/vendors/safeuem',
        'metadata' => ['type' => 'taxonomy', 'handle' => 'vendors', 'slug' => 'safeuem'],
        'content_hash' => sha1('safeuem vendor'),
    ]);

    KnowledgeChunk::query()->create([
        'knowledge_document_id' => $safeuemVendorDocument->id,
        'bot_profile_id' => $profile->id,
        'site' => 'default',
        'locale' => 'default',
        'position' => 0,
        'content' => '{"title":"SafeUEM","category":["mdm"]}',
        'content_plain' => 'SafeUEM mdm vendor taxonomy.',
        'metadata' => [
            'title' => 'SafeUEM',
            'url' => 'https://example.test/vendors/safeuem',
            'driver' => 'statamic',
            'type' => 'taxonomy',
            'handle' => 'vendors',
            'slug' => 'safeuem',
        ],
    ]);

    $fleetBrandDocument = KnowledgeDocument::query()->create([
        'bot_profile_id' => $profile->id,
        'source_connection_id' => $source->id,
        'driver' => 'statamic',
        'external_id' => 'entry:fleet-device-management-brand',
        'site' => 'default',
        'locale' => 'default',
        'title' => 'Fleet Device Management',
        'url' => 'https://example.test/fleet-device-management-brand',
        'metadata' => ['type' => 'entry', 'slug' => 'fleet-device-management-brand'],
        'content_hash' => sha1('fleet brand'),
    ]);

    KnowledgeChunk::query()->create([
        'knowledge_document_id' => $fleetBrandDocument->id,
        'bot_profile_id' => $profile->id,
        'site' => 'default',
        'locale' => 'default',
        'position' => 0,
        'content' => 'Fleet Device Management is an MDM platform that we resell.',
        'content_plain' => 'Fleet Device Management is an MDM platform that we resell.',
        'metadata' => [
            'title' => 'Fleet Device Management',
            'url' => 'https://example.test/fleet-device-management-brand',
            'driver' => 'statamic',
            'type' => 'entry',
            'slug' => 'fleet-device-management-brand',
        ],
    ]);

    config()->set('statamic-ai-chatbot.providers.embeddings.enabled', false);

    $results = app(KnowledgeRetriever::class)->search($profile, 'What MDM providers do you resell?', 'default', 'default');

    expect($results)->not->toBeEmpty()
        ->and($results->pluck('metadata.title')->take(2)->all())->toContain('SafeUEM')
        ->and($results->pluck('metadata.title')->take(2)->all())->toContain('Fleet Device Management');
});
