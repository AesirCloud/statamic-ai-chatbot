<?php

namespace AesirCloud\StatamicAiChatbot\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotSetting extends Model
{
    protected $table = 'statamic_ai_chatbot_settings';

    protected $fillable = [
        'key',
        'payload',
    ];

    protected $casts = [
        'payload' => 'encrypted:array',
    ];
}
