<?php

namespace AesirCloud\StatamicAiChatbot\Commands;

use AesirCloud\StatamicAiChatbot\Models\ChatConversation;
use AesirCloud\StatamicAiChatbot\Models\LeadSubmission;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class PruneChatbotDataCommand extends Command
{
    protected $signature = 'statamic-ai-chatbot:prune';

    protected $description = 'Prune expired chatbot conversations and leads based on retention settings.';

    public function handle(): int
    {
        $conversationDays = (int) config('statamic-ai-chatbot.retention.conversation_days', 90);
        $leadDays = (int) config('statamic-ai-chatbot.retention.lead_days', 365);

        ChatConversation::query()
            ->where('created_at', '<', CarbonImmutable::now()->subDays($conversationDays))
            ->delete();

        LeadSubmission::query()
            ->where('created_at', '<', CarbonImmutable::now()->subDays($leadDays))
            ->delete();

        $this->info('Chatbot retention pruning complete.');

        return self::SUCCESS;
    }
}
