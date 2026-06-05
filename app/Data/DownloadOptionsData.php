<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class DownloadOptionsData extends Data
{
    public function __construct(
        /** Whether to include the camera overlay in the download */
        public bool $include_camera = true,

        /** Camera position: 'bottom-right', 'bottom-left', 'top-right', 'top-left' */
        public string $camera_position = 'bottom-right',

        /** Camera size as fraction of main video width (0.1 - 0.4) */
        public float $camera_size = 0.25,

        /** Whether to burn captions/subtitles into the video */
        public bool $include_captions = false,

        /** Output quality: '1080p', '720p', '480p', 'original' */
        public string $quality = '1080p',
    ) {}
}
