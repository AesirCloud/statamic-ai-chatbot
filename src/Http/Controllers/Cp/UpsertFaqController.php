<?php

namespace AesirCloud\StatamicAiChatbot\Http\Controllers\Cp;

use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\Concerns\RespondsWithDashboardData;
use AesirCloud\StatamicAiChatbot\Models\FaqItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class UpsertFaqController
{
    use RespondsWithDashboardData;

    public function __invoke(Request $request): JsonResponse
    {
        $faqId = $request->integer('id') ?: null;

        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:faq_items,id'],
            'bot_profile_id' => ['required', 'integer', 'exists:bot_profiles,id'],
            'site' => ['nullable', 'string', 'max:255'],
            'locale' => ['nullable', 'string', 'max:255'],
            'question' => ['required', 'string', 'max:255'],
            'question_variants' => ['nullable', 'array'],
            'question_variants.*' => ['nullable', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:20000'],
            'priority' => ['required', 'integer', 'min:0'],
            'cta_actions' => ['nullable', 'array'],
            'cta_actions.*' => ['nullable', 'array'],
            'cta_actions.*.type' => ['nullable', 'string', Rule::in(['link', 'lead_capture', 'email', 'phone'])],
            'cta_actions.*.label' => ['nullable', 'string', 'max:255'],
            'cta_actions.*.url' => ['nullable', 'string', 'max:2000'],
            'cta_actions.*.value' => ['nullable', 'string', 'max:255'],
            'lead_capture_fields' => ['nullable', 'array'],
            'lead_capture_fields.*' => ['nullable', 'string', 'max:255'],
            'active' => ['required', 'boolean'],
        ]);

        FaqItem::query()->updateOrCreate(
            ['id' => $faqId],
            [
                'bot_profile_id' => $validated['bot_profile_id'],
                'site' => $validated['site'] ?? null,
                'locale' => $validated['locale'] ?? null,
                'question' => $validated['question'],
                'question_variants' => $this->cleanList($validated['question_variants'] ?? []),
                'answer' => $validated['answer'],
                'priority' => $validated['priority'],
                'cta_actions' => collect($validated['cta_actions'] ?? [])
                    ->filter(fn ($action) => filled(Arr::get($action, 'type')) && filled(Arr::get($action, 'label')))
                    ->values()
                    ->all(),
                'lead_capture_fields' => $this->cleanList($validated['lead_capture_fields'] ?? []),
                'active' => $validated['active'],
            ]
        );

        return $this->dashboardResponse($request, $faqId ? 'FAQ updated.' : 'FAQ created.');
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    protected function cleanList(array $values): array
    {
        return Collection::make($values)
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (string) $value)
            ->values()
            ->all();
    }
}
