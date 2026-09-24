<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class CompleteStreamUploadData extends Data
{
    public function __construct(
        public string $session_id,

        public int $user_id,

        public ?string $title,

        public ?int $duration,

        /**
         * Total chunks the client produced. Lets the server prove nothing
         * was lost before it builds a video out of a partial upload.
         */
        public ?int $expected_chunks,

        public ?int $expected_camera_chunks,

        public ?bool $has_camera,

        public ?float $zoom_level,

        public ?int $zoom_duration_ms,

        public ?array $zoom_events,
    ) {}
}
