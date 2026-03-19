<?php

namespace AesirCloud\StatamicAiChatbot\Support\Knowledge;

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Models\KnowledgeDocument;
use AesirCloud\StatamicAiChatbot\Models\SourceConnection;
use AesirCloud\StatamicAiChatbot\Support\Config\ProviderManager;
use Illuminate\Support\Arr;
use AesirCloud\StatamicAiChatbot\Support\Sources\DriverManager;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Embeddings;
use Throwable;

class KnowledgeSyncService
{
    public function __construct(
        protected DriverManager $driverManager,
        protected TextChunker $textChunker,
        protected ProviderManager $providerManager,
    ) {
    }

    public function syncProfile(BotProfile $profile): int
    {
        $synced = 0;

        foreach ($profile->sourceConnections()->where('active', true)->get() as $source) {
            try {
                $this->syncSource($profile, $source);
                $synced++;
            } catch (Throwable) {
                // The source is already marked with the error details in syncSource.
            }
        }

        return $synced;
    }

    public function syncSource(BotProfile $profile, SourceConnection $source): void
    {
        try {
            $driver = $this->driverManager->driver($source->driver);
            $documents = $driver->sync($source, $profile);
            $embeddingsConfig = $this->providerManager->forEmbeddings($profile);

            DB::transaction(function () use ($profile, $source, $documents, $embeddingsConfig) {
                $source->knowledgeDocuments()->delete();

                foreach ($documents as $documentData) {
                    $document = KnowledgeDocument::query()->create([
                        'bot_profile_id' => $profile->id,
                        'source_connection_id' => $source->id,
                        'driver' => $source->driver,
                        'external_id' => $documentData['external_id'],
                        'site' => $documentData['site'] ?? null,
                        'locale' => $documentData['locale'] ?? null,
                        'title' => $documentData['title'] ?? 'Untitled',
                        'excerpt' => $documentData['excerpt'] ?? null,
                        'url' => $documentData['url'] ?? null,
                        'metadata' => $documentData['metadata'] ?? [],
                        'content_hash' => sha1((string) ($documentData['content'] ?? '')),
                    ]);

                    $chunks = $this->textChunker->chunk((string) ($documentData['content'] ?? ''));
                    $vectors = $this->generateEmbeddings($chunks, $embeddingsConfig);

                    foreach ($chunks as $index => $chunk) {
                        $document->chunks()->create([
                            'bot_profile_id' => $profile->id,
                            'site' => $documentData['site'] ?? null,
                            'locale' => $documentData['locale'] ?? null,
                            'position' => $index,
                            'content' => $chunk,
                            'content_plain' => $chunk,
                            'embedding' => $vectors[$index] ?? null,
                            'metadata' => [
                                'title' => $document->title,
                                'url' => $document->url,
                                'source' => $source->name,
                                'driver' => $source->driver,
                                ...($documentData['metadata'] ?? []),
                            ],
                        ]);
                    }
                }
            });

            $source->forceFill([
                'status' => 'ready',
                'last_synced_at' => now(),
                'last_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $source->forceFill([
                'status' => 'error',
                'last_error' => $exception->getMessage(),
            ])->save();

            throw $exception;
        }
    }

    /**
     * @param  array<int, string>  $chunks
     * @param  array{driver:string,model:?string,dimensions:int,enabled:bool}  $embeddingsConfig
     * @return array<int, mixed>
     */
    protected function generateEmbeddings(array $chunks, array $embeddingsConfig): array
    {
        if ($chunks === [] || ! $embeddingsConfig['enabled'] || ! $this->hasEmbeddingsCredentials($embeddingsConfig)) {
            return [];
        }

        try {
            return Embeddings::for($chunks)
                ->dimensions($embeddingsConfig['dimensions'])
                ->cache()
                ->generate($embeddingsConfig['driver'], $embeddingsConfig['model'])
                ->embeddings;
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  array{driver:string,model:?string,dimensions:int,enabled:bool}  $embeddingsConfig
     */
    protected function hasEmbeddingsCredentials(array $embeddingsConfig): bool
    {
        $provider = (string) ($embeddingsConfig['driver'] ?? '');
        $providerConfig = config("ai.providers.{$provider}", []);

        if ($provider === 'ollama') {
            return filled(Arr::get($providerConfig, 'url'));
        }

        return filled(Arr::get($providerConfig, 'key'));
    }
}
