<?php

namespace AesirCloud\StatamicAiChatbot\Http\Controllers\Cp;

use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\Concerns\RespondsWithDashboardData;
use AesirCloud\StatamicAiChatbot\Models\SourceConnection;
use AesirCloud\StatamicAiChatbot\Support\Knowledge\KnowledgeSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class SyncSourceController
{
    use RespondsWithDashboardData;

    public function __invoke(Request $request, KnowledgeSyncService $syncService): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id' => ['required', 'integer', 'exists:source_connections,id'],
            ]);

            $source = SourceConnection::query()->with('botProfile')->findOrFail($validated['id']);
            $syncService->syncSource($source->botProfile, $source);

            return $this->dashboardResponse($request, 'Source synced.');
        } catch (Throwable $exception) {
            return $this->dashboardResponse($request, 'Source sync failed: '.$exception->getMessage(), 422);
        }
    }
}
