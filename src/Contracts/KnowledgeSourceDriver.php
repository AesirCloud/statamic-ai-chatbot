<?php

namespace AesirCloud\StatamicAiChatbot\Contracts;

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Models\SourceConnection;

interface KnowledgeSourceDriver
{
    public function key(): string;

    public function label(): string;

    /**
     * @return iterable<int, array<string, mixed>>
     */
    public function sync(SourceConnection $source, BotProfile $profile): iterable;
}
