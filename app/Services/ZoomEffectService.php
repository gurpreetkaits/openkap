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
     * Generate the FFmpeg zoompan filter string from video zoom events.
     */
    public function generateZoomFilter(Video $video): ?string
    {
        $zoomSettings = $video->zoomSettings;

        if (! $zoomSettings || ! $zoomSettings->enabled) {
            return null;
        }

        $events = $zoomSettings->events;
        if (empty($events)) {
            return null;
        }

        $resolution = $zoomSettings->getResolution();
        $width = $resolution['width'];
        $height = $resolution['height'];

        // Get video FPS for frame calculations
        $fps = $this->getVideoFps($video) ?? 30;

        // Get zoom settings
        $zoomLevel = $zoomSettings->zoom_level;
        $zoomDurationMs = $zoomSettings->duration_ms;
        $holdDurationMs = 1000;

        // Filter to only enabled zoom events
        $enabledEvents = array_filter($events, fn ($e) => $e['zoom_enabled'] ?? true);

        if (empty($enabledEvents)) {
            return null;
        }

        // Build the zoompan filter expression
        $filter = $this->buildZoompanFilter(
            $enabledEvents,
            $width,
            $height,
            $fps,
            $zoomLevel,
            $zoomDurationMs,
            $holdDurationMs
        );

        return $filter;
    }

    /**
     * Build the zoompan filter expression.
     */
    protected function buildZoompanFilter(
        array $events,
        int $width,
        int $height,
        float $fps,
        float $zoomLevel,
        int $zoomDurationMs,
        int $holdDurationMs
    ): string {
        // Calculate frame durations
        $zoomFrames = (int) ceil(($zoomDurationMs / 1000) * $fps);
        $holdFrames = (int) ceil(($holdDurationMs / 1000) * $fps);
        $totalEventFrames = ($zoomFrames * 2) + $holdFrames;

        // Build zoom expression - starts at 1 (no zoom), transitions to zoomLevel, holds, transitions back
        $zoomExprs = [];
        $xExprs = [];
        $yExprs = [];

        foreach ($events as $index => $event) {
            $eventData = ZoomEventData::fromArray($event);

            if (! $eventData->zoom_enabled) {
                continue;
            }

            // Calculate frame number for this event
            $startFrame = (int) (($eventData->timestamp_ms / 1000) * $fps);
            $zoomInEnd = $startFrame + $zoomFrames;
            $holdEnd = $zoomInEnd + $holdFrames;
            $zoomOutEnd = $holdEnd + $zoomFrames;

            // Calculate normalized position (0-1)
            $targetX = $eventData->x ?? ($width / 2);
            $targetY = $eventData->y ?? ($height / 2);

            // For keyboard events, use center of screen or last click position
            if ($eventData->isKeyboardEvent()) {
                $targetX = $width / 2;
                $targetY = $height / 2;
            }

            // Calculate the zoom center offset
            $normX = $targetX / $width;
            $normY = $targetY / $height;

            // Build conditional expressions for this event
            // Zoom in: linear interpolation from 1 to zoomLevel
            $zoomExprs[] = sprintf(
                'between(on,%d,%d)*lerp(1,%s,(on-%d)/%d)',
                $startFrame,
                $zoomInEnd,
                $zoomLevel,
                $startFrame,
                $zoomFrames
            );

            // Hold at zoom level
            $zoomExprs[] = sprintf(
                'between(on,%d,%d)*%s',
                $zoomInEnd,
                $holdEnd,
                $zoomLevel
            );

            // Zoom out: linear interpolation from zoomLevel back to 1
            $zoomExprs[] = sprintf(
                'between(on,%d,%d)*lerp(%s,1,(on-%d)/%d)',
                $holdEnd,
                $zoomOutEnd,
                $zoomLevel,
                $holdEnd,
                $zoomFrames
            );

            // X position (where to zoom to)
            $xOffset = $normX - 0.5;
            $xExprs[] = sprintf(
                'between(on,%d,%d)*(iw/2+iw*%s*(zoom-1)/zoom)',
                $startFrame,
                $zoomOutEnd,
                $xOffset
            );

            // Y position (where to zoom to)
            $yOffset = $normY - 0.5;
            $yExprs[] = sprintf(
                'between(on,%d,%d)*(ih/2+ih*%s*(zoom-1)/zoom)',
                $startFrame,
                $zoomOutEnd,
                $yOffset
            );
        }

        // Combine all expressions with default values
        $zoomExpr = $this->combineExpressionsWithDefault($zoomExprs, '1');
        $xExpr = $this->combineExpressionsWithDefault($xExprs, 'iw/2-(iw/zoom/2)');
        $yExpr = $this->combineExpressionsWithDefault($yExprs, 'ih/2-(ih/zoom/2)');

        // Build the full zoompan filter
        $filter = sprintf(
            "zoompan=z='%s':x='%s':y='%s':d=1:s=%dx%d:fps=%d",
            $zoomExpr,
            $xExpr,
            $yExpr,
            $width,
            $height,
            (int) $fps
        );

        return $filter;
    }

    /**
     * Combine multiple expressions with a default fallback.
     */
    protected function combineExpressionsWithDefault(array $exprs, string $default): string
    {
        if (empty($exprs)) {
            return $default;
        }

        $combined = implode('+', $exprs);

        return sprintf(
            'if((%s)>0,(%s),%s)',
            implode('+', array_map(fn ($e) => "({$e})!=0", $exprs)),
            $combined,
            $default
        );
    }

    /**
     * Apply zoom effects to a video file.
     */
    public function applyZoomEffects(
        Video $video,
        string $inputPath,
        string $outputPath,
        ?callable $progressCallback = null
    ): bool {
        $filter = $this->generateZoomFilter($video);

        if (! $filter) {
            Log::info('No zoom filter to apply', ['video_id' => $video->id]);

            return false;
        }

        Log::info('Applying zoom effects', [
            'video_id' => $video->id,
            'filter' => $filter,
        ]);

        if ($progressCallback) {
            $progressCallback(10);
        }

        // Build FFmpeg command
        $command = sprintf(
            '%s -y -i %s -vf "%s" -c:v libx264 -preset medium -crf 20 -c:a copy %s 2>&1',
            escapeshellarg($this->ffmpegPath),
            escapeshellarg($inputPath),
            $filter,
            escapeshellarg($outputPath)
        );

        Log::info('FFmpeg zoom command', ['command' => $command]);

        if ($progressCallback) {
            $progressCallback(20);
        }

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        $outputText = implode("\n", $output);

        if ($returnCode !== 0) {
            Log::error('FFmpeg zoom processing failed', [
                'video_id' => $video->id,
                'return_code' => $returnCode,
                'output' => $outputText,
            ]);
            throw new \Exception('Zoom effect processing failed: '.substr($outputText, -500));
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
