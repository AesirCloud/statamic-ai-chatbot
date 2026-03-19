<?php

namespace AesirCloud\StatamicAiChatbot\Support\Knowledge;

use Illuminate\Support\Str;

class TextChunker
{
    /**
     * @return array<int, string>
     */
    public function chunk(string $text): array
    {
        $maxCharacters = (int) config('statamic-ai-chatbot.knowledge.max_chunk_characters', 1200);
        $overlap = (int) config('statamic-ai-chatbot.knowledge.chunk_overlap_characters', 150);
        $plain = Str::of(strip_tags($text))->squish()->value();

        if ($plain === '') {
            return [];
        }

        $chunks = [];
        $offset = 0;
        $length = strlen($plain);

        while ($offset < $length) {
            $chunks[] = trim(substr($plain, $offset, $maxCharacters));

            if (($offset + $maxCharacters) >= $length) {
                break;
            }

            $offset += max($maxCharacters - $overlap, 1);
        }

        return array_values(array_filter($chunks));
    }
}
