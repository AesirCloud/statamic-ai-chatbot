<?php

namespace AesirCloud\StatamicAiChatbot;

use AesirCloud\StatamicAiChatbot\Commands\PruneChatbotDataCommand;
use AesirCloud\StatamicAiChatbot\Commands\SyncKnowledgeCommand;
use AesirCloud\StatamicAiChatbot\Contracts\KnowledgeSourceDriver;
use AesirCloud\StatamicAiChatbot\Contracts\LeadDestinationDriver;
use AesirCloud\StatamicAiChatbot\Contracts\SupportHandoffResolver;
use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\UtilityDataController;
use AesirCloud\StatamicAiChatbot\Support\Config\ProviderManager;
use AesirCloud\StatamicAiChatbot\Support\Config\SettingsRepository;
use AesirCloud\StatamicAiChatbot\Support\Cp\DashboardData;
use AesirCloud\StatamicAiChatbot\Support\DefaultSupportHandoffResolver;
use AesirCloud\StatamicAiChatbot\Support\Knowledge\FaqMatcher;
use AesirCloud\StatamicAiChatbot\Support\Knowledge\KnowledgeRetriever;
use AesirCloud\StatamicAiChatbot\Support\Knowledge\KnowledgeSyncService;
use AesirCloud\StatamicAiChatbot\Support\Knowledge\TextChunker;
use AesirCloud\StatamicAiChatbot\Support\Leads\DatabaseLeadDestination;
use AesirCloud\StatamicAiChatbot\Support\Leads\EmailLeadDestination;
use AesirCloud\StatamicAiChatbot\Support\Leads\LeadDestinationManager;
use AesirCloud\StatamicAiChatbot\Support\Leads\WebhookLeadDestination;
use AesirCloud\StatamicAiChatbot\Support\Profiles\BotProfileResolver;
use AesirCloud\StatamicAiChatbot\Support\Sources\DriverManager;
use AesirCloud\StatamicAiChatbot\Support\Sources\StatamicContentSourceDriver;
use AesirCloud\StatamicAiChatbot\Support\Sources\YouTubeSourceDriver;
use AesirCloud\StatamicAiChatbot\Tags\ChatbotWidgetTag;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Statamic\Facades\Utility;
use Statamic\Providers\AddonServiceProvider;
use Throwable;

class StatamicAiChatbotServiceProvider extends AddonServiceProvider
{
    protected $commands = [
        SyncKnowledgeCommand::class,
        PruneChatbotDataCommand::class,
    ];

    protected $routes = [
        'cp' => __DIR__.'/../routes/cp.php',
        'actions' => __DIR__.'/../routes/actions.php',
        'web' => __DIR__.'/../routes/web.php',
    ];

    protected $tags = [
        ChatbotWidgetTag::class,
    ];

    protected $vite = [
        'input' => [
            'resources/js/cp.js',
            'resources/css/cp.css',
        ],
        'publicDirectory' => 'public',
        'buildDirectory' => 'build',
    ];

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(__DIR__.'/../config/statamic-ai-chatbot.php', 'statamic-ai-chatbot');

        $this->app->singleton(ProviderManager::class);
        $this->app->singleton(SettingsRepository::class);
        $this->app->singleton(DashboardData::class);
        $this->app->singleton(TextChunker::class);
        $this->app->singleton(FaqMatcher::class);
        $this->app->singleton(KnowledgeRetriever::class);
        $this->app->singleton(BotProfileResolver::class);
        $this->app->singleton(KnowledgeSyncService::class);
        $this->app->singleton(SupportHandoffResolver::class, DefaultSupportHandoffResolver::class);

        $this->app->singleton(DriverManager::class, function ($app) {
            return new DriverManager([
                $app->make(StatamicContentSourceDriver::class),
                $app->make(YouTubeSourceDriver::class),
            ]);
        });

        $this->app->singleton(LeadDestinationManager::class, function ($app) {
            return new LeadDestinationManager([
                $app->make(DatabaseLeadDestination::class),
                $app->make(EmailLeadDestination::class),
                $app->make(WebhookLeadDestination::class),
            ]);
        });
    }

    public function bootAddon(): void
    {
        $this->app->make(SettingsRepository::class)->apply();
        $this->ensurePublishedAssets();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'aesircloud-statamic-ai-chatbot');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->publishes([
            __DIR__.'/../config/statamic-ai-chatbot.php' => config_path('statamic-ai-chatbot.php'),
        ], 'statamic-ai-chatbot-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'statamic-ai-chatbot-migrations');

        Utility::extend(function () {
            Utility::register('statamic_ai_chatbot')
                ->title('AI Chatbot')
                ->navTitle('AI Chatbot')
                ->icon('microphone')
                ->description('Manage providers, FAQs, sources, chats, and leads.')
                ->inertia('aesircloud-statamic-ai-chatbot::Dashboard', UtilityDataController::props(...));
        });

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('statamic-ai-chatbot:sync')->hourly();
            $schedule->command('statamic-ai-chatbot:prune')->dailyAt('01:00');
        });
    }

    protected function ensurePublishedAssets(): void
    {
        if ($this->app->runningUnitTests()) {
            return;
        }

        $source = realpath(__DIR__.'/../public/build');
        $target = public_path('vendor/statamic-ai-chatbot/build');

        if (! $source || ! is_dir($source)) {
            return;
        }

        try {
            if ($this->publishedAssetsAreCurrent($source, $target)) {
                return;
            }

            /** @var Filesystem $files */
            $files = $this->app->make(Filesystem::class);

            $files->ensureDirectoryExists(dirname($target));
            $files->deleteDirectory($target);
            $files->copyDirectory($source, $target);
        } catch (Throwable $exception) {
            Log::warning('Statamic AI chatbot could not sync published assets automatically.', [
                'exception_class' => $exception::class,
                'exception_message' => $exception->getMessage(),
            ]);
        }
    }

    protected function publishedAssetsAreCurrent(string $source, string $target): bool
    {
        if (! is_dir($target)) {
            return false;
        }

        foreach ($this->assetManifest($source) as $relativePath => $sourcePath) {
            $targetPath = $target.DIRECTORY_SEPARATOR.$relativePath;

            if (! is_file($targetPath)) {
                return false;
            }

            if (filesize($sourcePath) !== filesize($targetPath)) {
                return false;
            }

            if (sha1_file($sourcePath) !== sha1_file($targetPath)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, string>
     */
    protected function assetManifest(string $source): array
    {
        /** @var Filesystem $files */
        $files = $this->app->make(Filesystem::class);

        return collect($files->allFiles($source))
            ->mapWithKeys(fn ($file) => [
                str_replace($source.DIRECTORY_SEPARATOR, '', $file->getPathname()) => $file->getPathname(),
            ])
            ->all();
    }
}
