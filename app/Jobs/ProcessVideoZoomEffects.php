<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessVideoZoomEffects implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $timeout = 1800; // 30 minutes max for 4K processing

    protected Video $video;

    /**
     * Create a new job instance.
     */
    public function __construct(Video $video)
    {
        $this->video = $video;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("[ProcessVideoZoomEffects] Starting zoom processing for video {$this->video->id}");

        // Check if video has click events
        if (! $this->video->click_events || count($this->video->click_events) === 0) {
            Log::info('[ProcessVideoZoomEffects] No click events, skipping zoom processing');
            $this->video->update(['processing_status' => 'processed']);

            return;
        }

        try {
            // Update status to processing
            $this->video->update(['processing_status' => 'processing']);

            // Get original video path
            $originalMedia = $this->video->getFirstMedia('videos');
            if (! $originalMedia) {
                throw new \Exception('Original video not found');
            }

            $originalPath = $originalMedia->getPath();
            $tempDir = storage_path('app/temp');
            $outputPath = "{$tempDir}/processed_video_{$this->video->id}.mp4";

            // Ensure temp directory exists
            if (! is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            Log::info("[ProcessVideoZoomEffects] Original: {$originalPath}, Output: {$outputPath}");

            // Get original video dimensions to preserve quality
            $dimensions = $this->getVideoDimensions($originalPath);
            $width = $dimensions['width'];
            $height = $dimensions['height'];
            $fps = $dimensions['fps'];

            Log::info("[ProcessVideoZoomEffects] Original dimensions: {$width}x{$height} @ {$fps}fps");

            // Build FFmpeg zoom filter complex from click events (preserving original resolution)
            $filterComplex = $this->buildZoomFilterComplex($this->video->click_events, $this->video->duration, $width, $height, $fps);

            // Try hardware encoding first (VideoToolbox on Mac), fallback to fast software encoding
            $command = $this->buildFfmpegCommand($originalPath, $filterComplex, $outputPath, $width, $height);

            Log::info('[ProcessVideoZoomEffects] Filter complex: '.$filterComplex);
            Log::info('[ProcessVideoZoomEffects] Executing FFmpeg command');

            // Use proc_open to avoid shell interpretation
            $process = proc_open(
                $command,
                [
                    1 => ['pipe', 'w'],  // stdout
                    2 => ['pipe', 'w'],  // stderr
                ],
                $pipes
            );

            if (is_resource($process)) {
                $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $returnCode = proc_close($process);
            } else {
                throw new \Exception('Failed to start FFmpeg process');
            }

            if ($returnCode !== 0) {
                throw new \Exception('FFmpeg failed: '.$output);
            }

            // Save processed video to media library
            $this->video->addMedia($outputPath)
                ->usingFileName("video_{$this->video->id}_zoomed.mp4")
                ->toMediaCollection('processed_videos');

            // Update video status
            $this->video->update([
                'processing_status' => 'processed',
                'processed_video_path' => "processed_videos/video_{$this->video->id}_zoomed.mp4",
            ]);

            // Clean up temp file
            @unlink($outputPath);

            Log::info("[ProcessVideoZoomEffects] Zoom processing completed for video {$this->video->id}");

        } catch (\Exception $e) {
            Log::error("[ProcessVideoZoomEffects] Error processing video {$this->video->id}: ".$e->getMessage());

            $this->video->update([
                'processing_status' => 'failed',
            ]);

            throw $e;
        }
    }

    /**
     * Get video dimensions using ffprobe
     */
    protected function getVideoDimensions(string $videoPath): array
    {
        $command = [
            'ffprobe',
            '-v', 'error',
            '-select_streams', 'v:0',
            '-show_entries', 'stream=width,height,r_frame_rate',
            '-of', 'json',
            $videoPath,
        ];

        $process = proc_open(
            $command,
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );

        $output = '';
        if (is_resource($process)) {
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        }

        $data = json_decode($output, true);
        $stream = $data['streams'][0] ?? [];

        $width = $stream['width'] ?? 1920;
        $height = $stream['height'] ?? 1080;

        // Parse frame rate (can be "30/1" or "29.97")
        $fpsStr = $stream['r_frame_rate'] ?? '30/1';
        if (str_contains($fpsStr, '/')) {
            [$num, $den] = explode('/', $fpsStr);
            $fps = $den > 0 ? round((int) $num / (int) $den) : 30;
        } else {
            $fps = (int) round((float) $fpsStr);
        }

        // Ensure fps is reasonable
        $fps = max(24, min(60, $fps));

        return [
            'width' => $width,
            'height' => $height,
            'fps' => $fps,
        ];
    }

    /**
     * Build FFmpeg filter complex for zoom effects based on click events
     * Preserves original video resolution for 4K quality
     */
    protected function buildZoomFilterComplex(array $clickEvents, int $videoDuration, int $width, int $height, int $fps): string
    {
        // Sort clicks by timestamp
        usort($clickEvents, fn ($a, $b) => $a['timestamp'] <=> $b['timestamp']);

        $zoomDuration = 2.0; // Total zoom duration (1.0s ease-in + 1.0s ease-out) - slower for smoother effect
        $zoomLevel = 2.0; // 2x zoom at peak

        // Deduplicate clicks - only filter clicks that are within 0.3s of each other (rapid double-clicks)
        // This allows most clicks to be processed while filtering accidental double-clicks
        $uniqueClicks = [];
        $lastClickTime = -999;
        foreach ($clickEvents as $click) {
            $clickTime = $click['timestamp'];
            if (($clickTime - $lastClickTime) >= 0.3) {
                $uniqueClicks[] = $click;
                $lastClickTime = $clickTime;
            }
        }

        Log::info('[ProcessVideoZoomEffects] Processing '.count($uniqueClicks).' clicks (from '.count($clickEvents).' total)');

        if (empty($uniqueClicks)) {
            Log::warning('[ProcessVideoZoomEffects] No valid clicks after deduplication');

            return 'null';
        }

        // Build zoom expressions for each click with smooth zoom in/out
        $zoomParts = [];
        $xParts = [];
        $yParts = [];

        foreach ($uniqueClicks as $click) {
            $clickTime = $click['timestamp'];
            $halfDuration = $zoomDuration / 2;

            // Zoom starts 0.5s before click, peaks at click, ends 0.5s after
            $startTime = max(0, $clickTime - $halfDuration);
            $endTime = min($clickTime + $halfDuration, $videoDuration);
            $peakTime = $clickTime;

            // Skip if not enough time for zoom (need at least 0.5s for a visible effect)
            if (($endTime - $startTime) < 0.5) {
                Log::warning("[ProcessVideoZoomEffects] Skipping click at {$clickTime}s - insufficient duration");

                continue;
            }

            $x = $click['normalizedX'] ?? 0.5;
            $y = $click['normalizedY'] ?? 0.5;

            Log::info("[ProcessVideoZoomEffects] Adding zoom at {$clickTime}s, position ({$x}, {$y})");

            // Smooth zoom in/out using sine curve for natural easing
            // Formula: 1 + (zoomLevel-1) * sin(PI * (in_time - startTime) / duration)
            // This creates smooth: 1 → zoomLevel → 1 transition
            $zoomAmount = $zoomLevel - 1;
            $duration = $endTime - $startTime;
            $zoomParts[] = sprintf(
                'if(gte(in_time,%.2f)*lte(in_time,%.2f),1+%.1f*sin(PI*(in_time-%.2f)/%.2f),0)',
                $startTime,
                $endTime,
                $zoomAmount,
                $startTime,
                $duration
            );

            // X position for this click's zoom period
            $xParts[] = sprintf(
                'if(gte(in_time,%.1f)*lte(in_time,%.1f),iw*%.4f-iw/zoom/2,0)',
                $startTime,
                $endTime,
                $x
            );

            // Y position for this click's zoom period
            $yParts[] = sprintf(
                'if(gte(in_time,%.1f)*lte(in_time,%.1f),ih*%.4f-ih/zoom/2,0)',
                $startTime,
                $endTime,
                $y
            );
        }

        if (empty($zoomParts)) {
            Log::warning('[ProcessVideoZoomEffects] No valid zoom conditions');

            return 'null';
        }

        // Combine all zoom expressions - use max to get the active zoom
        $zoomExpr = 'max(1,'.implode('+', $zoomParts).')';

        // Combine x/y expressions - sum of conditional positions (only one is active at a time)
        // When no zoom is active, default to center
        $xExpr = 'if(gt(zoom,1),'.implode('+', $xParts).',iw/2-iw/zoom/2)';
        $yExpr = 'if(gt(zoom,1),'.implode('+', $yParts).',ih/2-ih/zoom/2)';

        Log::info('[ProcessVideoZoomEffects] Generated zoom for '.count($zoomParts).' click(s)');
        Log::info('[ProcessVideoZoomEffects] Zoom expression length: '.strlen($zoomExpr));

        // Build filter chain preserving original resolution
        // Using zoompan with original dimensions to maintain 4K quality
        return sprintf(
            "zoompan=z='%s':x='%s':y='%s':d=1:s=%dx%d:fps=%d",
            $zoomExpr,
            $xExpr,
            $yExpr,
            $width,
            $height,
            $fps
        );
    }

    /**
     * Build optimized FFmpeg command with hardware acceleration when available
     * Uses higher quality settings for 4K video preservation
     */
    protected function buildFfmpegCommand(string $inputPath, string $filterComplex, string $outputPath, int $width, int $height): array
    {
        $hwEncoder = $this->getHardwareEncoder();

        // Determine quality settings based on resolution
        // 4K (3840x2160) needs higher bitrate, 1080p needs less
        $is4K = $width >= 3840 || $height >= 2160;
        $isQHD = $width >= 2560 || $height >= 1440;

        if ($hwEncoder) {
            // Hardware encoding bitrates
            $bitrate = $is4K ? '35M' : ($isQHD ? '20M' : '10M');
            Log::info("[ProcessVideoZoomEffects] Using hardware encoding ({$hwEncoder}) at {$bitrate}");

            return [
                'ffmpeg',
                '-threads', '0',
                '-i', $inputPath,
                '-filter_complex', $filterComplex,
                '-c:v', $hwEncoder,
                '-b:v', $bitrate,
                '-maxrate', $is4K ? '45M' : ($isQHD ? '25M' : '15M'),
                '-bufsize', $is4K ? '70M' : ($isQHD ? '40M' : '20M'),
                '-profile:v', 'high',
                '-c:a', 'aac',
                '-b:a', '192k',
                '-movflags', '+faststart',
                '-y', $outputPath,
            ];
        }

        // For screen recordings, use bitrate-based encoding to prevent over-compression
        // CRF tends to over-compress static screen content
        // 4K: ~25-35 Mbps, QHD: ~15-20 Mbps, 1080p: ~8-12 Mbps
        $bitrate = $is4K ? '30M' : ($isQHD ? '18M' : '10M');
        $maxrate = $is4K ? '40M' : ($isQHD ? '24M' : '14M');
        $bufsize = $is4K ? '60M' : ($isQHD ? '36M' : '20M');

        Log::info("[ProcessVideoZoomEffects] Using software encoding (libx264) at {$bitrate}");

        return [
            'ffmpeg',
            '-threads', '0',
            '-i', $inputPath,
            '-filter_complex', $filterComplex,
            '-c:v', 'libx264',
            '-preset', 'medium',
            '-b:v', $bitrate,
            '-maxrate', $maxrate,
            '-bufsize', $bufsize,
            '-profile:v', 'high',
            '-level', '5.1',      // Required for 4K
            '-pix_fmt', 'yuv420p',
            '-c:a', 'aac',
            '-b:a', '192k',
            '-movflags', '+faststart',
            '-y', $outputPath,
        ];
    }

    /**
     * Get the best available hardware encoder, or null if none available
     * Note: In Docker containers, hardware encoders may be listed but not usable
     * without proper GPU passthrough. We test them before returning.
     */
    protected function getHardwareEncoder(): ?string
    {
        // In Docker, hardware encoders are often not available even if listed
        // For reliability, we'll only use VideoToolbox (native macOS) since
        // NVENC/VAAPI require GPU passthrough which isn't configured
        $process = proc_open(
            ['ffmpeg', '-hide_banner', '-encoders'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );

        if (is_resource($process)) {
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);

            // Only use VideoToolbox as it's reliable on macOS
            // NVENC/VAAPI require GPU passthrough which Docker doesn't have by default
            if (str_contains($output, 'h264_videotoolbox')) {
                return 'h264_videotoolbox';
            }
        }

        // Fall back to software encoding - more reliable in containers
        return null;
    }
}
