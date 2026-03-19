<?php

namespace AesirCloud\StatamicAiChatbot\Http\Controllers\Cp;

use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\Concerns\RespondsWithDashboardData;
use AesirCloud\StatamicAiChatbot\Models\LeadSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteLeadController
{
    use RespondsWithDashboardData;

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:lead_submissions,id'],
        ]);

        LeadSubmission::query()->findOrFail($validated['id'])->delete();

        return $this->dashboardResponse($request, 'Lead deleted.');
    }
}
