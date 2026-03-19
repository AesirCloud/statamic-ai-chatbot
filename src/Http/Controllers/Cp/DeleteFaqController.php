<?php

namespace AesirCloud\StatamicAiChatbot\Http\Controllers\Cp;

use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\Concerns\RespondsWithDashboardData;
use AesirCloud\StatamicAiChatbot\Models\FaqItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteFaqController
{
    use RespondsWithDashboardData;

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:faq_items,id'],
        ]);

        FaqItem::query()->findOrFail($validated['id'])->delete();

        return $this->dashboardResponse($request, 'FAQ deleted.');
    }
}
