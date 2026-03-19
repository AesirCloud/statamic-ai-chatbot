<?php

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Support\Profiles\BotProfileResolver;
use AesirCloud\StatamicAiChatbot\Tags\ChatbotWidgetTag;

it('renders the widget config with a csrf token for public actions', function () {
    $profile = new BotProfile([
        'handle' => 'default',
        'name' => 'Default Bot',
        'widget_settings' => [
            'eyebrow_label' => 'Peak Support AI',
        ],
    ]);

    $resolver = new class($profile)
    {
        public function __construct(protected BotProfile $profile)
        {
        }

        public function resolve(...$arguments): BotProfile
        {
            return $this->profile;
        }
    };

    $this->app->instance(BotProfileResolver::class, $resolver);
    $this->app['view']->addNamespace('aesircloud-statamic-ai-chatbot', dirname(__DIR__, 2).'/resources/views');

    $tag = new ChatbotWidgetTag();
    $tag->setContent('');
    $tag->setContext([]);
    $tag->setParameters([]);

    $output = $tag->widget();

    expect($output)
        ->toContain('data-aesircloud-statamic-ai-chatbot')
        ->toContain('csrf_token')
        ->toContain(csrf_token())
        ->toContain('Peak Support AI');
});

it('keeps global widget settings when profile overrides are null', function () {
    config()->set('statamic-ai-chatbot.widget', [
        'eyebrow_label' => '3EYE AI',
        'primary_color' => '#000000',
        'accent_color' => '#dc6a2d',
        'welcome_title' => 'How can we help?',
        'welcome_message' => 'Ask us anything.',
    ]);

    $profile = new BotProfile([
        'handle' => 'default',
        'name' => 'Default Bot',
        'widget_settings' => [
            'eyebrow_label' => null,
            'primary_color' => null,
            'launcher_label' => '',
        ],
    ]);

    $resolver = new class($profile)
    {
        public function __construct(protected BotProfile $profile)
        {
        }

        public function resolve(...$arguments): BotProfile
        {
            return $this->profile;
        }
    };

    $this->app->instance(BotProfileResolver::class, $resolver);
    $this->app['view']->addNamespace('aesircloud-statamic-ai-chatbot', dirname(__DIR__, 2).'/resources/views');

    $tag = new ChatbotWidgetTag();
    $tag->setContent('');
    $tag->setContext([]);
    $tag->setParameters([]);

    $output = $tag->widget();

    expect($output)
        ->toContain('3EYE AI')
        ->toContain('#000000');
});
