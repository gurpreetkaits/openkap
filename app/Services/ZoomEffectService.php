<?php

namespace App\Services;

use App\Data\ZoomEventData;
use App\Models\Video;
use Illuminate\Support\Facades\Log;

class ZoomEffectService
{
    protected string $ffmpegPath;

    protected string $ffprobePath;

    public function __construct()
    {
        $this->ffmpegPath = config('media-library.ffmpeg_path');
        $this->ffprobePath = config('media-library.ffprobe_path');
    }

    /**
     * Apply zoom effects to a video using scale+crop approach.
     * This is more reliable than zoompan for video files.
     */
    public function applyZoomEffects(
        Video $video,
        string $inputPath,
        string $outputPath,
        ?callable $progressCallback = null
    ): bool {
        $zoomSettings = $video->zoomSettings;

        if (! $zoomSettings || ! $zoomSettings->enabled) {
            Log::info('No zoom settings enabled', ['video_id' => $video->id]);

            return false;
        }

        $events = $zoomSettings->events;
        if (empty($events)) {
            Log::info('No zoom events', ['video_id' => $video->id]);

            return false;
        }

        // Get click events only
        $clickEvents = array_values(array_filter($events, fn ($e) => ($e['type'] ?? '') === 'click'));

        if (empty($clickEvents)) {
            Log::info('No click events for zoom', ['video_id' => $video->id]);

            return false;
        }

        // Limit to 3 zoom events for performance
        $clickEvents = array_slice($clickEvents, 0, 3);

        // Get actual video dimensions from the file
        $dimensions = $this->getVideoDimensionsFromFile($inputPath);
        $width = $dimensions['width'];
        $height = $dimensions['height'];
        $fps = $this->getVideoFpsFromFile($inputPath) ?? 30;

        // Ensure dimensions are even (required by libx264)
        $width = $width + ($width % 2);
        $height = $height + ($height % 2);

        $zoomLevel = $zoomSettings->zoom_level;
        $zoomDurationMs = $zoomSettings->duration_ms;
        $holdDurationMs = 1500;

        Log::info('Applying zoom effects', [
            'video_id' => $video->id,
            'input' => $inputPath,
            'events' => count($clickEvents),
            'zoom_level' => $zoomLevel,
            'dimensions' => "{$width}x{$height}",
            'fps' => $fps,
        ]);

        if ($progressCallback) {
            $progressCallback(10);
        }

        // Build zoom segments
        $segments = $this->buildZoomSegments(
            $clickEvents,
            $width,
            $height,
            $fps,
            $zoomLevel,
            $zoomDurationMs,
            $holdDurationMs
        );

        if (empty($segments)) {
            Log::info('No valid zoom segments', ['video_id' => $video->id]);

            return false;
        }

        if ($progressCallback) {
            $progressCallback(20);
        }

        // Use segment-based approach: split video, zoom segments, concatenate
        $success = $this->processWithSegments($video, $inputPath, $outputPath, $segments, $width, $height, $fps, $progressCallback);

        if (! $success) {
            throw new \Exception('Zoom segment processing failed');
        }

        if (! file_exists($outputPath)) {
            throw new \Exception('Zoom output file was not created');
        }

        $outputSize = filesize($outputPath);
        if ($outputSize < 1000) {
            throw new \Exception("Zoom output file is too small: {$outputSize} bytes");
        }

        if ($progressCallback) {
            $progressCallback(100);
        }

        Log::info('Zoom effects applied successfully', [
            'video_id' => $video->id,
            'output_size' => $outputSize,
        ]);

        return true;
    }

