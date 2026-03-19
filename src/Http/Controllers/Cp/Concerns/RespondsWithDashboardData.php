<?php

namespace AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\Concerns;

use AesirCloud\StatamicAiChatbot\Support\Cp\DashboardData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

trait RespondsWithDashboardData
{
    protected function dashboardResponse(Request $request, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => app(DashboardData::class)->toArray($request),
        ], $status);
    }
}
