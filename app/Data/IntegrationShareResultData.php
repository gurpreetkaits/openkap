<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class IntegrationShareResultData extends Data
{
    public function __construct(
        public bool $success,
        public ?string $external_url,
        public ?string $external_id,
        public ?string $error,
        public ?array $metadata,
    ) {}
}
