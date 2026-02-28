<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class IntegrationTokenData extends Data
{
    public function __construct(
        public string $access_token,
        public ?string $refresh_token,
        public ?int $expires_in,
        public ?string $external_user_id,
        public ?string $external_user_name,
        public ?array $metadata,
    ) {}
}
