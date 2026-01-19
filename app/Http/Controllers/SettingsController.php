<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use App\Repositories\UserSettingRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

        // Update each provided setting
        $updatedKeys = [];
        foreach ($defaults as $key => $default) {
            if ($request->has($key)) {
                $value = $request->input($key);

                // Apply constraints
                if ($key === 'default_zoom_level') {
                    $value = max(1.2, min(4.0, (float) $value));
                }
                if ($key === 'default_zoom_duration_ms') {
                    $value = max(100, min(2000, (int) $value));
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
}
