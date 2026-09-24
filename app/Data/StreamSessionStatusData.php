<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class StreamSessionStatusData extends Data
{
    public function __construct(
        public string $session_id,

        public string $title,

        public int $chunks_received,

        /**
         * Every chunk index currently on disk, ascending. The client
         * reconciles against this before asking to complete.
         *
         * @var list<int>
         */
        public array $received_indexes,

        /**
         * Indexes the client said it recorded that never arrived. Null when
         * the client did not tell us how many to expect.
         *
         * @var list<int>|null
         */
        public ?array $missing_indexes,

        public int $total_size,

        public ?string $started_at,

        public ?string $last_chunk_at,
    ) {}
}
