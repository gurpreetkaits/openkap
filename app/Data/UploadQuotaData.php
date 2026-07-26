<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class UploadQuotaData extends Data
{
    public function __construct(
        /** Whether the user can upload another video right now */
        public bool $can_upload,

        /** Plan type: 'free', 'pro', or 'teams' */
        public string $plan_type,

        /** Number of videos the user currently has */
        public int $videos_count,

        /** Max videos allowed; null means unlimited */
        public ?int $max_videos,

        /** Remaining video quota; null means unlimited */
        public ?int $remaining_video_quota,

        /** Whether the monthly recording-minutes cap has been exceeded */
        public bool $monthly_minutes_exceeded,

        /** URL to the upgrade / subscription page */
        public string $upgrade_url,
    ) {}
}
