<?php

namespace AesirCloud\StatamicAiChatbot\Tags;

use AesirCloud\StatamicAiChatbot\Support\Profiles\BotProfileResolver;
use Illuminate\Support\Arr;
use Statamic\Tags\Tags;

class ChatbotWidgetTag extends Tags
{
    protected static $handle = 'ai_chatbot';

    public function widget(): string
    {
        if (! config('statamic-ai-chatbot.enabled', true)) {
            return '';
        }

        $profile = app(BotProfileResolver::class)->resolve(
            handle: $this->params->get('profile', config('statamic-ai-chatbot.default_profile_handle')),
            site: $this->params->get('site'),
            locale: $this->params->get('locale'),
        );

        return (string) view('aesircloud-statamic-ai-chatbot::widget', [
            'profile' => $profile,
            'config' => array_merge(
                config('statamic-ai-chatbot.widget'),
                array_filter(Arr::get($profile->toArray(), 'widget_settings', []), fn ($value) => $value !== null),
                [
                    'profile' => $profile->handle,
                    'site' => $profile->site,
                    'locale' => $profile->locale,
                    'csrf_token' => csrf_token(),
                    'chat_endpoint' => action(\AesirCloud\StatamicAiChatbot\Http\Controllers\Api\ChatController::class),
                    'lead_endpoint' => action(\AesirCloud\StatamicAiChatbot\Http\Controllers\Api\LeadController::class),
                ],
            ),
        ])->render();
    }
}
