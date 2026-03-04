<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Cache key prefix for settings
     */
    private const CACHE_PREFIX = 'setting_';

    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $cacheKey = self::CACHE_PREFIX.$key;

        try {
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
                $setting = self::where('key', $key)->first();

                if (! $setting) {
                    return $default;
                }

                return self::castValue($setting->value, $setting->type);
            });
        } catch (\Exception $e) {
            // Table doesn't exist or other DB error - return default
            return $default;
        }
    }

    /**
     * Set a setting value
     */
    public static function setValue(string $key, mixed $value, string $type = 'string', string $group = 'general', ?string $description = null): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => is_array($value) || is_object($value) ? json_encode($value) : (string) $value,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );

        // Clear cache
        Cache::forget(self::CACHE_PREFIX.$key);

        return $setting;
    }

    /**
     * Get all settings for a group
     */
    public static function getGroup(string $group): array
    {
        $settings = self::where('group', $group)->get();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key] = self::castValue($setting->value, $setting->type);
        }

        return $result;
    }

    /**
     * Cast value to appropriate type
     */
    private static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer', 'int' => (int) $value,
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'float', 'double' => (float) $value,
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        $settings = self::all();
        foreach ($settings as $setting) {
            Cache::forget(self::CACHE_PREFIX.$setting->key);
        }
    }

    // Convenience methods for common settings
    public static function getFreeVideoLimit(): int
    {
        return (int) self::getValue('free_video_limit', 2);
    }

    public static function getFreeRecordingDurationLimit(): int
    {
        return (int) self::getValue('free_recording_duration_limit', 300);
    }

    public static function getMinRecordingDurationLimit(): int
    {
        return (int) self::getValue('min_recording_duration_limit', 1);
    }

    public static function getMonthlyPrice(): int
    {
        return (int) self::getValue('monthly_price', 8);
    }

    public static function getYearlyPrice(): int
    {
        return (int) self::getValue('yearly_price', 80);
    }
}
