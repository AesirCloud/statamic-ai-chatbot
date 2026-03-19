<?php

namespace AesirCloud\StatamicAiChatbot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourceConnection extends Model
{
    protected $fillable = [
        'bot_profile_id',
        'driver',
        'name',
        'config',
        'status',
        'last_synced_at',
        'last_error',
        'active',
    ];

    protected $casts = [
        'config' => 'array',
        'active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function botProfile(): BelongsTo
    {
        return $this->belongsTo(BotProfile::class);
    }

    public function knowledgeDocuments(): HasMany
    {
        return $this->hasMany(KnowledgeDocument::class);
    }
}
