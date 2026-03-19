<?php

namespace AesirCloud\StatamicAiChatbot\Support\Sources;

use AesirCloud\StatamicAiChatbot\Contracts\KnowledgeSourceDriver;
use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Models\SourceConnection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class YouTubeSourceDriver implements KnowledgeSourceDriver
{
    public function key(): string
    {
        return 'youtube';
    }

    public function label(): string
    {
        return 'YouTube Transcripts';
    }

    public function sync(SourceConnection $source, BotProfile $profile): iterable
    {
        $items = [];

        foreach (Arr::wrap($source->config['items'] ?? []) as $item) {
            $parsed = $this->parseItem($item);

            if (! $parsed) {
                continue;
            }

            $response = Http::timeout((int) config('statamic-ai-chatbot.youtube.timeout', 15))
                ->acceptJson()
                ->get('https://www.youtube.com/oembed', [
                    'url' => $parsed['watch_url'],
                    'format' => 'json',
                ]);

            if (! $response->successful()) {
                continue;
            }

            $title = (string) $response->json('title', 'YouTube video');
            $author = (string) $response->json('author_name', 'YouTube');
            $transcript = Arr::get($item, 'transcript', '');

            if (blank($transcript)) {
                continue;
            }

            $items[] = [
                'external_id' => 'youtube:'.$parsed['video_id'],
                'site' => $profile->site,
                'locale' => $profile->locale,
                'title' => $title,
                'excerpt' => Str::limit($transcript, 220),
                'url' => $parsed['watch_url'],
                'content' => $transcript,
                'metadata' => [
                    'type' => 'youtube',
                    'video_id' => $parsed['video_id'],
                    'author' => $author,
                    'timestamp' => Arr::get($item, 'timestamp'),
                    'transcript_available' => true,
                ],
            ];
        }

        return $items;
    }

    /**
     * @return array<string, string>|null
     */
    protected function parseItem(mixed $item): ?array
    {
        $url = is_array($item) ? (string) ($item['url'] ?? '') : (string) $item;

        if (! str_contains($url, 'youtube.com') && ! str_contains($url, 'youtu.be')) {
            return null;
        }

        $videoId = null;
        $parts = parse_url($url);

        if (($parts['host'] ?? null) === 'youtu.be') {
            $videoId = trim((string) ($parts['path'] ?? ''), '/');
        }

        if (blank($videoId) && isset($parts['query'])) {
            parse_str($parts['query'], $query);
            $videoId = $query['v'] ?? null;
        }

        if (blank($videoId)) {
            return null;
        }

        return [
            'video_id' => $videoId,
            'watch_url' => 'https://www.youtube.com/watch?v='.$videoId,
        ];
    }
}
