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
        $normalizedQuery = $this->normalizeText($query);
        $tokens = $this->tokenize($query);

        $chunks = KnowledgeChunk::query()
            ->with('document')
            ->where('bot_profile_id', $profile->id)
            ->when($site, fn ($queryBuilder) => $queryBuilder->where(function ($builder) use ($site) {
                $builder->whereNull('site')->orWhere('site', $site);
            }))
            ->when($locale, fn ($queryBuilder) => $queryBuilder->where(function ($builder) use ($locale) {
                $builder->whereNull('locale')->orWhere('locale', $locale);
            }))
            ->get()
            ->map(function (KnowledgeChunk $chunk) use ($normalizedQuery, $tokens) {
                $chunk->score = $this->keywordScore($chunk, $normalizedQuery, $tokens);

                return $chunk;
            })
            ->values();

        if ($chunks->isEmpty()) {
            return collect();
        }

        $embeddingsConfig = $this->providerManager->forEmbeddings($profile);

        if (! $embeddingsConfig['enabled']) {
            return $chunks
                ->filter(fn (KnowledgeChunk $chunk) => $chunk->score > 0)
                ->sortByDesc('score')
                ->take($limit)
                ->values();
        }

        $queryEmbedding = Embeddings::for([$query])
            ->dimensions($embeddingsConfig['dimensions'])
            ->cache()
            ->generate($embeddingsConfig['driver'], $embeddingsConfig['model'])
            ->embeddings[0] ?? null;

        if (! is_array($queryEmbedding)) {
            return $chunks
                ->filter(fn (KnowledgeChunk $chunk) => $chunk->score > 0)
                ->sortByDesc('score')
                ->take($limit)
                ->values();
        }

        $keywordMatches = $chunks->filter(fn (KnowledgeChunk $chunk) => $chunk->score > 0);

        if ($keywordMatches->isNotEmpty()) {
            return $keywordMatches
                ->map(function (KnowledgeChunk $chunk) use ($queryEmbedding) {
                    $embedding = is_array($chunk->embedding) ? $chunk->embedding : null;
                    $semanticScore = $embedding ? max(0.0, $this->cosineSimilarity($queryEmbedding, $embedding)) : 0.0;
                    $chunk->score = $chunk->score + ($semanticScore * 4);

                    return $chunk;
                })
                ->sortByDesc('score')
                ->take($limit)
                ->values();
        }

        return $chunks
            ->map(function (KnowledgeChunk $chunk) use ($queryEmbedding) {
                $embedding = is_array($chunk->embedding) ? $chunk->embedding : null;
                $chunk->score = $embedding ? $this->cosineSimilarity($queryEmbedding, $embedding) : 0.0;

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

    /**
     * @param  Collection<int, string>  $tokens
     */
    protected function keywordScore(KnowledgeChunk $chunk, string $normalizedQuery, Collection $tokens): float
    {
        $title = $this->normalizeText((string) data_get($chunk->metadata, 'title', $chunk->document?->title ?? ''));
        $slug = $this->normalizeText((string) data_get($chunk->metadata, 'slug', ''));
        $handle = $this->normalizeText((string) data_get($chunk->metadata, 'handle', ''));
        $type = $this->normalizeText((string) data_get($chunk->metadata, 'type', ''));
        $content = $this->normalizeText((string) $chunk->content_plain);

        $score = 0.0;

        if ($normalizedQuery !== '') {
            $score += $this->containsPhrase($title, $normalizedQuery) ? 12.0 : 0.0;
            $score += $this->containsPhrase($slug, $normalizedQuery) ? 9.0 : 0.0;
            $score += $this->containsPhrase($handle, $normalizedQuery) ? 8.0 : 0.0;
            $score += $this->containsPhrase($content, $normalizedQuery) ? 4.0 : 0.0;
        }

        foreach ($tokens as $token) {
            $score += $this->containsWholeWord($title, $token) ? 7.0 : 0.0;
            $score += $this->containsWholeWord($slug, $token) ? 6.0 : 0.0;
            $score += $this->containsWholeWord($handle, $token) ? 6.0 : 0.0;
            $score += $this->containsWholeWord($type, $token) ? 3.0 : 0.0;
            $score += $this->containsWholeWord($content, $token) ? 2.0 : 0.0;
        }

        if ($tokens->intersect(['vendor', 'vendors', 'brand', 'brands'])->isNotEmpty()) {
            $score += $handle === 'vendors' ? 10.0 : 0.0;
            $score += $type === 'taxonomy' ? 4.0 : 0.0;
        }

        return $score;
    }

    /**
     * @return Collection<int, string>
     */
    protected function tokenize(string $text): Collection
    {
        $stopWords = [
            'a', 'an', 'and', 'are', 'can', 'could', 'do', 'does', 'for', 'how',
            'i', 'in', 'is', 'it', 'me', 'of', 'on', 'our', 'tell', 'that',
            'the', 'their', 'to', 'us', 'we', 'what', 'which', 'who', 'with',
            'work', 'works', 'would', 'you', 'your',
        ];

        return collect(preg_split('/\s+/', $this->normalizeText($text)) ?: [])
            ->filter(fn (?string $token) => filled($token) && strlen($token) > 1)
            ->reject(fn (string $token) => in_array($token, $stopWords, true))
            ->map(fn (string $token) => Str::singular($token))
            ->unique()
            ->values();
    }

    protected function normalizeText(string $text): string
    {
        return Str::of($text)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/i', ' ')
            ->squish()
            ->value();
    }

    protected function containsPhrase(string $haystack, string $needle): bool
    {
        if ($haystack === '' || $needle === '') {
            return false;
        }

        return Str::contains($haystack, $needle);
    }

    protected function containsWholeWord(string $haystack, string $needle): bool
    {
        if ($haystack === '' || $needle === '') {
            return false;
        }

        return preg_match('/(?:^|\s)'.preg_quote($needle, '/').'(?:\s|$)/', $haystack) === 1;
    }
}
