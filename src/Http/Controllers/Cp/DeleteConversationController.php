<?php

namespace AesirCloud\StatamicAiChatbot\Http\Controllers\Cp;

use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\Concerns\RespondsWithDashboardData;
use AesirCloud\StatamicAiChatbot\Models\ChatConversation;
use AesirCloud\StatamicAiChatbot\Models\LeadSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeleteConversationController
{
    use RespondsWithDashboardData;

    public function __invoke(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:chat_conversations,id'],
            ]);

            DB::transaction(function () use ($validated): void {
                $conversation = ChatConversation::query()->findOrFail($validated['id']);

                LeadSubmission::query()
                    ->where('chat_conversation_id', $validated['id'])
                    ->update(['chat_conversation_id' => null]);

                $conversation->messages()->delete();
                $conversation->delete();
            });

            return $this->dashboardResponse($request, 'Conversation deleted.');
        } catch (Throwable $exception) {
            return $this->dashboardResponse($request, 'Conversation delete failed: '.$exception->getMessage(), 422);
        }
    }
}
