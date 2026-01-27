<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ApplyBlurJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 7200; // 2 hours

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Video $video
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $video = $this->video->fresh();

        Log::info('Starting blur effect application', [
            'video_id' => $video->id,
            'blur_region' => $video->blur_region,
            'start_time' => $video->blur_start_time,
            'end_time' => $video->blur_end_time,
        ]);

        // Get the current media
        $media = $video->getFirstMedia('videos');

        if (! $media) {
            Log::error('No media found for video', ['video_id' => $video->id]);
            $this->markAsFailed($video, 'No media file found');

            return;
        }

        $inputPath = $media->getPath();

        if (! file_exists($inputPath)) {
            Log::error('Video file not found', ['video_id' => $video->id, 'path' => $inputPath]);
            $this->markAsFailed($video, 'Video file not found');

            return;
        }

        // Mark as processing
        $video->update([
            'blur_status' => 'processing',
            'blur_progress' => 10,
        ]);

        // Prepare output path
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $extension = pathinfo($inputPath, PATHINFO_EXTENSION);
        $outputPath = $tempDir.'/blurred_'.$video->id.'_'.time().'.'.$extension;

        try {
            // Get video dimensions using ffprobe
            $dimensions = $this->getVideoDimensions($inputPath);

            if (! $dimensions) {
                throw new \Exception('Failed to get video dimensions');
            }

            $video->update(['blur_progress' => 20]);

            // Calculate blur region in pixels from percentage
            $blurRegion = $video->blur_region;
            $blurX = round($dimensions['width'] * ($blurRegion['x'] / 100));
            $blurY = round($dimensions['height'] * ($blurRegion['y'] / 100));
            $blurW = round($dimensions['width'] * ($blurRegion['width'] / 100));
            $blurH = round($dimensions['height'] * ($blurRegion['height'] / 100));

            // Ensure minimum dimensions (FFmpeg requires even numbers for some codecs)
            $blurW = max(2, $blurW - ($blurW % 2));
            $blurH = max(2, $blurH - ($blurH % 2));

            Log::info('Blur dimensions calculated', [
                'video_id' => $video->id,
                'video_dimensions' => $dimensions,
                'blur_pixels' => ['x' => $blurX, 'y' => $blurY, 'w' => $blurW, 'h' => $blurH],
            ]);

            $video->update(['blur_progress' => 30]);

            // Build FFmpeg command with blur filter
            $ffmpegPath = config('media-library.ffmpeg_path');

            // Build the blur filter
            // If we have time range, apply blur only during that time
            $startTime = $video->blur_start_time;
            $endTime = $video->blur_end_time;

            if ($startTime !== null && $endTime !== null) {
                // Apply blur only during specific time range
                // Using enable='between(t,start,end)' to control when blur is applied
                $blurFilter = sprintf(
                    "[0:v]split=2[original][toblur];[toblur]crop=%d:%d:%d:%d,boxblur=20:20[blurred];[original][blurred]overlay=%d:%d:enable='between(t,%.2f,%.2f)'[out]",
                    $blurW, $blurH, $blurX, $blurY,
                    $blurX, $blurY,
                    $startTime, $endTime
                );
            } else {
                // Apply blur to entire video
                $blurFilter = sprintf(
                    '[0:v]split=2[original][toblur];[toblur]crop=%d:%d:%d:%d,boxblur=20:20[blurred];[original][blurred]overlay=%d:%d[out]',
                    $blurW, $blurH, $blurX, $blurY,
                    $blurX, $blurY
                );
            }

            $command = sprintf(
                '%s -y -i %s -filter_complex %s -map "[out]" -map 0:a? -c:v libx264 -preset medium -crf 23 -c:a copy %s 2>&1',
                escapeshellarg($ffmpegPath),
                escapeshellarg($inputPath),
                escapeshellarg($blurFilter),
                escapeshellarg($outputPath)
            );

            Log::info('Running FFmpeg blur command', [
                'video_id' => $video->id,
                'command' => $command,
            ]);

            $video->update(['blur_progress' => 40]);

            // Execute FFmpeg
            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            $outputText = implode("\n", $output);

            Log::info('FFmpeg blur output', [
                'video_id' => $video->id,
                'return_code' => $returnCode,
                'output_length' => strlen($outputText),
            ]);

            if ($returnCode !== 0) {
                throw new \Exception("FFmpeg failed with code $returnCode: ".substr($outputText, -500));
            }

            if (! file_exists($outputPath)) {
                throw new \Exception('Output file was not created');
            }

            $outputSize = filesize($outputPath);
            if ($outputSize < 1000) {
                throw new \Exception("Output file is too small: $outputSize bytes");
            }

            $video->update(['blur_progress' => 80]);

            // Replace the original media with the blurred file
            $video->clearMediaCollection('videos');

            $video->addMedia($outputPath)
                ->usingFileName('video_'.$video->id.'.mp4')
                ->toMediaCollection('videos');

            $video->update(['blur_progress' => 95]);

            // Regenerate thumbnail from the blurred video
            $video->generateThumbnailFromMidpoint();

            // Mark as completed
            $video->update([
                'blur_status' => 'completed',
                'blur_progress' => 100,
                'blur_error' => null,
            ]);

            Log::info('Blur effect applied successfully', [
                'video_id' => $video->id,
                'output_size' => $outputSize,
            ]);

            // Clean up temp file if it still exists
            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }

            // Dispatch HLS conversion job to update the HLS files
            ProcessHlsConversionJob::dispatch($video)->delay(now()->addSeconds(5));

        } catch (\Exception $e) {
            Log::error('Blur effect application failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            // Clean up temp file
            if (isset($outputPath) && file_exists($outputPath)) {
                @unlink($outputPath);
            }

            $this->markAsFailed($video, $e->getMessage());

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Get video dimensions using ffprobe.
     */
    private function getVideoDimensions(string $filePath): ?array
    {
        $ffprobePath = config('media-library.ffprobe_path');

        $command = sprintf(
            '%s -v quiet -select_streams v:0 -show_entries stream=width,height -of json %s',
            escapeshellarg($ffprobePath),
            escapeshellarg($filePath)
        );

        $output = [];
        exec($command, $output);
        $data = json_decode(implode('', $output), true);

        if (isset($data['streams'][0]['width']) && isset($data['streams'][0]['height'])) {
            return [
                'width' => (int) $data['streams'][0]['width'],
                'height' => (int) $data['streams'][0]['height'],
            ];
        }

        return null;
    }

    /**
     * Mark the blur operation as failed.
     */
    private function markAsFailed(Video $video, string $error): void
    {
        $video->update([
            'blur_status' => 'failed',
            'blur_error' => substr($error, 0, 1000),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('Blur job failed permanently', [
            'video_id' => $this->video->id,
            'error' => $exception?->getMessage(),
        ]);

        $this->markAsFailed($this->video, $exception?->getMessage() ?? 'Unknown error');
    }
}
