<?php

namespace AesirCloud\StatamicAiChatbot\Contracts;

use AesirCloud\StatamicAiChatbot\Models\BotProfile;

interface SupportHandoffResolver
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<int, array<string, mixed>>
     */
    public function resolve(BotProfile $profile, string $intent, int $confidence, array $context = []): array;
}
