<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class TextOverlayData extends Data
{
    public function __construct(
        public string $text,
        public float $x,
        public float $y,
        public int $font_size = 32,
        public string $font_color = 'white',
        public ?string $background_color = 'black',
        public ?float $start_time = null,
        public ?float $end_time = null,
    ) {}
}
