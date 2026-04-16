<?php

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Facades\Log;

class HlsService
{
    protected string $ffmpegPath;

    protected string $ffprobePath;

    protected string $hlsStoragePath;

    public function __construct()
    {
        $this->ffmpegPath = config('media-library.ffmpeg_path');
        $this->ffprobePath = config('media-library.ffprobe_path');
        $this->hlsStoragePath = storage_path('app/public/hls');
    }

    /**
     * Convert a video to HLS format with multiple quality variants.
     */
    public function convertToHls(Video $video, ?callable $progressCallback = null): array
    {
        $media = $video->getFirstMedia('videos');

        if (! $media) {
            throw new \Exception('No video media found');
        }

        $inputPath = $media->getPath();

        if (! file_exists($inputPath)) {
            throw new \Exception('Video file not found on disk');
        }

        // Get video dimensions to determine quality variants
        $videoInfo = $this->getVideoInfo($inputPath);
        $sourceHeight = $videoInfo['height'] ?? 720;
        $sourceWidth = $videoInfo['width'] ?? 1280;

        // Create HLS output directory
        $hlsDir = $this->hlsStoragePath.'/'.$video->id;
        $this->ensureDirectoryExists($hlsDir);

        // Determine quality variants based on source resolution
        $variants = $this->getQualityVariants($sourceHeight, $sourceWidth);

        if ($progressCallback) {
            $progressCallback(10);
        }

        // Generate HLS streams for each variant
        $this->generateHlsStreams($inputPath, $hlsDir, $variants, $progressCallback);

        // Create master playlist
        $masterPlaylistPath = $this->createMasterPlaylist($hlsDir, $variants);

        if ($progressCallback) {
            $progressCallback(100);
        }

        return [
            'hls_path' => 'hls/'.$video->id,
            'master_playlist' => $masterPlaylistPath,
            'variants' => array_keys($variants),
        ];
    }

    /**
     * Get video information using ffprobe.
     */
    protected function getVideoInfo(string $inputPath): array
    {
        $command = sprintf(
            '%s -v quiet -select_streams v:0 -show_entries stream=width,height,duration,bit_rate -of json %s',
            escapeshellarg($this->ffprobePath),
            escapeshellarg($inputPath)
        );

        $output = [];
        exec($command, $output);
        $json = implode('', $output);
        $data = json_decode($json, true);

        $stream = $data['streams'][0] ?? [];

        return [
            'width' => (int) ($stream['width'] ?? 1280),
            'height' => (int) ($stream['height'] ?? 720),
            'duration' => (float) ($stream['duration'] ?? 0),
            'bitrate' => (int) ($stream['bit_rate'] ?? 2000000),
        ];
    }

    /**
     * Determine quality variants based on source resolution.
     * Encodes only at 1080P and/or 4K — no unnecessary 720p downscale for HD sources.
     * For sub-1080p sources, encodes at 720p as the sole variant.
     */
    protected function getQualityVariants(int $sourceHeight, int $sourceWidth): array
    {
        $variants = [];

        // Sub-1080p source: encode at 720p only
        if ($sourceHeight < 1000) {
            $variants['720p'] = [
                'width' => 1280,
                'height' => 720,
                'bitrate' => '2800k',
                'maxrate' => '2996k',
                'bufsize' => '4200k',
                'audio_bitrate' => '128k',
                'bandwidth' => 2800000,
            ];

            return $variants;
        }

        // 1080p+ source: include 1080P (added first so HLS manifest lists it before 4K)
        $variants['1080p'] = [
            'width' => 1920,
            'height' => 1080,
            'bitrate' => '5000k',
            'maxrate' => '5350k',
            'bufsize' => '7500k',
            'audio_bitrate' => '192k',
            'bandwidth' => 5000000,
        ];

        // 4K source: also encode at 4K (lenient threshold for screen recordings)
        if ($sourceHeight >= 2000) {
            $variants['2160p'] = [
                'width' => 3840,
                'height' => 2160,
                'bitrate' => '14000k',
                'maxrate' => '14980k',
                'bufsize' => '21000k',
                'audio_bitrate' => '192k',
                'bandwidth' => 14000000,
            ];
        }

        return $variants;
    }

