<?php

namespace AesirCloud\StatamicAiChatbot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaqItem extends Model
{
    protected $fillable = [
        'bot_profile_id',
        'site',
        'locale',
        'question',
        'question_variants',
        'answer',
        'priority',
        'cta_actions',
        'lead_capture_fields',
        'active',
    ];

    protected $casts = [
        'question_variants' => 'array',
        'cta_actions' => 'array',
        'lead_capture_fields' => 'array',
        'active' => 'boolean',
    ];

    public function botProfile(): BelongsTo
    {
        return $this->belongsTo(BotProfile::class);
    }
}
