<?php

namespace AesirCloud\StatamicAiChatbot\Http\Controllers\Cp;

use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\Concerns\RespondsWithDashboardData;
use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Support\Knowledge\KnowledgeSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class RunSyncController
{
    use RespondsWithDashboardData;

    public function __invoke(Request $request, KnowledgeSyncService $syncService): JsonResponse
    {
        try {
            $validated = $request->validate([
                'profile' => ['nullable', 'string'],
            ]);

            BotProfile::query()
                ->when($validated['profile'] ?? null, fn ($query, $handle) => $query->where('handle', $handle))
                ->get()
                ->each(fn (BotProfile $profile) => $syncService->syncProfile($profile));

            return $this->dashboardResponse($request, 'Knowledge sync completed.');
        } catch (Throwable $exception) {
            return $this->dashboardResponse($request, 'Knowledge sync failed: '.$exception->getMessage(), 422);
        }
    }
}
