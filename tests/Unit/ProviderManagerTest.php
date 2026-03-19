<?php

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Support\Config\ProviderManager;

it('builds ordered unique text provider candidates', function () {
    config([
        'statamic-ai-chatbot.providers.text.driver' => 'openai',
        'statamic-ai-chatbot.providers.text.model' => 'gpt-5-mini',
        'statamic-ai-chatbot.providers.text_fallbacks' => [
            ['driver' => 'openai', 'model' => 'gpt-5-mini', 'enabled' => true],
            ['driver' => 'anthropic', 'model' => 'claude-sonnet-4-5', 'enabled' => true],
            ['driver' => 'anthropic', 'model' => 'claude-sonnet-4-5', 'enabled' => true],
            ['driver' => '', 'model' => 'ignored', 'enabled' => true],
            ['driver' => 'gemini', 'model' => 'gemini-2.5-pro', 'enabled' => false],
        ],
    ]);

    $manager = app(ProviderManager::class);

    expect($manager->forTextCandidates(new BotProfile([
        'provider_overrides' => [],
    ])))->toBe([
        ['driver' => 'openai', 'model' => 'gpt-5-mini'],
        ['driver' => 'anthropic', 'model' => 'claude-sonnet-4-5'],
    ]);
});

it('normalizes known provider model identifiers to lowercase', function () {
    config([
        'statamic-ai-chatbot.providers.text.driver' => 'openai',
        'statamic-ai-chatbot.providers.text.model' => 'GPT-5.4-NANO',
        'statamic-ai-chatbot.providers.embeddings.driver' => 'openai',
        'statamic-ai-chatbot.providers.embeddings.model' => 'TEXT-EMBEDDING-3-SMALL',
    ]);

    $manager = app(ProviderManager::class);

    expect($manager->forText())->toBe([
        'driver' => 'openai',
        'model' => 'gpt-5.4-nano',
    ])->and($manager->forEmbeddings())->toMatchArray([
        'driver' => 'openai',
        'model' => 'text-embedding-3-small',
    ]);
});
