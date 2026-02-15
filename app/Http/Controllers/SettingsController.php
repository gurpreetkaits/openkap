<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use App\Repositories\UserSettingRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function __construct(
        protected UserSettingRepository $settingsRepository
    ) {}

    /**
     * Get current user's settings.
     */
    public function index()
    {
        $user = Auth::user();
        $settings = $this->settingsRepository->getAllAsArray($user);

        return response()->json([
            'settings' => $settings,
        ]);
    }

    /**
     * Update user's settings.
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        $defaults = UserSetting::getDefaults();

        Log::info('Settings update request', [
            'user_id' => $user->id,
            'request_data' => $request->all(),
        ]);

        // Validate only known settings
        $rules = [];
        foreach ($defaults as $key => $default) {
            $rules[$key] = match ($default['type']) {
                'boolean' => 'sometimes|boolean',
                'integer' => 'sometimes|integer',
                'float' => 'sometimes|numeric',
                'json' => 'sometimes|array',
                default => 'sometimes|string',
            };
        }

        // Add specific validation rules for zoom settings
        if (isset($rules['default_zoom_level'])) {
            $rules['default_zoom_level'] = 'sometimes|numeric|min:1.2|max:4';
        }
        if (isset($rules['default_zoom_duration_ms'])) {
            $rules['default_zoom_duration_ms'] = 'sometimes|integer|min:100|max:2000';
        }

        $request->validate($rules);

        // Paid-only settings require active subscription
        $paidOnlySettings = ['brand_color', 'organization_logo'];

        // Update each provided setting
        $updatedKeys = [];
        foreach ($defaults as $key => $default) {
            if ($request->has($key)) {
                // Block paid-only settings for free users
                if (in_array($key, $paidOnlySettings) && ! $user->hasActiveSubscription()) {
                    continue;
                }

                $value = $request->input($key);

                // Apply constraints
                if ($key === 'default_zoom_level') {
                    $value = max(1.2, min(4.0, (float) $value));
                }
                if ($key === 'default_zoom_duration_ms') {
                    $value = max(100, min(2000, (int) $value));
                }
                if ($key === 'brand_color') {
                    $value = preg_match('/^#[0-9A-Fa-f]{6}$/', $value) ? $value : '#F97316';
                }

                $this->settingsRepository->set($user, $key, $value);
                $updatedKeys[] = $key;
            }
        }

        $settings = $this->settingsRepository->getAllAsArray($user);

        Log::info('Settings updated successfully', [
            'user_id' => $user->id,
            'updated_keys' => $updatedKeys,
            'new_settings' => $settings,
        ]);

        return response()->json([
            'message' => 'Settings updated successfully',
            'settings' => $settings,
        ]);
    }

    /**
     * Reset settings to defaults.
     */
    public function reset()
    {
        $user = Auth::user();
        $settings = $this->settingsRepository->resetToDefaults($user);

        return response()->json([
            'message' => 'Settings reset to defaults',
            'settings' => $settings,
        ]);
    }

    /**
     * Get a single setting value.
     */
    public function getSetting(string $key)
    {
        $user = Auth::user();
        $value = $this->settingsRepository->get($user, $key);

        return response()->json([
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Update a single setting.
     */
    public function setSetting(Request $request, string $key)
    {
        $user = Auth::user();
        $defaults = UserSetting::getDefaults();

        if (! isset($defaults[$key])) {
            return response()->json([
                'message' => 'Unknown setting key',
            ], 400);
        }

        $request->validate([
            'value' => 'required',
        ]);

        $value = $request->input('value');
        $this->settingsRepository->set($user, $key, $value);

        return response()->json([
            'message' => 'Setting updated successfully',
            'key' => $key,
            'value' => $this->settingsRepository->get($user, $key),
        ]);
    }

    /**
     * Upload organization logo (paid users only).
     */
    public function uploadLogo(Request $request)
    {
        $user = Auth::user();

        if (! $user->hasActiveSubscription()) {
            return response()->json([
                'message' => 'An active subscription is required to upload an organization logo.',
            ], 403);
        }

        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
        ]);

        // Delete old logo if it exists
        $oldLogo = $this->settingsRepository->get($user, 'organization_logo');
        if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
            Storage::disk('public')->delete($oldLogo);
        }

        $path = $request->file('logo')->store('logos', 'public');
        $this->settingsRepository->set($user, 'organization_logo', $path);

        Log::info('Organization logo uploaded', [
            'user_id' => $user->id,
            'path' => $path,
        ]);

        return response()->json([
            'message' => 'Logo uploaded successfully',
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * Remove organization logo.
     */
    public function removeLogo()
    {
        $user = Auth::user();

        $logo = $this->settingsRepository->get($user, 'organization_logo');
        if ($logo && Storage::disk('public')->exists($logo)) {
            Storage::disk('public')->delete($logo);
        }

        $this->settingsRepository->set($user, 'organization_logo', '');

        return response()->json([
            'message' => 'Logo removed successfully',
        ]);
    }
}
