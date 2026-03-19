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
