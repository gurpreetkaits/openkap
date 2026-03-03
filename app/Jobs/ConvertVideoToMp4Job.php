<?php

namespace App\Jobs;

use App\Models\Video;
use App\Repositories\WorkspaceRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ConvertVideoToMp4Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * The maximum number of seconds the job can run.
     * Set to 30 minutes for long videos.
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
        $video = $this->video;
        $video->refresh();

        // Concurrency lock: only 1 ffmpeg conversion at a time on small servers
        $lock = Cache::lock('ffmpeg-conversion', $this->timeout);

        if (! $lock->get()) {
            Log::warning('Another ffmpeg conversion is running, releasing back to queue', [
                'video_id' => $video->id,
            ]);
            $this->release(60); // Retry after 60 seconds

            return;
        }

        try {
            $this->runConversion($video);
        } finally {
            $lock->release();
        }
    }

    /**
     * Run the actual video conversion.
     */
    private function runConversion(Video $video): void
    {
        Log::info('Starting video conversion', [
            'video_id' => $video->id,
            'title' => $video->title,
        ]);

        // Get the current media
        $media = $video->getFirstMedia('videos');

        if (! $media) {
            Log::error('No media found for video', ['video_id' => $video->id]);
            $this->markAsFailed($video, 'No media file found');

            return;
        }

        $inputPath = $media->getPath();
        $mimeType = $media->mime_type;
        $originalExtension = pathinfo($inputPath, PATHINFO_EXTENSION);

        // Store original extension
        $video->update(['original_extension' => $originalExtension]);

        // Skip if already MP4 with faststart (check file structure)
        if ($mimeType === 'video/mp4' && $this->hasFastStart($inputPath)) {
            Log::info('Video already MP4 with faststart, skipping conversion', [
                'video_id' => $video->id,
            ]);
            $video->update([
                'conversion_status' => 'completed',
                'conversion_progress' => 100,
                'converted_at' => now(),
                'file_size_bytes' => filesize($inputPath),
            ]);

            $this->recalculateWorkspaceStorage($video);

            // Dispatch next job in pipeline (zoom if enabled, otherwise HLS)
            $this->dispatchNextJob($video);

            return;
        }

        // Mark as processing
        $video->update([
            'conversion_status' => 'processing',
            'conversion_progress' => 10,
        ]);

        // Prepare output path
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $outputPath = $tempDir.'/converted_'.$video->id.'_'.time().'.mp4';

        try {
            $ffmpegPath = config('media-library.ffmpeg_path');

            Log::info('Running FFmpeg conversion', [
                'video_id' => $video->id,
                'input' => $inputPath,
            ]);

            // Execute conversion with progress tracking
            $video->update(['conversion_progress' => 20]);

            // Use Symfony Process so child ffmpeg gets killed on queue worker timeout
            $process = new Process([
                $ffmpegPath,
                '-y',
                '-threads', '1',
                '-i', $inputPath,
                '-vf', 'scale=min(iw\,1920):min(ih\,1080):force_original_aspect_ratio=decrease,pad=ceil(iw/2)*2:ceil(ih/2)*2',
                '-c:v', 'libx264',
                '-preset', 'fast',
                '-crf', '22',
                '-maxrate', '5M',
                '-bufsize', '3M',
                '-pix_fmt', 'yuv420p',
                '-c:a', 'aac',
                '-b:a', '128k',
                '-max_muxing_queue_size', '1024',
                '-movflags', '+faststart',
                $outputPath,
            ]);
            $process->setTimeout($this->timeout);
            $process->run();

            if (! $process->isSuccessful()) {
                $errorOutput = $process->getErrorOutput();
                throw new \Exception('FFmpeg failed (exit '.$process->getExitCode().'): '.substr($errorOutput, -500));
            }

            if (! file_exists($outputPath)) {
                throw new \Exception('Output file was not created');
            }

            $outputSize = filesize($outputPath);
            if ($outputSize < 1000) {
                throw new \Exception("Output file is too small: $outputSize bytes");
            }

            // Extract actual duration using ffprobe via Process
            $ffprobePath = config('media-library.ffprobe_path');

            $probeProcess = new Process([
                $ffprobePath,
                '-v', 'quiet',
                '-select_streams', 'v:0',
                '-show_entries', 'stream=duration',
                '-of', 'json',
                $outputPath,
            ]);
            $probeProcess->setTimeout(30);
            $probeProcess->run();

            $probeData = json_decode($probeProcess->getOutput(), true);
            $duration = isset($probeData['streams'][0]['duration'])
                ? (float) $probeData['streams'][0]['duration']
                : $video->duration;

            $video->update(['conversion_progress' => 80]);

            // Replace the original media with the converted file
            $video->clearMediaCollection('videos');

            $video->addMedia($outputPath)
                ->usingFileName('video_'.$video->id.'.mp4')
                ->toMediaCollection('videos');

            $video->update([
                'conversion_progress' => 95,
                'duration' => round($duration), // Update with actual duration
            ]);

            // Regenerate thumbnail from the converted video
            $video->generateThumbnailFromMidpoint();

            // Mark as completed
            $video->update([
                'conversion_status' => 'completed',
                'conversion_progress' => 100,
                'conversion_error' => null,
                'converted_at' => now(),
                'file_size_bytes' => $outputSize,
            ]);

            Log::info('Video conversion completed successfully', [
                'video_id' => $video->id,
                'original_extension' => $originalExtension,
                'output_size' => $outputSize,
                'duration' => $duration,
            ]);

            $this->recalculateWorkspaceStorage($video);

            // Dispatch next job in pipeline (zoom if enabled, otherwise HLS)
            $this->dispatchNextJob($video);

            // Clean up temp file if it still exists
            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }

        } catch (\Exception $e) {
            Log::error('Video conversion failed', [
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
     * Dispatch the next job in the processing pipeline.
     * If zoom is enabled, dispatch ApplyZoomEffectsJob first.
     * Otherwise, go directly to HLS conversion.
     */
    private function dispatchNextJob(Video $video): void
    {
        // Load zoom settings and user settings relationships
        $video->load(['zoomSettings', 'user']);

        $isZoomEnabled = $video->isZoomEnabled();
        $hasZoomEvents = $video->hasZoomEventsToProcess();
        $userAutoZoomEnabled = $video->user?->isAutoZoomEnabled() ?? false;

        Log::info('Checking zoom settings for next job', [
            'video_id' => $video->id,
            'has_zoom_settings' => $video->zoomSettings !== null,
            'is_zoom_enabled' => $isZoomEnabled,
            'has_zoom_events' => $hasZoomEvents,
            'zoom_event_count' => $video->getZoomEventCount(),
            'user_auto_zoom_enabled' => $userAutoZoomEnabled,
        ]);

        if ($isZoomEnabled && $hasZoomEvents) {
            Log::info('Dispatching ApplyZoomEffectsJob', [
                'video_id' => $video->id,
                'title' => $video->title,
                'zoom_event_count' => $video->getZoomEventCount(),
            ]);
            ApplyZoomEffectsJob::dispatch($video)->delay(now()->addSeconds(5));
        } else {
            $reason = ! $isZoomEnabled ? 'zoom_disabled_for_video' : 'no_zoom_events';
            if (! $userAutoZoomEnabled && ! $video->zoomSettings) {
                $reason = 'user_auto_zoom_disabled';
            }

            Log::info('Dispatching ProcessHlsConversionJob (skipping zoom)', [
                'video_id' => $video->id,
                'title' => $video->title,
                'reason' => $reason,
            ]);
            ProcessHlsConversionJob::dispatch($video)->delay(now()->addSeconds(5));
        }
    }

    /**
     * Check if MP4 file already has faststart (moov atom at beginning).
     */
    private function hasFastStart(string $filePath): bool
    {
        // Read first 32 bytes to check for ftyp and moov atoms
        $handle = fopen($filePath, 'rb');
        if (! $handle) {
            return false;
        }

        $header = fread($handle, 32);
        fclose($handle);

        // Check if moov atom appears early in the file (indicates faststart)
        // This is a simplified check - moov should come before mdat for faststart
        return str_contains($header, 'ftyp') && str_contains($header, 'moov');
    }

    /**
     * Recalculate workspace storage if the video belongs to a workspace.
     */
    private function recalculateWorkspaceStorage(Video $video): void
    {
        if ($video->workspace_id) {
            $video->load('workspace');
            if ($video->workspace) {
                app(WorkspaceRepository::class)->recalculateStorage($video->workspace);
            }
        }
    }

    /**
     * Mark the video conversion as failed.
     */
    private function markAsFailed(Video $video, string $error): void
    {
        $video->update([
            'conversion_status' => 'failed',
            'conversion_error' => substr($error, 0, 1000), // Limit error message length
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('Video conversion job failed permanently', [
            'video_id' => $this->video->id,
            'error' => $exception?->getMessage(),
        ]);

        $this->markAsFailed($this->video, $exception?->getMessage() ?? 'Unknown error');
    }
}
