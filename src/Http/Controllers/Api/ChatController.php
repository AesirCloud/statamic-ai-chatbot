<?php

namespace AesirCloud\StatamicAiChatbot\Http\Controllers\Api;

use AesirCloud\StatamicAiChatbot\Support\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController
{
    public function __invoke(Request $request, ChatService $chatService): JsonResponse
    {
        $validated = $request->validate([
            'profile' => ['nullable', 'string'],
            'site' => ['nullable', 'string'],
            'locale' => ['nullable', 'string'],
            'session_id' => ['nullable', 'string'],
            'path' => ['nullable', 'string'],
            'message' => ['required', 'string', 'max:5000'],
            'visitor' => ['nullable', 'array'],
            'visitor.name' => ['nullable', 'string', 'max:255'],
            'visitor.email' => ['nullable', 'email', 'max:255'],
        ]);

        return response()->json($chatService->handle($validated));
    }
}
