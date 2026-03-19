<?php

namespace AesirCloud\StatamicAiChatbot\Http\Controllers\Cp;

use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\Concerns\RespondsWithDashboardData;
use AesirCloud\StatamicAiChatbot\Models\SourceConnection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteSourceController
{
    use RespondsWithDashboardData;

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:source_connections,id'],
        ]);

        SourceConnection::query()->findOrFail($validated['id'])->delete();

        return $this->dashboardResponse($request, 'Source deleted.');
    }
}
