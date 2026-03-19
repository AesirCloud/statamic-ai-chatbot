<?php

namespace AesirCloud\StatamicAiChatbot\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BotProfile extends Model
{
    protected $fillable = [
        'handle',
        'name',
        'site',
        'locale',
        'is_default',
        'active',
        'branding',
        'provider_overrides',
        'widget_settings',
        'support_settings',
        'lead_settings',
        'system_prompt',
    ];

    protected $casts = [
        'active' => 'boolean',
        'is_default' => 'boolean',
        'branding' => 'array',
        'provider_overrides' => 'array',
        'widget_settings' => 'array',
        'support_settings' => 'array',
        'lead_settings' => 'array',
    ];

    public function faqItems(): HasMany
    {
        return $this->hasMany(FaqItem::class);
    }

    public function sourceConnections(): HasMany
    {
        return $this->hasMany(SourceConnection::class);
    }

    public function chatConversations(): HasMany
    {
        return $this->hasMany(ChatConversation::class);
    }

    public function leadSubmissions(): HasMany
    {
        return $this->hasMany(LeadSubmission::class);
    }
}
