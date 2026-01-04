<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class TranscriptionData extends Data
{
    public function __construct(
        public string $text,
        public string $language,
        public float $duration,
        public ?array $segments = null,
    ) {}
}
