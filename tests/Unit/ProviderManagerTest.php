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
