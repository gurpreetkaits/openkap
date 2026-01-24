<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class EmailResponseData extends Data
{
    public function __construct(
        public string $id,
    ) {}
}
