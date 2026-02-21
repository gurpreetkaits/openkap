<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class BlurRegionData extends Data
{
    public function __construct(
        public float $x,
        public float $y,
        public float $width,
        public float $height,
        public ?float $start_time = null,
        public ?float $end_time = null,
    ) {}
}
