<?php

namespace AesirCloud\StatamicAiChatbot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeChunk extends Model
{
    protected $fillable = [
        'knowledge_document_id',
        'bot_profile_id',
        'site',
        'locale',
        'position',
        'content',
        'content_plain',
        'embedding',
        'metadata',
        'score',
    ];

    protected $casts = [
        'embedding' => 'array',
        'metadata' => 'array',
        'score' => 'float',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'knowledge_document_id');
    }

    public function botProfile(): BelongsTo
    {
        return $this->belongsTo(BotProfile::class);
    }
}
