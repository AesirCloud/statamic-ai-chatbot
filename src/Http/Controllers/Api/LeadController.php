<?php

namespace AesirCloud\StatamicAiChatbot\Http\Controllers\Api;

use AesirCloud\StatamicAiChatbot\Models\LeadSubmission;
use AesirCloud\StatamicAiChatbot\Support\Leads\LeadDestinationManager;
use AesirCloud\StatamicAiChatbot\Support\Profiles\BotProfileResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeadController
{
    public function __invoke(
        Request $request,
        BotProfileResolver $profileResolver,
        LeadDestinationManager $leadDestinationManager,
    ): JsonResponse {
        $validated = $request->validate([
            'profile' => ['nullable', 'string'],
            'site' => ['nullable', 'string'],
            'locale' => ['nullable', 'string'],
            'conversation_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:5000'],
            'payload' => ['nullable', 'array'],
        ]);

        $profile = $profileResolver->resolve(
            handle: $validated['profile'] ?? null,
            site: $validated['site'] ?? null,
            locale: $validated['locale'] ?? null,
        );

        $lead = LeadSubmission::query()->create([
            'bot_profile_id' => $profile->id,
            'chat_conversation_id' => $validated['conversation_id'] ?? null,
            'site' => $validated['site'] ?? $profile->site,
            'locale' => $validated['locale'] ?? $profile->locale,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'message' => $validated['message'] ?? null,
            'status' => 'new',
            'payload' => $validated['payload'] ?? [],
        ]);

        $leadDestinationManager->dispatch($lead);

        return response()->json([
            'id' => $lead->id,
            'status' => $lead->status,
        ]);
    }
}
