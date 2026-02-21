<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class VideoEditData extends Data
{
    /**
     * @param  BlurRegionData[]  $blur_regions
     * @param  OverlayConfigData[]  $overlay_configs
     */
    public function __construct(
        public array $blur_regions = [],
        public array $overlay_configs = [],
    ) {}
}
