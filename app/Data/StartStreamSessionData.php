<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class StartStreamSessionData extends Data
{
    public function __construct(
        public int $user_id,

        public string $title,

        public string $mime_type,

        public bool $has_camera,

        public ?string $quality,
    ) {}
}
