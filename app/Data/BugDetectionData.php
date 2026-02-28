<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class BugDetectionData extends Data
{
    public function __construct(
        public array $bugs,
        public int $promptTokens,
        public int $completionTokens,
        public int $totalTokens,
    ) {}
}
