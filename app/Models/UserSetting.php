<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'key',
        'value',
        'type',
    ];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ============================================
    // VALUE TYPE CASTING
    // ============================================

    /**
     * Get the typed value based on the type column.
     */
    public function getTypedValueAttribute(): mixed
    {
        return self::castValue($this->value, $this->type);
    }

    /**
     * Cast a value based on its type.
     */
    public static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean' => $value === 'true' || $value === '1',
            'integer' => (int) $value,
            'float' => (float) $value,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Convert a value to string for storage.
     */
    public static function valueToString(mixed $value, string $type): string
    {
        return match ($type) {
            'boolean' => self::normalizeBoolean($value) ? 'true' : 'false',
            'json' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Normalize various boolean representations to actual boolean.
     */
    public static function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
        }

        return (bool) $value;
    }

    // ============================================
    // DEFAULTS
    // ============================================

    /**
     * Get default settings with their types.
     */
    public static function getDefaults(): array
    {
        return [
            'auto_zoom_enabled' => ['value' => false, 'type' => 'boolean'],
            'default_zoom_level' => ['value' => 2.0, 'type' => 'float'],
            'default_zoom_duration_ms' => ['value' => 500, 'type' => 'integer'],
            'bunny_encoding_enabled' => ['value' => true, 'type' => 'boolean'],
        ];
    }

    /**
     * Get a default value for a specific key.
     */
    public static function getDefault(string $key): mixed
    {
        $defaults = self::getDefaults();

        if (isset($defaults[$key])) {
            return $defaults[$key]['value'];
        }

        return null;
    }

    /**
     * Get the type for a specific key.
     */
    public static function getType(string $key): string
    {
        $defaults = self::getDefaults();

        return $defaults[$key]['type'] ?? 'string';
    }
}
