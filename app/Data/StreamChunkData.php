<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class StreamChunkData extends Data
{
    public function __construct(
        public string $session_id,

        public int $user_id,

        public int $chunk_index,

        /** Absolute path to the uploaded temp file. */
        public string $source_path,

        /** "video" or "camera". */
        public string $type,
    ) {}
}
