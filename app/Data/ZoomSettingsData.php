<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ZoomSettingsData extends Data
{
    public function __construct(
        public bool $zoom_enabled,
        public float $zoom_level = 2.0,
        public int $zoom_duration_ms = 500,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            zoom_enabled: $data['zoom_enabled'] ?? false,
            zoom_level: $data['zoom_level'] ?? 2.0,
            zoom_duration_ms: $data['zoom_duration_ms'] ?? 500,
        );
    }

    public function getZoomDurationSeconds(): float
    {
        return $this->zoom_duration_ms / 1000;
    }

    public function toModelArray(): array
    {
        return [
            'zoom_enabled' => $this->zoom_enabled,
            'zoom_level' => $this->zoom_level,
            'zoom_duration_ms' => $this->zoom_duration_ms,
        ];
    }
}
