<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class SummaryData extends Data
{
    public function __construct(
        public string $summary,
        public int $promptTokens,
        public int $completionTokens,
        public int $totalTokens,
    ) {}
}
