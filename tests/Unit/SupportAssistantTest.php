<?php

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Support\Chat\SupportAssistant;
use AesirCloud\StatamicAiChatbot\Support\Config\ProviderManager;

it('builds instructions without requiring a branding voice key', function () {
    $assistant = new SupportAssistant(app(ProviderManager::class));
    $profile = new BotProfile([
        'handle' => 'default',
        'name' => 'Default Bot',
        'branding' => [],
        'system_prompt' => 'Be helpful.',
    ]);

    $reflection = new ReflectionMethod($assistant, 'instructions');
    $reflection->setAccessible(true);

    $instructions = $reflection->invoke($assistant, $profile);

    expect($instructions)
        ->toContain('Be helpful.')
        ->not->toContain('Brand voice:');
});