    /**
     * Process video by splitting into segments, applying zoom to specific parts, and concatenating.
     */
    protected function processWithSegments(
        Video $video,
        string $inputPath,
        string $outputPath,
        array $segments,
        int $width,
        int $height,
        float $fps,
        ?callable $progressCallback = null
    ): bool {
        $tempDir = sys_get_temp_dir().'/zoom_'.$video->id.'_'.time();
        mkdir($tempDir, 0755, true);

        try {
            $partFiles = [];
            $partIndex = 0;
            $lastEndTime = 0;

            foreach ($segments as $idx => $segment) {
                $startTime = $segment['start_time'];
                $endTime = $segment['end_time'];

                // Part before zoom (if any gap)
                if ($startTime > $lastEndTime + 0.1) {
                    $beforeFile = "{$tempDir}/part_{$partIndex}.mp4";
                    if ($this->extractSegment($inputPath, $beforeFile, $lastEndTime, $startTime, $width, $height)) {
                        $partFiles[] = $beforeFile;
                        $partIndex++;
                    }
                }

                // Zoomed part
                $zoomFile = "{$tempDir}/part_{$partIndex}.mp4";
                if ($this->createZoomedSegment($inputPath, $zoomFile, $segment, $width, $height, $fps)) {
                    $partFiles[] = $zoomFile;
                    $partIndex++;
                }

                $lastEndTime = $endTime;

                if ($progressCallback) {
                    $progress = 20 + (int) (($idx + 1) / count($segments) * 60);
                    $progressCallback($progress);
                }
            }

            // Get video duration for final segment
            $duration = $this->getVideoDuration($inputPath);

            // Part after last zoom
            if ($duration > 0 && $lastEndTime < $duration - 0.1) {
                $afterFile = "{$tempDir}/part_{$partIndex}.mp4";
                if ($this->extractSegment($inputPath, $afterFile, $lastEndTime, $duration, $width, $height)) {
                    $partFiles[] = $afterFile;
                }
            }

            if (empty($partFiles)) {
                Log::error('No parts created', ['video_id' => $video->id]);

                return false;
            }

            // Concatenate all parts
            $success = $this->concatenateParts($partFiles, $outputPath, $tempDir);

            return $success;

        } finally {
            // Clean up temp files
            $files = glob("{$tempDir}/*");
            foreach ($files as $file) {
                @unlink($file);
            }
            @rmdir($tempDir);
        }
    }

    /**
     * Extract a segment of video without modification.
     */
    protected function extractSegment(string $inputPath, string $outputPath, float $start, float $end, int $width, int $height): bool
    {
        $duration = $end - $start;
        if ($duration <= 0) {
            return false;
        }

        $command = sprintf(
            '%s -y -ss %.3f -i %s -t %.3f -vf "scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2,setsar=1" -c:v libx264 -preset ultrafast -crf 18 -pix_fmt yuv420p -c:a aac -b:a 128k %s 2>&1',
            escapeshellarg($this->ffmpegPath),
            $start,
            escapeshellarg($inputPath),
            $duration,
            $width, $height,
            $width, $height,
            escapeshellarg($outputPath)
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::warning('Extract segment failed', [
                'start' => $start,
                'end' => $end,
                'output' => implode("\n", array_slice($output, -10)),
            ]);

            return false;
        }

        return file_exists($outputPath) && filesize($outputPath) > 0;
    }

