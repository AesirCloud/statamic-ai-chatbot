<?php

namespace AesirCloud\StatamicAiChatbot\Http\Controllers\Cp;

use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\Concerns\RespondsWithDashboardData;
use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Support\Config\SettingsRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeleteBotProfileController
{
    use RespondsWithDashboardData;

    public function __invoke(Request $request, SettingsRepository $settingsRepository): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:bot_profiles,id'],
        ]);

        $profile = BotProfile::query()->findOrFail($validated['id']);
        $wasDefault = $profile->is_default || config('statamic-ai-chatbot.default_profile_handle') === $profile->handle;

        $profile->delete();

        if ($wasDefault) {
            $replacement = BotProfile::query()
                ->orderByDesc('active')
                ->orderByDesc('is_default')
                ->orderBy('name')
                ->first();

            if ($replacement) {
                $replacement->forceFill(['is_default' => true])->save();
                BotProfile::query()->whereKeyNot($replacement->id)->update(['is_default' => false]);
            }

            $settings = $settingsRepository->all();
            $settings['default_profile_handle'] = $replacement?->handle;
            $settingsRepository->save($settings);
        }

        return $this->dashboardResponse($request, 'Profile deleted.');
    }
}
