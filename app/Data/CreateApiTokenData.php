<?php

namespace App\Data;

use Illuminate\Support\Carbon;
use Spatie\LaravelData\Data;

class CreateApiTokenData extends Data
{
    public function __construct(
        /** Human-readable label for the token, shown in the UI */
        public string $name,

        /** Sanctum abilities granted to the token */
        public array $abilities = ['*'],

        /** Days until the token expires; null means it never expires */
        public ?int $expires_in_days = null,
    ) {}

    /** Resolve the absolute expiry timestamp, or null for a non-expiring token. */
    public function expiresAt(): ?Carbon
    {
        return $this->expires_in_days !== null
            ? now()->addDays($this->expires_in_days)
            : null;
    }
}