    /**
     * Create a zoomed segment using scale + crop approach.
     */
    protected function createZoomedSegment(
        string $inputPath,
        string $outputPath,
        array $segment,
        int $width,
        int $height,
        float $fps
    ): bool {
        $startTime = $segment['start_time'];
        $endTime = $segment['end_time'];
        $duration = $endTime - $startTime;
        $clickX = $segment['click_x'];
        $clickY = $segment['click_y'];
        $zoomLevel = $segment['zoom_level'];
        $zoomDuration = $segment['zoom_duration'];
        $zoomInEnd = $segment['zoom_in_end'] - $startTime;
        $holdEnd = $segment['hold_end'] - $startTime;

        // Scale factor for the zoomed portion
        $scaledWidth = (int) ($width * $zoomLevel);
        $scaledHeight = (int) ($height * $zoomLevel);

        // Make dimensions even
        $scaledWidth = $scaledWidth + ($scaledWidth % 2);
        $scaledHeight = $scaledHeight + ($scaledHeight % 2);

        // Calculate crop position to center on click point
        // When fully zoomed, we crop from the scaled video
        $normX = $clickX / $width;
        $normY = $clickY / $height;

        // Crop position in scaled coordinates
        $cropX = (int) max(0, ($scaledWidth * $normX) - ($width / 2));
        $cropY = (int) max(0, ($scaledHeight * $normY) - ($height / 2));

        // Clamp to valid range
        $cropX = min($cropX, $scaledWidth - $width);
        $cropY = min($cropY, $scaledHeight - $height);

        // Build filter with animated zoom using expressions
        // t = time in seconds relative to segment start
        // Zoom in: 0 to zoomDuration
        // Hold: zoomDuration to holdEnd
        // Zoom out: holdEnd to duration

        $zoomInDur = max(0.1, $zoomDuration);
        $holdDur = max(0.1, $holdEnd - $zoomDuration);
        $zoomOutDur = max(0.1, $duration - $holdEnd);

        // Current zoom as function of time
        // Scale width = base_width * current_zoom
        // We need to express scale factor that changes over time
        $zoomExpr = sprintf(
            'if(lt(t,%.3f),1+(%.3f)*t/%.3f,if(lt(t,%.3f),%.3f,%.3f-(%.3f)*(t-%.3f)/%.3f))',
            $zoomInDur,
            $zoomLevel - 1,
            $zoomInDur,
            $holdEnd,
            $zoomLevel,
            $zoomLevel,
            $zoomLevel - 1,
            $holdEnd,
            $zoomOutDur
        );

        // Width and height expressions
        $wExpr = "iw*({$zoomExpr})";
        $hExpr = "ih*({$zoomExpr})";

        // Crop position expressions - pan to click location
        // When zoom=1, crop from center. When zoom>1, pan towards click.
        $cropXExpr = sprintf(
            '(out_w*%.4f-ow/2)*(((%s)-1)/(%.3f-1))',
            $normX,
            $zoomExpr,
            $zoomLevel
        );
        $cropYExpr = sprintf(
            '(out_h*%.4f-oh/2)*(((%s)-1)/(%.3f-1))',
            $normY,
            $zoomExpr,
            $zoomLevel
        );

        // Clamp crop position
        $cropXExpr = "max(0,min({$cropXExpr},out_w-ow))";
        $cropYExpr = "max(0,min({$cropYExpr},out_h-oh))";

        // Build the filter chain: scale up -> crop -> scale to output size
        // Using scale with eval=frame for per-frame evaluation
        $filter = sprintf(
            "scale=w='%s':h='%s':eval=frame,crop=w=%d:h=%d:x='%s':y='%s':exact=1,scale=%d:%d:flags=lanczos,setsar=1",
            $wExpr,
            $hExpr,
            $width,
            $height,
            $cropXExpr,
            $cropYExpr,
            $width,
            $height
        );

        // Write filter to file to avoid escaping issues
        $filterFile = sys_get_temp_dir().'/zoom_segment_filter.txt';
        file_put_contents($filterFile, $filter);

        $command = sprintf(
            '%s -y -ss %.3f -i %s -t %.3f -filter_complex_script %s -c:v libx264 -preset ultrafast -crf 18 -pix_fmt yuv420p -c:a aac -b:a 128k %s 2>&1',
            escapeshellarg($this->ffmpegPath),
            $startTime,
            escapeshellarg($inputPath),
            $duration,
            escapeshellarg($filterFile),
            escapeshellarg($outputPath)
        );

        Log::info('Zoom segment command', [
            'filter' => $filter,
            'command' => $command,
        ]);

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        @unlink($filterFile);

        if ($returnCode !== 0) {
            Log::error('Zoom segment failed', [
                'segment' => $segment,
                'output' => implode("\n", array_slice($output, -20)),
            ]);

            // Try simpler fallback: static zoom at max level
            return $this->createSimpleZoomedSegment($inputPath, $outputPath, $segment, $width, $height);
        }

        return file_exists($outputPath) && filesize($outputPath) > 0;
    }

