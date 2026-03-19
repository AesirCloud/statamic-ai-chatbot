<?php

namespace AesirCloud\StatamicAiChatbot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = [
        'chat_conversation_id',
        'role',
        'content',
        'structured_output',
        'citations',
        'metadata',
    ];

    protected $casts = [
        'structured_output' => 'array',
        'citations' => 'array',
        'metadata' => 'array',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ChatConversation::class, 'chat_conversation_id');
    }
}
