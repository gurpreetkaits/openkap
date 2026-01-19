<?php

namespace App\Jobs;

use App\Models\Video;
use App\Models\VideoZoomSetting;
use App\Services\ZoomEffectService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ApplyZoomEffectsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     * Zoom effects processing can be intensive.
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
    public function handle(ZoomEffectService $zoomService): void
    {
        $video = $this->video->load(['zoomSettings', 'user.settings']);
        $zoomSettings = $video->zoomSettings;
        $user = $video->user;

        Log::info('Starting zoom effects processing', [
            'video_id' => $video->id,
            'title' => $video->title,
            'zoom_enabled' => $zoomSettings?->enabled ?? false,
            'zoom_event_count' => $zoomSettings?->getEventCount() ?? 0,
            'user_auto_zoom' => $user?->isAutoZoomEnabled() ?? true,
        ]);

        // Skip if user has disabled auto-zoom in their settings
        if ($user && ! $user->isAutoZoomEnabled()) {
            Log::info('Skipping zoom processing - user has auto-zoom disabled', [
                'video_id' => $video->id,
                'user_id' => $user->id,
            ]);
            $this->dispatchNextJob($video);

            return;
        }

        // Skip if no zoom settings or zoom not enabled
        if (! $zoomSettings || ! $zoomSettings->enabled) {
            Log::info('Skipping zoom processing - zoom not enabled', [
                'video_id' => $video->id,
            ]);
            $this->dispatchNextJob($video);

            return;
        }

        // Skip if no zoom events
        if ($zoomSettings->getEventCount() === 0) {
            Log::info('Skipping zoom processing - no zoom events', [
                'video_id' => $video->id,
            ]);
            $this->updateZoomStatus($zoomSettings, 'completed', 100);
            $this->dispatchNextJob($video);

            return;
        }

        // Skip if MP4 conversion isn't complete
        if ($video->conversion_status !== 'completed') {
            Log::info('Skipping zoom processing - MP4 not ready', [
                'video_id' => $video->id,
                'conversion_status' => $video->conversion_status,
            ]);

            return;
        }

        // Skip if already processed
        if ($zoomSettings->isCompleted()) {
            Log::info('Zoom effects already applied', ['video_id' => $video->id]);
            $this->dispatchNextJob($video);

            return;
        }

        // Get the current video media
        $media = $video->getFirstMedia('videos');
        if (! $media) {
            Log::error('No media found for video', ['video_id' => $video->id]);
            $this->markAsFailed($zoomSettings, 'No media file found');

            return;
        }

        $inputPath = $media->getPath();

        // Mark as processing
        $this->updateZoomStatus($zoomSettings, 'processing', 5);

        // Prepare output path
        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $outputPath = $tempDir.'/zoomed_'.$video->id.'_'.time().'.mp4';

        try {
            // Apply zoom effects with progress callback
            $result = $zoomService->applyZoomEffects(
                $video,
                $inputPath,
                $outputPath,
                function ($progress) use ($zoomSettings) {
                    $zoomSettings->update(['progress' => $progress]);
                }
            );

            if (! $result) {
                // No zoom filter was applied (maybe all events disabled)
                Log::info('No zoom effects applied - filter was empty', ['video_id' => $video->id]);
                $this->updateZoomStatus($zoomSettings, 'completed', 100);
                $this->dispatchNextJob($video);

                return;
            }

            // Replace the original media with the zoomed file
            $video->clearMediaCollection('videos');
            $video->addMedia($outputPath)
                ->usingFileName('video_'.$video->id.'.mp4')
                ->toMediaCollection('videos');

            // Mark as completed
            $this->updateZoomStatus($zoomSettings, 'completed', 100);

            Log::info('Zoom effects applied successfully', [
                'video_id' => $video->id,
                'zoom_event_count' => $zoomSettings->getEventCount(),
            ]);

            // Clean up temp file
            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }

            // Dispatch next job in pipeline
            $this->dispatchNextJob($video);

        } catch (\Exception $e) {
            Log::error('Zoom effects processing failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            // Clean up temp file
            if (isset($outputPath) && file_exists($outputPath)) {
                @unlink($outputPath);
            }

            $this->markAsFailed($zoomSettings, $e->getMessage());

            throw $e;
        }
    }

    /**
     * Dispatch the next job in the processing pipeline.
     */
    protected function dispatchNextJob(Video $video): void
    {
        // Continue to HLS conversion
        Log::info('Dispatching ProcessHlsConversionJob after zoom effects', [
            'video_id' => $video->id,
        ]);
        ProcessHlsConversionJob::dispatch($video)->delay(now()->addSeconds(5));
    }

    /**
     * Update zoom status.
     */
    protected function updateZoomStatus(VideoZoomSetting $settings, string $status, ?int $progress = null): void
    {
        $data = ['status' => $status, 'error' => null];
        if ($progress !== null) {
            $data['progress'] = $progress;
        }
        $settings->update($data);
    }

    /**
     * Mark the video zoom processing as failed.
     */
    protected function markAsFailed(VideoZoomSetting $settings, string $error): void
    {
        $settings->update([
            'status' => 'failed',
            'error' => substr($error, 0, 1000),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('Zoom effects job failed permanently', [
            'video_id' => $this->video->id,
            'error' => $exception?->getMessage(),
        ]);

        $zoomSettings = $this->video->zoomSettings;
        if ($zoomSettings) {
            $this->markAsFailed($zoomSettings, $exception?->getMessage() ?? 'Unknown error');
        }

        // Still try to continue with HLS even if zoom failed
        ProcessHlsConversionJob::dispatch($this->video)->delay(now()->addSeconds(5));
    }
}
