<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class AdminUserVideoData extends Data
{
    public function __construct(
        public int $id,

        public string $title,

        /** Recording length in seconds; null/0 means nothing was captured */
        public ?int $duration,

        /** Stored size in bytes; null/0 means the upload produced no data */
        public ?int $file_size_bytes,

        /** Source resolution reported by Bunny, e.g. "1920x1080"; null until processed */
        public ?string $bunny_resolution,

        public bool $has_audio,

        public bool $has_camera,

        /** Where the file lives: 'local', 'bunny', ... */
        public ?string $storage_type,

        public ?string $conversion_status,

        public ?string $hls_status,

        public ?string $bunny_status,

        /** First non-empty pipeline error, so admins see why a recording failed */
        public ?string $error,

        /**
         * Rolled-up recording health, so a bad capture is obvious at a glance:
         * 'failed'     - a pipeline stage errored
         * 'empty'      - finished, but no duration or no bytes (user recorded nothing usable)
         * 'processing' - still converting/uploading
         * 'ok'         - usable recording
         */
        public string $health,

        public string $created_at,
    ) {}
}
