<?php

use AesirCloud\StatamicAiChatbot\Contracts\SupportHandoffResolver;
use AesirCloud\StatamicAiChatbot\Support\Chat\ChatService;
use AesirCloud\StatamicAiChatbot\Support\Config\ProviderManager;
use AesirCloud\StatamicAiChatbot\Support\Knowledge\KnowledgeRetriever;
use AesirCloud\StatamicAiChatbot\Support\Knowledge\KnowledgeSyncService;

it('registers core services in the container', function () {
    expect(app(ProviderManager::class))->toBeInstanceOf(ProviderManager::class)
        ->and(app(KnowledgeRetriever::class))->toBeInstanceOf(KnowledgeRetriever::class)
        ->and(app(KnowledgeSyncService::class))->toBeInstanceOf(KnowledgeSyncService::class)
        ->and(app(ChatService::class))->toBeInstanceOf(ChatService::class)
        ->and(app(SupportHandoffResolver::class))->toBeInstanceOf(SupportHandoffResolver::class);
});
