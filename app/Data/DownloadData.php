<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class DownloadData extends Data
{
    public function __construct(
        public int $video_id,
        public ?int $user_id = null,
        public ?string $token = null,
        public string $status = 'queued',
        public int $progress = 0,
        public string $format = 'mp4',
        public ?string $file_path = null,
        public ?int $file_size = null,
        public ?string $error_message = null,
        public ?string $expires_at = null,
        public ?string $downloaded_at = null,
    ) {}
}
