<?php

namespace AesirCloud\StatamicAiChatbot\Support\Cp;

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Models\ChatConversation;
use AesirCloud\StatamicAiChatbot\Models\FaqItem;
use AesirCloud\StatamicAiChatbot\Models\LeadSubmission;
use AesirCloud\StatamicAiChatbot\Models\SourceConnection;
use AesirCloud\StatamicAiChatbot\Support\Config\SettingsRepository;
use AesirCloud\StatamicAiChatbot\Support\Sources\DriverManager;
use Illuminate\Http\Request;
use Statamic\Facades\Site;
use Throwable;

class DashboardData
{
    public function __construct(
        protected SettingsRepository $settingsRepository,
        protected DriverManager $driverManager,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'profiles' => BotProfile::query()
                ->withCount(['faqItems', 'sourceConnections', 'chatConversations', 'leadSubmissions'])
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->get()
                ->map(fn (BotProfile $profile) => $this->profile($profile))
                ->values()
                ->all(),
            'faqs' => FaqItem::query()
                ->with('botProfile:id,name,handle')
                ->orderByDesc('priority')
                ->orderBy('question')
                ->get()
                ->map(fn (FaqItem $faq) => $this->faq($faq))
                ->values()
                ->all(),
            'sources' => SourceConnection::query()
                ->with('botProfile:id,name,handle')
                ->withCount('knowledgeDocuments')
                ->latest()
                ->get()
                ->map(fn (SourceConnection $source) => $this->source($source))
                ->values()
                ->all(),
            'conversations' => ChatConversation::query()
                ->with([
                    'botProfile:id,name,handle',
                    'messages' => fn ($query) => $query->oldest(),
                ])
                ->withCount('messages')
                ->latest()
                ->limit(25)
                ->get()
                ->map(fn (ChatConversation $conversation) => $this->conversation($conversation))
                ->values()
                ->all(),
            'leads' => LeadSubmission::query()
                ->with('botProfile:id,name,handle')
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (LeadSubmission $lead) => $this->lead($lead))
                ->values()
                ->all(),
            'stats' => [
                'profiles' => BotProfile::count(),
                'faqs' => FaqItem::count(),
                'sources' => SourceConnection::count(),
                'conversations' => ChatConversation::count(),
                'leads' => LeadSubmission::count(),
            ],
            'drivers' => $this->driverManager->options(),
            'providerCatalog' => $this->settingsRepository->providerCatalog(),
            'providerOptions' => collect($this->settingsRepository->providerCatalog())
                ->map(fn (array $provider) => [
                    'key' => $provider['key'],
                    'label' => $provider['label'],
                ])
                ->values()
                ->all(),
            'sites' => $this->sites(),
            'settings' => $this->settingsRepository->all(),
            'options' => [
                'retentionModes' => [
                    ['value' => 'conversations_and_leads', 'label' => 'Conversations and leads'],
                    ['value' => 'leads_only', 'label' => 'Leads only'],
                ],
                'widgetPositions' => [
                    ['value' => 'bottom-right', 'label' => 'Bottom right'],
                    ['value' => 'bottom-left', 'label' => 'Bottom left'],
                    ['value' => 'top-right', 'label' => 'Top right'],
                    ['value' => 'top-left', 'label' => 'Top left'],
                ],
                'leadStatuses' => [
                    ['value' => 'new', 'label' => 'New'],
                    ['value' => 'contacted', 'label' => 'Contacted'],
                    ['value' => 'qualified', 'label' => 'Qualified'],
                    ['value' => 'converted', 'label' => 'Converted'],
                    ['value' => 'closed', 'label' => 'Closed'],
                ],
            ],
            'routes' => [
                'sync' => cp_route('aesircloud.statamic-ai-chatbot.sync'),
                'settingsSave' => cp_route('aesircloud.statamic-ai-chatbot.settings.save'),
                'profileSave' => cp_route('aesircloud.statamic-ai-chatbot.profiles.save'),
                'profileDelete' => cp_route('aesircloud.statamic-ai-chatbot.profiles.delete'),
                'faqSave' => cp_route('aesircloud.statamic-ai-chatbot.faqs.save'),
                'faqDelete' => cp_route('aesircloud.statamic-ai-chatbot.faqs.delete'),
                'sourceSave' => cp_route('aesircloud.statamic-ai-chatbot.sources.save'),
                'sourceDelete' => cp_route('aesircloud.statamic-ai-chatbot.sources.delete'),
                'sourceSync' => cp_route('aesircloud.statamic-ai-chatbot.sources.sync'),
                'conversationDelete' => cp_route('aesircloud.statamic-ai-chatbot.conversations.delete'),
                'leadSave' => cp_route('aesircloud.statamic-ai-chatbot.leads.save'),
                'leadDelete' => cp_route('aesircloud.statamic-ai-chatbot.leads.delete'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function profile(BotProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'handle' => $profile->handle,
            'name' => $profile->name,
            'site' => $profile->site,
            'locale' => $profile->locale,
            'is_default' => (bool) $profile->is_default,
            'active' => (bool) $profile->active,
            'branding' => $profile->branding ?? [],
            'provider_overrides' => $profile->provider_overrides ?? [],
            'widget_settings' => $profile->widget_settings ?? [],
            'support_settings' => $profile->support_settings ?? [],
            'lead_settings' => $profile->lead_settings ?? [],
            'system_prompt' => $profile->system_prompt,
            'faq_items_count' => (int) ($profile->faq_items_count ?? 0),
            'source_connections_count' => (int) ($profile->source_connections_count ?? 0),
            'chat_conversations_count' => (int) ($profile->chat_conversations_count ?? 0),
            'lead_submissions_count' => (int) ($profile->lead_submissions_count ?? 0),
            'created_at' => optional($profile->created_at)?->toIso8601String(),
            'updated_at' => optional($profile->updated_at)?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function faq(FaqItem $faq): array
    {
        return [
            'id' => $faq->id,
            'bot_profile_id' => $faq->bot_profile_id,
            'profile' => [
                'handle' => $faq->botProfile?->handle,
                'name' => $faq->botProfile?->name,
            ],
            'site' => $faq->site,
            'locale' => $faq->locale,
            'question' => $faq->question,
            'question_variants' => $faq->question_variants ?? [],
            'answer' => $faq->answer,
            'priority' => $faq->priority,
            'cta_actions' => $faq->cta_actions ?? [],
            'lead_capture_fields' => $faq->lead_capture_fields ?? [],
            'active' => (bool) $faq->active,
            'created_at' => optional($faq->created_at)?->toIso8601String(),
            'updated_at' => optional($faq->updated_at)?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function source(SourceConnection $source): array
    {
        return [
            'id' => $source->id,
            'bot_profile_id' => $source->bot_profile_id,
            'profile' => [
                'handle' => $source->botProfile?->handle,
                'name' => $source->botProfile?->name,
            ],
            'driver' => $source->driver,
            'name' => $source->name,
            'config' => $source->config ?? [],
            'status' => $source->status,
            'last_synced_at' => optional($source->last_synced_at)?->toIso8601String(),
            'last_error' => $source->last_error,
            'active' => (bool) $source->active,
            'knowledge_documents_count' => (int) ($source->knowledge_documents_count ?? 0),
            'created_at' => optional($source->created_at)?->toIso8601String(),
            'updated_at' => optional($source->updated_at)?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function conversation(ChatConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'bot_profile_id' => $conversation->bot_profile_id,
            'profile' => [
                'handle' => $conversation->botProfile?->handle,
                'name' => $conversation->botProfile?->name,
            ],
            'site' => $conversation->site,
            'locale' => $conversation->locale,
            'session_id' => $conversation->session_id,
            'visitor_name' => $conversation->visitor_name,
            'visitor_email' => $conversation->visitor_email,
            'metadata' => $conversation->metadata ?? [],
            'messages_count' => (int) ($conversation->messages_count ?? 0),
            'messages' => $conversation->messages
                ->map(fn ($message) => [
                    'id' => $message->id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'citations' => $message->citations ?? [],
                    'metadata' => $message->metadata ?? [],
                    'created_at' => optional($message->created_at)?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'created_at' => optional($conversation->created_at)?->toIso8601String(),
            'updated_at' => optional($conversation->updated_at)?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function lead(LeadSubmission $lead): array
    {
        return [
            'id' => $lead->id,
            'bot_profile_id' => $lead->bot_profile_id,
            'chat_conversation_id' => $lead->chat_conversation_id,
            'profile' => [
                'handle' => $lead->botProfile?->handle,
                'name' => $lead->botProfile?->name,
            ],
            'site' => $lead->site,
            'locale' => $lead->locale,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'message' => $lead->message,
            'status' => $lead->status,
            'payload' => $lead->payload ?? [],
            'delivery_log' => $lead->delivery_log ?? [],
            'created_at' => optional($lead->created_at)?->toIso8601String(),
            'updated_at' => optional($lead->updated_at)?->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    protected function sites(): array
    {
        try {
            return Site::all()
                ->map(fn ($site) => [
                    'handle' => $site->handle(),
                    'name' => $site->name(),
                    'locale' => $site->lang(),
                ])
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }
}
