<?php

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Support\Chat\SupportAssistant;
use AesirCloud\StatamicAiChatbot\Support\Config\ProviderManager;
use Laravel\Ai\Exceptions\RateLimitedException;

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

it('retries the next configured provider when a retryable ai error occurs', function () {
    config([
        'statamic-ai-chatbot.providers.text.driver' => 'openai',
        'statamic-ai-chatbot.providers.text.model' => 'gpt-5-mini',
        'statamic-ai-chatbot.providers.text_fallbacks' => [
            ['driver' => 'anthropic', 'model' => 'claude-sonnet-4-5', 'enabled' => true],
        ],
    ]);

    $assistant = new class(app(ProviderManager::class)) extends SupportAssistant
    {
        public array $attempts = [];

        protected function generateResponse(BotProfile $profile, string $prompt, array $candidate): mixed
        {
            $this->attempts[] = $candidate['driver'].':'.($candidate['model'] ?? '');

            if ($candidate['driver'] === 'openai') {
                throw RateLimitedException::forProvider('openai');
            }

            return [
                'message' => 'Fallback answer',
                'intent' => 'support',
                'confidence' => 82,
                'citations_json' => '[]',
                'next_actions_json' => '[]',
                'lead_capture_fields_json' => '[]',
            ];
        }
    };

    $response = $assistant->respond(new BotProfile([
        'handle' => 'default',
        'name' => 'Default Bot',
        'branding' => [],
        'provider_overrides' => [],
    ]), 'Where can I learn more?', collect());

    expect($assistant->attempts)->toBe([
        'openai:gpt-5-mini',
        'anthropic:claude-sonnet-4-5',
    ])->and($response)->toMatchArray([
        'message' => 'Fallback answer',
        'status' => 'ok',
        'error_code' => null,
    ]);
});

it('returns a degraded unavailable response when every provider attempt is retryable', function () {
    config([
        'statamic-ai-chatbot.providers.text.driver' => 'openai',
        'statamic-ai-chatbot.providers.text.model' => 'gpt-5-mini',
        'statamic-ai-chatbot.providers.text_fallbacks' => [
            ['driver' => 'anthropic', 'model' => 'claude-sonnet-4-5', 'enabled' => true],
        ],
    ]);

    $assistant = new class(app(ProviderManager::class)) extends SupportAssistant
    {
        public int $attempts = 0;

        protected function generateResponse(BotProfile $profile, string $prompt, array $candidate): mixed
        {
            $this->attempts++;

            throw RateLimitedException::forProvider($candidate['driver']);
        }
    };

    $response = $assistant->respond(new BotProfile([
        'handle' => 'default',
        'name' => 'Default Bot',
        'branding' => [],
        'provider_overrides' => [],
    ]), 'Tell me more', collect());

    expect($assistant->attempts)->toBe(2)
        ->and($response)->toMatchArray([
            'status' => 'degraded',
            'error_code' => 'ai_provider_unavailable',
        ])
        ->and($response['message'])->toContain('trouble reaching the AI assistant');
});

it('returns a degraded misconfiguration response without continuing to fallback providers on non retryable errors', function () {
    config([
        'statamic-ai-chatbot.providers.text.driver' => 'openai',
        'statamic-ai-chatbot.providers.text.model' => 'gpt-5-mini',
        'statamic-ai-chatbot.providers.text_fallbacks' => [
            ['driver' => 'anthropic', 'model' => 'claude-sonnet-4-5', 'enabled' => true],
        ],
    ]);

    $assistant = new class(app(ProviderManager::class)) extends SupportAssistant
    {
        public int $attempts = 0;

        protected function generateResponse(BotProfile $profile, string $prompt, array $candidate): mixed
        {
            $this->attempts++;

            throw new InvalidArgumentException('Unsupported model configuration.');
        }
    };

    $response = $assistant->respond(new BotProfile([
        'handle' => 'default',
        'name' => 'Default Bot',
        'branding' => [],
        'provider_overrides' => [],
    ]), 'Tell me more', collect());

    expect($assistant->attempts)->toBe(1)
        ->and($response)->toMatchArray([
            'status' => 'degraded',
            'error_code' => 'ai_provider_misconfigured',
        ])
        ->and($response['message'])->toContain('trouble with the AI setup');
});
