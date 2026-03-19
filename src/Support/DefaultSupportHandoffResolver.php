<?php

namespace AesirCloud\StatamicAiChatbot\Support;

use AesirCloud\StatamicAiChatbot\Contracts\SupportHandoffResolver;
use AesirCloud\StatamicAiChatbot\Models\BotProfile;

class DefaultSupportHandoffResolver implements SupportHandoffResolver
{
    public function resolve(BotProfile $profile, string $intent, int $confidence, array $context = []): array
    {
        $actions = data_get($profile->support_settings, 'actions', []);

        if ($actions !== []) {
            return $actions;
        }

        $default = [
            [
                'type' => 'link',
                'label' => 'Contact support',
                'url' => data_get($profile->support_settings, 'contact_url'),
            ],
            [
                'type' => 'email',
                'label' => 'Email us',
                'value' => data_get($profile->support_settings, 'email'),
            ],
        ];

        if ($intent === 'lead_capture' || $confidence < 55) {
            $default[] = [
                'type' => 'lead_capture',
                'label' => 'Request a follow-up',
            ];
        }

        return collect($default)->filter(fn (array $action) => filled($action['url'] ?? $action['value'] ?? $action['type'] ?? null))->values()->all();
    }
}
