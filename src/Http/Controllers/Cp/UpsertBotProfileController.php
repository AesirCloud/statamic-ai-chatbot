<?php

namespace AesirCloud\StatamicAiChatbot\Http\Controllers\Cp;

use AesirCloud\StatamicAiChatbot\Http\Controllers\Cp\Concerns\RespondsWithDashboardData;
use AesirCloud\StatamicAiChatbot\Models\BotProfile;
use AesirCloud\StatamicAiChatbot\Support\Config\SettingsRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpsertBotProfileController
{
    use RespondsWithDashboardData;

    public function __invoke(Request $request, SettingsRepository $settingsRepository): JsonResponse
    {
        $providerKeys = collect($settingsRepository->providerCatalog())->pluck('key')->all();
        $profileId = $request->integer('id') ?: null;
        $existingProfile = $profileId ? BotProfile::query()->findOrFail($profileId) : null;

        $validated = $request->validate([
            'id' => ['nullable', 'integer', 'exists:bot_profiles,id'],
            'handle' => ['required', 'alpha_dash', 'max:255', Rule::unique('bot_profiles', 'handle')->ignore($profileId)],
            'name' => ['required', 'string', 'max:255'],
            'site' => ['nullable', 'string', 'max:255'],
            'locale' => ['nullable', 'string', 'max:255'],
            'is_default' => ['required', 'boolean'],
            'active' => ['required', 'boolean'],
            'branding' => ['nullable', 'array'],
            'branding.voice' => ['nullable', 'string', 'max:1000'],
            'provider_overrides' => ['nullable', 'array'],
            'provider_overrides.text' => ['nullable', 'array'],
            'provider_overrides.text.driver' => ['nullable', 'string', Rule::in($providerKeys)],
            'provider_overrides.text.model' => ['nullable', 'string', 'max:255'],
            'provider_overrides.embeddings' => ['nullable', 'array'],
            'provider_overrides.embeddings.driver' => ['nullable', 'string', Rule::in($providerKeys)],
            'provider_overrides.embeddings.model' => ['nullable', 'string', 'max:255'],
            'provider_overrides.embeddings.dimensions' => ['nullable', 'integer', 'min:1'],
            'provider_overrides.embeddings.enabled' => ['nullable', 'boolean'],
            'widget_settings' => ['nullable', 'array'],
            'widget_settings.position' => ['nullable', Rule::in(['bottom-right', 'bottom-left', 'top-right', 'top-left'])],
            'widget_settings.width' => ['nullable', 'string', 'max:255'],
            'widget_settings.eyebrow_label' => ['nullable', 'string', 'max:255'],
            'widget_settings.launcher_label' => ['nullable', 'string', 'max:255'],
            'widget_settings.welcome_title' => ['nullable', 'string', 'max:255'],
            'widget_settings.welcome_message' => ['nullable', 'string', 'max:2000'],
            'widget_settings.primary_color' => ['nullable', 'string', 'max:32'],
            'widget_settings.accent_color' => ['nullable', 'string', 'max:32'],
            'widget_settings.support_hours' => ['nullable', 'string', 'max:255'],
            'widget_settings.privacy_notice' => ['nullable', 'string', 'max:2000'],
            'widget_settings.logo_url' => ['nullable', 'string', 'max:2000'],
            'support_settings' => ['nullable', 'array'],
            'support_settings.contact_url' => ['nullable', 'string', 'max:2000'],
            'support_settings.email' => ['nullable', 'string', 'max:255'],
            'support_settings.phone' => ['nullable', 'string', 'max:255'],
            'support_settings.label' => ['nullable', 'string', 'max:255'],
            'lead_settings' => ['nullable', 'array'],
            'lead_settings.enabled' => ['nullable', 'boolean'],
            'lead_settings.headline' => ['nullable', 'string', 'max:255'],
            'lead_settings.description' => ['nullable', 'string', 'max:2000'],
            'system_prompt' => ['nullable', 'string', 'max:20000'],
        ]);

        $profile = BotProfile::query()->updateOrCreate(
            ['id' => $profileId],
            [
                'handle' => $validated['handle'],
                'name' => $validated['name'],
                'site' => $validated['site'] ?? null,
                'locale' => $validated['locale'] ?? null,
                'is_default' => $validated['is_default'],
                'active' => $validated['active'],
                'branding' => $this->stripNullValues(Arr::get($validated, 'branding', [])),
                'provider_overrides' => $this->normalizeProviderOverrides(
                    $this->stripNullValues(Arr::get($validated, 'provider_overrides', []))
                ),
                'widget_settings' => $this->stripNullValues(Arr::get($validated, 'widget_settings', [])),
                'support_settings' => $this->stripNullValues(Arr::get($validated, 'support_settings', [])),
                'lead_settings' => $this->stripNullValues(Arr::get($validated, 'lead_settings', [])),
                'system_prompt' => $validated['system_prompt'] ?? null,
            ]
        );

        if ($profile->is_default || ! BotProfile::query()->where('is_default', true)->exists()) {
            $profile->forceFill(['is_default' => true])->save();
            BotProfile::query()->whereKeyNot($profile->id)->update(['is_default' => false]);
            $this->updateDefaultProfileHandle($settingsRepository, $profile->handle);
        } elseif ($existingProfile && config('statamic-ai-chatbot.default_profile_handle') === $existingProfile->handle) {
            $this->updateDefaultProfileHandle($settingsRepository, $profile->handle);
        }

        return $this->dashboardResponse($request, $profileId ? 'Profile updated.' : 'Profile created.');
    }

    protected function updateDefaultProfileHandle(SettingsRepository $settingsRepository, ?string $handle): void
    {
        $settings = $settingsRepository->all();
        $settings['default_profile_handle'] = $handle;
        $settingsRepository->save($settings);
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function stripNullValues(array $values): array
    {
        return collect($values)
            ->map(function ($value) {
                if (is_array($value)) {
                    return $this->stripNullValues($value);
                }

                return $value;
            })
            ->reject(fn ($value) => $value === null)
            ->all();
    }

    protected function normalizeProviderOverrides(array $overrides): array
    {
        foreach (['text', 'embeddings'] as $channel) {
            $driver = Str::lower((string) Arr::get($overrides, "{$channel}.driver", ''));
            $model = Arr::get($overrides, "{$channel}.model");

            if ($this->shouldNormalizeModelIdentifier($driver) && is_string($model) && trim($model) !== '') {
                Arr::set($overrides, "{$channel}.model", Str::lower(trim($model)));
            }
        }

        return $overrides;
    }

    protected function shouldNormalizeModelIdentifier(string $driver): bool
    {
        return in_array($driver, [
            'openai',
            'anthropic',
            'gemini',
            'xai',
            'groq',
            'openrouter',
            'cohere',
            'voyageai',
            'deepseek',
            'mistral',
        ], true);
    }
}
