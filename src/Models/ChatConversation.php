<?php

namespace AesirCloud\StatamicAiChatbot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatConversation extends Model
{
    protected $fillable = [
        'bot_profile_id',
        'site',
        'locale',
        'session_id',
        'visitor_name',
        'visitor_email',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function botProfile(): BelongsTo
    {
        return $this->belongsTo(BotProfile::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }
}