    /**
     * Create a simple zoomed segment with static zoom (fallback).
     */
    protected function createSimpleZoomedSegment(
        string $inputPath,
        string $outputPath,
        array $segment,
        int $width,
        int $height
    ): bool {
        $startTime = $segment['start_time'];
        $duration = $segment['end_time'] - $startTime;
        $clickX = $segment['click_x'];
        $clickY = $segment['click_y'];
        $zoomLevel = $segment['zoom_level'];

        // Simpler approach: static zoom centered on click
        $scaledWidth = (int) ($width * $zoomLevel);
        $scaledHeight = (int) ($height * $zoomLevel);
        $scaledWidth = $scaledWidth + ($scaledWidth % 2);
        $scaledHeight = $scaledHeight + ($scaledHeight % 2);

        $normX = $clickX / $width;
        $normY = $clickY / $height;

        $cropX = (int) max(0, min(($scaledWidth * $normX) - ($width / 2), $scaledWidth - $width));
        $cropY = (int) max(0, min(($scaledHeight * $normY) - ($height / 2), $scaledHeight - $height));

        $filter = sprintf(
            'scale=%d:%d,crop=%d:%d:%d:%d,scale=%d:%d,setsar=1',
            $scaledWidth, $scaledHeight,
            $width, $height, $cropX, $cropY,
            $width, $height
        );

        $command = sprintf(
            '%s -y -ss %.3f -i %s -t %.3f -vf "%s" -c:v libx264 -preset ultrafast -crf 18 -pix_fmt yuv420p -c:a aac -b:a 128k %s 2>&1',
            escapeshellarg($this->ffmpegPath),
            $startTime,
            escapeshellarg($inputPath),
            $duration,
            $filter,
            escapeshellarg($outputPath)
        );

        Log::info('Simple zoom fallback', ['command' => $command]);

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('Simple zoom also failed', [
                'output' => implode("\n", array_slice($output, -10)),
            ]);

            return false;
        }

