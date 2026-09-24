<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class StreamChunkReceiptData extends Data
{
    public function __construct(
        public int $chunk_index,

        public int $chunk_size,

        public string $type,

        /** How many distinct chunks of this type are now on disk. */
        public int $chunks_received,

        public int $total_size,
    ) {}
}
