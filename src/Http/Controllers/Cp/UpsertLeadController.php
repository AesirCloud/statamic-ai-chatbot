<?php

namespace AesirCloud\StatamicAiChatbot\Http\Controllers\Cp;

use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\Concerns\RespondsWithDashboardData;
use AesirCloud\StatamicAiChatbot\Models\LeadSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UpsertLeadController
{
    use RespondsWithDashboardData;

    public function __invoke(Request $request): JsonResponse
    {
        $leadId = $request->integer('id') ?: null;

        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:lead_submissions,id'],
            'bot_profile_id' => ['required', 'integer', 'exists:bot_profiles,id'],
            'chat_conversation_id' => ['nullable', 'integer', 'exists:chat_conversations,id'],
            'site' => ['nullable', 'string', 'max:255'],
            'locale' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', Rule::in(['new', 'contacted', 'qualified', 'converted', 'closed'])],
            'payload' => ['nullable', 'array'],
            'delivery_log' => ['nullable', 'array'],
        ]);

        LeadSubmission::query()->updateOrCreate(
            ['id' => $leadId],
            [
                'bot_profile_id' => $validated['bot_profile_id'],
                'chat_conversation_id' => $validated['chat_conversation_id'] ?? null,
                'site' => $validated['site'] ?? null,
                'locale' => $validated['locale'] ?? null,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'message' => $validated['message'] ?? null,
                'status' => $validated['status'],
                'payload' => $validated['payload'] ?? [],
                'delivery_log' => $validated['delivery_log'] ?? [],
            ]
        );

        return $this->dashboardResponse($request, $leadId ? 'Lead updated.' : 'Lead created.');
    }
}
