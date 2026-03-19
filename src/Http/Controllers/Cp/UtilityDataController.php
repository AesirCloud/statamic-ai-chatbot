<?php

namespace AesirCloud\StatamicAiChatbot\Http\Controllers\Cp;

use AesirCloud\StatamicAiChatbot\Support\Cp\DashboardData;
use Illuminate\Http\Request;

class UtilityDataController
{
    /**
     * @return array<string, mixed>
     */
    public static function props(Request $request): array
    {
        return app(DashboardData::class)->toArray($request);
    }
}
