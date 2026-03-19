<?php

namespace AesirCloud\StatamicAiChatbot\Support\Knowledge;

use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Models\FaqItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FaqMatcher
{
    public function match(BotProfile $profile, string $question, ?string $site = null, ?string $locale = null): ?FaqItem
    {
        $needle = Str::of($question)->lower()->squish()->value();

        /** @var Collection<int, FaqItem> $items */
        $items = FaqItem::query()
            ->where('bot_profile_id', $profile->id)
            ->where('active', true)
            ->when($site, fn ($query) => $query->where(function ($builder) use ($site) {
                $builder->whereNull('site')->orWhere('site', $site);
            }))
            ->when($locale, fn ($query) => $query->where(function ($builder) use ($locale) {
                $builder->whereNull('locale')->orWhere('locale', $locale);
            }))
            ->orderByDesc('priority')
            ->get();

        return $items
            ->map(function (FaqItem $item) use ($needle) {
                $candidates = collect([$item->question, ...($item->question_variants ?? [])])
                    ->filter()
                    ->map(fn (string $candidate) => Str::of($candidate)->lower()->squish()->value());

                $score = $candidates
                    ->map(fn (string $candidate) => $this->similarity($candidate, $needle))
                    ->max() ?? 0;

                return ['item' => $item, 'score' => $score];
            })
            ->where('score', '>=', 0.72)
            ->sortByDesc('score')
            ->first()['item'] ?? null;
    }

    protected function similarity(string $left, string $right): float
    {
        similar_text($left, $right, $percent);

        return round($percent / 100, 4);
    }
}
