<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Support\Collection;

class UserSettingRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new UserSetting);
    }

    /**
     * Get all settings for a user as a key-value collection.
     */
    public function getAllForUser(User $user): Collection
    {
        return UserSetting::where('user_id', $user->id)->get();
    }

    /**
     * Get all settings for a user as an associative array with typed values.
     */
    public function getAllAsArray(User $user): array
    {
        $settings = $this->getAllForUser($user);
        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = $setting->typed_value;
        }

        // Fill in defaults for any missing settings
        foreach (UserSetting::getDefaults() as $key => $default) {
            if (! isset($result[$key])) {
                $result[$key] = $default['value'];
            }
        }

        return $result;
    }

    /**
     * Get a specific setting value for a user.
     */
    public function get(User $user, string $key): mixed
    {
        $setting = UserSetting::where('user_id', $user->id)
            ->where('key', $key)
            ->first();

        if ($setting) {
            return $setting->typed_value;
        }

        return UserSetting::getDefault($key);
    }

    /**
     * Set a specific setting value for a user.
     */
    public function set(User $user, string $key, mixed $value, ?string $type = null): UserSetting
    {
        $type = $type ?? UserSetting::getType($key);
        $stringValue = UserSetting::valueToString($value, $type);

        return UserSetting::updateOrCreate(
            ['user_id' => $user->id, 'key' => $key],
            ['value' => $stringValue, 'type' => $type]
        );
    }

    /**
     * Set multiple settings at once.
     */
    public function setMany(User $user, array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set($user, $key, $value);
        }
    }

    /**
     * Ensure all default settings exist for a user.
     */
    public function ensureDefaults(User $user): void
    {
        $existingKeys = UserSetting::where('user_id', $user->id)
            ->pluck('key')
            ->toArray();

        foreach (UserSetting::getDefaults() as $key => $default) {
            if (! in_array($key, $existingKeys)) {
                $this->set($user, $key, $default['value'], $default['type']);
            }
        }
    }

    /**
     * Reset all settings to defaults for a user.
     */
    public function resetToDefaults(User $user): array
    {
        foreach (UserSetting::getDefaults() as $key => $default) {
            $this->set($user, $key, $default['value'], $default['type']);
        }

        return $this->getAllAsArray($user);
    }

    /**
     * Delete a specific setting for a user.
     */
    public function deleteSetting(User $user, string $key): bool
    {
        return UserSetting::where('user_id', $user->id)
            ->where('key', $key)
            ->delete() > 0;
    }

    /**
     * Delete all settings for a user.
     */
    public function deleteAllForUser(User $user): int
    {
        return UserSetting::where('user_id', $user->id)->delete();
    }

    /**
     * Check if a user has auto-zoom enabled.
     */
    public function isAutoZoomEnabled(User $user): bool
    {
        return (bool) $this->get($user, 'auto_zoom_enabled');
    }

    /**
     * Get branding settings (brand_color + organization_logo) for a user.
     */
    public function getBrandingForUser(User $user): array
    {
        $brandColor = $this->get($user, 'brand_color');
        $logo = $this->get($user, 'organization_logo');

        return [
            'brand_color' => $brandColor ?: '#F97316',
            'logo_url' => $logo ? \Illuminate\Support\Facades\Storage::disk('public')->url($logo) : null,
        ];
    }
}
