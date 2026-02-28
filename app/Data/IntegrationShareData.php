<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class IntegrationShareData extends Data
{
    public function __construct(
        public string $provider,
        public int $video_id,
        public string $target_id,
        public ?string $target_name,
        public ?string $message,
    ) {}
}
