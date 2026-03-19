<?php

namespace AesirCloud\StatamicAiChatbot\Support\Knowledge;

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Models\KnowledgeChunk;
use AesirCloud\StatamicAiChatbot\Support\Config\ProviderManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Ai\Embeddings;

class KnowledgeRetriever
{
    public function __construct(protected ProviderManager $providerManager)
    {
    }

    /**
     * @return Collection<int, KnowledgeChunk>
     */
    public function search(BotProfile $profile, string $query, ?string $site = null, ?string $locale = null): Collection
    {
        $limit = (int) config('statamic-ai-chatbot.knowledge.max_chunks', 6);
        $tokens = collect(preg_split('/\s+/', Str::of($query)->lower()->squish()->value()) ?: [])
            ->filter(fn (?string $token) => filled($token) && strlen($token) > 2)
            ->values();

        $chunks = KnowledgeChunk::query()
            ->with('document')
            ->where('bot_profile_id', $profile->id)
            ->when($site, fn ($queryBuilder) => $queryBuilder->where(function ($builder) use ($site) {
                $builder->whereNull('site')->orWhere('site', $site);
            }))
            ->when($locale, fn ($queryBuilder) => $queryBuilder->where(function ($builder) use ($locale) {
                $builder->whereNull('locale')->orWhere('locale', $locale);
            }))
            ->when($tokens->isNotEmpty(), function ($queryBuilder) use ($tokens) {
                $queryBuilder->where(function ($builder) use ($tokens) {
                    foreach ($tokens as $token) {
                        $builder->orWhere('content_plain', 'like', '%'.$token.'%');
                    }
                });
            })
            ->limit(50)
            ->get()
            ->map(function (KnowledgeChunk $chunk) use ($tokens) {
                $score = $tokens->sum(fn (string $token) => Str::contains(Str::lower($chunk->content_plain), $token) ? 1 : 0);
                $chunk->score = (float) $score;

                return $chunk;
            })
            ->sortByDesc('score')
            ->values();

        if ($chunks->isEmpty()) {
            return collect();
        }

        $embeddingsConfig = $this->providerManager->forEmbeddings($profile);

        if (! $embeddingsConfig['enabled']) {
            return $chunks->take($limit)->values();
        }

        $queryEmbedding = Embeddings::for([$query])
            ->dimensions($embeddingsConfig['dimensions'])
            ->cache()
            ->generate($embeddingsConfig['driver'], $embeddingsConfig['model'])
            ->embeddings[0] ?? null;

        if (! is_array($queryEmbedding)) {
            return $chunks->take($limit)->values();
        }

        return $chunks
            ->map(function (KnowledgeChunk $chunk) use ($queryEmbedding) {
                $embedding = is_array($chunk->embedding) ? $chunk->embedding : null;
                $chunk->score = $embedding ? $this->cosineSimilarity($queryEmbedding, $embedding) : $chunk->score;

                return $chunk;
            })
            ->filter(fn (KnowledgeChunk $chunk) => $chunk->score >= (float) config('statamic-ai-chatbot.knowledge.min_similarity', 0.28))
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    /**
     * @param  array<int, float|int>  $left
     * @param  array<int, float|int>  $right
     */
    protected function cosineSimilarity(array $left, array $right): float
    {
        $dot = 0.0;
        $leftMagnitude = 0.0;
        $rightMagnitude = 0.0;

        foreach ($left as $index => $value) {
            $other = (float) ($right[$index] ?? 0);
            $value = (float) $value;
            $dot += $value * $other;
            $leftMagnitude += $value ** 2;
            $rightMagnitude += $other ** 2;
        }

        if ($leftMagnitude === 0.0 || $rightMagnitude === 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($leftMagnitude) * sqrt($rightMagnitude));
    }
}
