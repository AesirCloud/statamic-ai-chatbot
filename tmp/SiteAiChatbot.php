<?php

namespace App\Tags;

use AesirCloud\StatamicAiChatbot\Tags\ChatbotWidgetTag;
use Throwable;

class SiteAiChatbot extends ChatbotWidgetTag
{
    protected static $handle = 'site_ai_chatbot';

    public function widget(): string
    {
        try {
            return parent::widget();
        } catch (Throwable $exception) {
            report($exception);

            return '';
        }
    }
}