    /**
     * Generate HLS streams for all quality variants.
     */
    protected function generateHlsStreams(
        string $inputPath,
        string $hlsDir,
        array $variants,
        ?callable $progressCallback = null
    ): void {
        $totalVariants = count($variants);
        $currentVariant = 0;
        $baseProgress = 10;
        $progressRange = 85; // 10% to 95%

        foreach ($variants as $quality => $settings) {
            $outputPlaylist = $hlsDir.'/'.$quality.'.m3u8';
            $segmentPattern = $hlsDir.'/'.$quality.'_%03d.ts';

            // Build FFmpeg command for this variant
            $command = $this->buildFfmpegCommand(
                $inputPath,
                $outputPlaylist,
                $segmentPattern,
                $settings
            );

            Log::info("Generating HLS variant: {$quality}", [
                'command' => $command,
            ]);

            $output = [];
            $returnCode = 0;
            exec($command.' 2>&1', $output, $returnCode);

            if ($returnCode !== 0) {
                $outputText = implode("\n", $output);
                Log::error("FFmpeg HLS conversion failed for {$quality}", [
                    'output' => $outputText,
                    'return_code' => $returnCode,
                ]);
                throw new \Exception("HLS conversion failed for {$quality}: ".substr($outputText, -500));
            }

            $currentVariant++;

            if ($progressCallback) {
                $progress = $baseProgress + (int) (($currentVariant / $totalVariants) * $progressRange);
                $progressCallback($progress);
            }
        }
    }

    /**
     * Build FFmpeg command for HLS conversion.
     */
    protected function buildFfmpegCommand(
        string $inputPath,
        string $outputPlaylist,
        string $segmentPattern,
        array $settings
    ): string {
        return sprintf(
            '%s -y -threads 2 -i %s '.
            '-vf "scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2" '.
            '-c:v libx264 -preset slow -crf 23 -threads 2 '.
            '-b:v %s -maxrate %s -bufsize %s '.
            '-c:a aac -b:a %s -ac 2 '.
            '-hls_time 6 -hls_list_size 0 -hls_segment_type mpegts '.
            '-hls_segment_filename %s '.
            '-hls_playlist_type vod '.
            '-f hls %s',
            escapeshellarg($this->ffmpegPath),
            escapeshellarg($inputPath),
            $settings['width'],
            $settings['height'],
            $settings['width'],
            $settings['height'],
            $settings['bitrate'],
            $settings['maxrate'],
            $settings['bufsize'],
            $settings['audio_bitrate'],
            escapeshellarg($segmentPattern),
            escapeshellarg($outputPlaylist)
        );
    }

    /**
     * Create the master playlist that references all quality variants.
     */
    protected function createMasterPlaylist(string $hlsDir, array $variants): string
    {
        $masterPath = $hlsDir.'/master.m3u8';
        $content = "#EXTM3U\n#EXT-X-VERSION:3\n\n";

        foreach ($variants as $quality => $settings) {
            $resolution = $settings['width'].'x'.$settings['height'];
            $bandwidth = $settings['bandwidth'];

            $content .= "#EXT-X-STREAM-INF:BANDWIDTH={$bandwidth},RESOLUTION={$resolution},NAME=\"{$quality}\"\n";
            $content .= "{$quality}.m3u8\n\n";
        }

        file_put_contents($masterPath, $content);

        return $masterPath;
    }

    /**
     * Ensure a directory exists, creating it if necessary.
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (! is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * Delete HLS files for a video.
     */
    public function deleteHlsFiles(Video $video): void
    {
        $hlsDir = $this->hlsStoragePath.'/'.$video->id;

        if (is_dir($hlsDir)) {
            $files = glob($hlsDir.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($hlsDir);
        }
    }

    /**
     * Check if HLS files exist for a video.
     */
    public function hlsFilesExist(Video $video): bool
    {
        $masterPlaylist = $this->hlsStoragePath.'/'.$video->id.'/master.m3u8';

        return file_exists($masterPlaylist);
    }

    /**
     * Get the URL for the master playlist.
     * Returns API-based URL to ensure CORS headers are included.
     */
    public function getMasterPlaylistUrl(Video $video): ?string
    {
        if (! $this->hlsFilesExist($video)) {
            return null;
        }

        // Use API route for CORS support (cross-origin playback)
        return url("/api/share/video/{$video->share_token}/hls/master.m3u8");
    }

    /**
     * Get HLS directory path for a video.
     */
    public function getHlsDirectory(Video $video): string
    {
        return $this->hlsStoragePath.'/'.$video->id;
    }
}
