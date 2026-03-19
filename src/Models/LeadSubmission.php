<?php

namespace AesirCloud\StatamicAiChatbot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadSubmission extends Model
{
    protected $fillable = [
        'bot_profile_id',
        'chat_conversation_id',
        'site',
        'locale',
        'name',
        'email',
        'phone',
        'message',
        'status',
        'payload',
        'delivery_log',
    ];

    protected $casts = [
        'payload' => 'array',
        'delivery_log' => 'array',
    ];

    public function botProfile(): BelongsTo
    {
        return $this->belongsTo(BotProfile::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }
}
