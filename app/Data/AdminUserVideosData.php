<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class AdminUserVideosData extends Data
{
    public function __construct(
        public int $user_id,

        public string $user_name,

        public string $user_email,

        /** Total videos this user has, regardless of how many are returned below */
        public int $total_videos,

        /** How many of the returned recordings look unusable (health 'failed' or 'empty') */
        public int $problem_videos,

        /** @var AdminUserVideoData[] */
        public array $videos,
    ) {}
}
