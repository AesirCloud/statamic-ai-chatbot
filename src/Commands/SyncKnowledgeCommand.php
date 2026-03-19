<?php

namespace AesirCloud\StatamicAiChatbot\Commands;

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Support\Knowledge\KnowledgeSyncService;
use Illuminate\Console\Command;

class SyncKnowledgeCommand extends Command
{
    protected $signature = 'statamic-ai-chatbot:sync {profile? : Optional bot profile handle}';

    protected $description = 'Sync configured knowledge sources for the Statamic AI chatbot.';

    public function handle(KnowledgeSyncService $syncService): int
    {
        $profileHandle = $this->argument('profile');

        $profiles = BotProfile::query()
            ->when($profileHandle, fn ($query) => $query->where('handle', $profileHandle))
            ->get();

        foreach ($profiles as $profile) {
            $count = $syncService->syncProfile($profile);
            $this->info("Synced {$count} source(s) for [{$profile->handle}].");
        }

        return self::SUCCESS;
    }
}
