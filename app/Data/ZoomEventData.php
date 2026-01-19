<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class ZoomEventData extends Data
{
    public function __construct(
        public string $type,
        public int $timestamp_ms,
        public ?int $x = null,
        public ?int $y = null,
        public ?string $keys = null,
        public ?int $duration_ms = null,
        public bool $zoom_enabled = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            timestamp_ms: $data['timestamp_ms'],
            x: $data['x'] ?? null,
            y: $data['y'] ?? null,
            keys: $data['keys'] ?? null,
            duration_ms: $data['duration_ms'] ?? null,
            zoom_enabled: $data['zoom_enabled'] ?? true,
        );
    }

    public function isClickEvent(): bool
    {
        return $this->type === 'click';
    }

    public function isKeyboardEvent(): bool
    {
        return $this->type === 'keyboard';
    }

    public function getTimestampSeconds(): float
    {
        return $this->timestamp_ms / 1000;
    }
}
