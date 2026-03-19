<?php

use AesirCloud\StatamicAiChatbot\Support\Knowledge\TextChunker;

it('splits long text into multiple chunks', function () {
    config()->set('statamic-ai-chatbot.knowledge.max_chunk_characters', 20);
    config()->set('statamic-ai-chatbot.knowledge.chunk_overlap_characters', 5);

    $chunks = app(TextChunker::class)->chunk(str_repeat('Statamic AI chatbot ', 8));

    expect($chunks)->toBeArray()
        ->and(count($chunks))->toBeGreaterThan(1);
});
