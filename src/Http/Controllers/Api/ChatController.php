<?php

namespace AesirCloud\StatamicAiChatbot\Http\Controllers\Api;

use AesirCloud\StatamicAiChatbot\Support\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController
{
    public function __invoke(Request $request, ChatService $chatService): JsonResponse
    {
        if (! config('statamic-ai-chatbot.enabled', true)) {
            return response()->json([
                'conversation_id' => null,
                'message' => 'The chatbot is currently turned off.',
                'intent' => 'disabled',
                'confidence' => 0,
                'status' => 'disabled',
                'error_code' => 'chatbot_disabled',
                'citations' => [],
                'next_actions' => [],
                'lead_capture_fields' => [],
                'widget' => config('statamic-ai-chatbot.widget', []),
            ], 503);
        }

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
