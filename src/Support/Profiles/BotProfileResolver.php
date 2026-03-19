<?php

namespace AesirCloud\StatamicAiChatbot\Support\Profiles;

use AesirCloud\StatamicAiChatbot\Models\BotProfile;

class BotProfileResolver
{
    public function resolve(?string $handle = null, ?string $site = null, ?string $locale = null): BotProfile
    {
        $handle ??= config('statamic-ai-chatbot.default_profile_handle', 'default');

        $baseQuery = BotProfile::query()
            ->where('active', true)
            ->when($handle, fn ($query) => $query->where('handle', $handle))
            ->when($locale, fn ($query) => $query->where(function ($builder) use ($locale) {
                $builder->whereNull('locale')->orWhere('locale', $locale);
            }))
            ->orderByDesc('is_default');

        if ($site) {
            $scopedProfile = (clone $baseQuery)
                ->where(function ($builder) use ($site) {
                    $builder->whereNull('site')->orWhere('site', $site);
                })
                ->first();

            if ($scopedProfile) {
                return $scopedProfile;
            }
        }

        return $baseQuery->firstOrFail();
    }
}
