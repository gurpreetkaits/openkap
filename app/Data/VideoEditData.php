<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class VideoEditData extends Data
{
    /**
     * @param  BlurRegionData[]  $blur_regions
     * @param  OverlayConfigData[]  $overlay_configs
     * @param  TextOverlayData[]  $text_overlays
     */
    public function __construct(
        public array $blur_regions = [],
        public array $overlay_configs = [],
        public array $text_overlays = [],
        public ?float $trim_start = null,
        public ?float $trim_end = null,
        public array $merge_video_ids = [],
        public int $main_video_position = 0,
    ) {}
}