        return file_exists($outputPath) && filesize($outputPath) > 0;
    }

    /**
     * Concatenate video parts into final output.
     */
    protected function concatenateParts(array $partFiles, string $outputPath, string $tempDir): bool
    {
        if (count($partFiles) === 1) {
            // Just copy the single file
            copy($partFiles[0], $outputPath);

            return file_exists($outputPath);
        }

        // Create concat list file
        $listFile = "{$tempDir}/concat_list.txt";
        $listContent = '';
        foreach ($partFiles as $file) {
            $listContent .= "file '".addslashes($file)."'\n";
        }
        file_put_contents($listFile, $listContent);

        $command = sprintf(
            '%s -y -f concat -safe 0 -i %s -c copy %s 2>&1',
            escapeshellarg($this->ffmpegPath),
            escapeshellarg($listFile),
            escapeshellarg($outputPath)
        );

        Log::info('Concat command', ['command' => $command]);

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::error('Concat failed, trying re-encode', [
                'output' => implode("\n", array_slice($output, -10)),
            ]);

            // Fallback: re-encode during concat
            $command = sprintf(
                '%s -y -f concat -safe 0 -i %s -c:v libx264 -preset ultrafast -crf 18 -c:a aac %s 2>&1',
                escapeshellarg($this->ffmpegPath),
                escapeshellarg($listFile),
                escapeshellarg($outputPath)
            );

            exec($command, $output, $returnCode);
        }

        return file_exists($outputPath) && filesize($outputPath) > 0;
    }

    /**
     * Build zoom segment data from click events.
     */
    protected function buildZoomSegments(
        array $clickEvents,
        int $width,
        int $height,
        float $fps,
        float $zoomLevel,
        int $zoomDurationMs,
        int $holdDurationMs
    ): array {
        $segments = [];
        $lastEndTime = -1;

        $zoomDurationSec = $zoomDurationMs / 1000;
        $holdDurationSec = $holdDurationMs / 1000;
        $totalEventDuration = ($zoomDurationSec * 2) + $holdDurationSec;

        foreach ($clickEvents as $click) {
            $clickData = ZoomEventData::fromArray($click);
            $startTime = $clickData->timestamp_ms / 1000;

            // Skip if overlaps with previous
            if ($startTime < $lastEndTime + 0.5) {
                continue;
            }

            // Calculate click position (clamp to valid range)
            $clickX = min(max($clickData->x ?? ($width / 2), 0), $width);
            $clickY = min(max($clickData->y ?? ($height / 2), 0), $height);

            $endTime = $startTime + $totalEventDuration;
            $lastEndTime = $endTime;

            $segments[] = [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'zoom_in_end' => $startTime + $zoomDurationSec,
                'hold_end' => $startTime + $zoomDurationSec + $holdDurationSec,
                'click_x' => $clickX,
                'click_y' => $clickY,
                'zoom_level' => $zoomLevel,
                'zoom_duration' => $zoomDurationSec,
            ];
        }

        return $segments;
    }

    /**
     * Get video duration using ffprobe.
     */
    protected function getVideoDuration(string $path): float
    {
        $command = sprintf(
            '%s -v quiet -show_entries format=duration -of csv=p=0 %s',
            escapeshellarg($this->ffprobePath),
            escapeshellarg($path)
        );

        $output = [];
        exec($command, $output);

        return (float) ($output[0] ?? 0);
    }

    /**
     * Get video dimensions from file.
     */
    protected function getVideoDimensionsFromFile(string $path): array
    {
        $command = sprintf(
            '%s -v quiet -select_streams v:0 -show_entries stream=width,height -of json %s',
            escapeshellarg($this->ffprobePath),
            escapeshellarg($path)
        );

        $output = [];
        exec($command, $output);
        $json = implode('', $output);
        $data = json_decode($json, true);

        $stream = $data['streams'][0] ?? [];

        return [
            'width' => (int) ($stream['width'] ?? 1920),
            'height' => (int) ($stream['height'] ?? 1080),
        ];
    }

    /**
     * Get video FPS from file.
     */
    protected function getVideoFpsFromFile(string $path): ?float
    {
        $command = sprintf(
            '%s -v quiet -select_streams v:0 -show_entries stream=r_frame_rate -of csv=p=0 %s',
            escapeshellarg($this->ffprobePath),
            escapeshellarg($path)
        );

        $output = [];
        exec($command, $output);

        if (empty($output[0])) {
            return null;
        }

        $parts = explode('/', trim($output[0]));
        if (count($parts) === 2 && $parts[1] > 0) {
            return (float) $parts[0] / (float) $parts[1];
        }

        return (float) $parts[0];
    }

    /**
     * Get video FPS using ffprobe.
     */
    protected function getVideoFps(Video $video): ?float
    {
        $media = $video->getFirstMedia('videos');
        if (! $media) {
            return null;
        }

        $inputPath = $media->getPath();

        $command = sprintf(
            '%s -v quiet -select_streams v:0 -show_entries stream=r_frame_rate -of csv=p=0 %s',
            escapeshellarg($this->ffprobePath),
            escapeshellarg($inputPath)
        );

        $output = [];
        exec($command, $output);

        if (empty($output[0])) {
            return null;
        }

        // Parse frame rate (e.g., "30/1" or "30000/1001")
        $parts = explode('/', trim($output[0]));
        if (count($parts) === 2 && $parts[1] > 0) {
            return (float) $parts[0] / (float) $parts[1];
        }

        return (float) $parts[0];
    }

    /**
     * Get video dimensions using ffprobe.
     */
    public function getVideoDimensions(Video $video): array
    {
        $media = $video->getFirstMedia('videos');
        if (! $media) {
            return ['width' => 1920, 'height' => 1080];
        }

        $inputPath = $media->getPath();

        $command = sprintf(
            '%s -v quiet -select_streams v:0 -show_entries stream=width,height -of json %s',
            escapeshellarg($this->ffprobePath),
            escapeshellarg($inputPath)
        );

        $output = [];
        exec($command, $output);
        $json = implode('', $output);
        $data = json_decode($json, true);

        $stream = $data['streams'][0] ?? [];

        return [
            'width' => (int) ($stream['width'] ?? 1920),
            'height' => (int) ($stream['height'] ?? 1080),
        ];
    }
}
