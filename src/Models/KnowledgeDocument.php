<?php

namespace AesirCloud\StatamicAiChatbot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeDocument extends Model
{
    protected $fillable = [
        'bot_profile_id',
        'source_connection_id',
        'driver',
        'external_id',
        'site',
        'locale',
        'title',
        'excerpt',
        'url',
        'metadata',
        'content_hash',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function botProfile(): BelongsTo
    {
        return $this->belongsTo(BotProfile::class);
    }

    public function sourceConnection(): BelongsTo
    {
        return $this->belongsTo(SourceConnection::class);
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class);
    }
}
