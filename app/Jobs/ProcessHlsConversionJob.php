<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\HlsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessHlsConversionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     * HLS conversion can take longer than MP4 conversion.
     */
    public int $timeout = 10800; // 3 hours for 4K video transcoding

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Video $video
    ) {}

    /**
     * Execute the job.
     */
    public function handle(HlsService $hlsService): void
    {
        $video = $this->video;

        Log::info('Starting HLS conversion', [
            'video_id' => $video->id,
            'title' => $video->title,
        ]);

        // Skip if MP4 conversion isn't complete
        if ($video->conversion_status !== 'completed') {
            Log::info('Skipping HLS conversion - MP4 not ready', [
                'video_id' => $video->id,
                'conversion_status' => $video->conversion_status,
            ]);

            return;
        }

        // Skip if HLS already processed
        if ($video->hls_status === 'completed' && $hlsService->hlsFilesExist($video)) {
            Log::info('HLS already converted', ['video_id' => $video->id]);

            return;
        }

        // Mark as processing
        $video->update([
            'hls_status' => 'processing',
            'hls_progress' => 5,
            'hls_error' => null,
        ]);

        try {
            // Perform HLS conversion with progress callback
            $result = $hlsService->convertToHls($video, function ($progress) use ($video) {
                $video->update(['hls_progress' => $progress]);
            });

            // Mark as completed
            $video->update([
                'hls_status' => 'completed',
                'hls_progress' => 100,
                'hls_path' => $result['hls_path'],
                'hls_error' => null,
                'hls_converted_at' => now(),
            ]);

            Log::info('HLS conversion completed successfully', [
                'video_id' => $video->id,
                'hls_path' => $result['hls_path'],
                'variants' => $result['variants'],
            ]);

        } catch (\Exception $e) {
            Log::error('HLS conversion failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            $this->markAsFailed($video, $e->getMessage());

            throw $e;
        }
    }

    /**
     * Mark the video HLS conversion as failed.
     */
    protected function markAsFailed(Video $video, string $error): void
    {
        $video->update([
            'hls_status' => 'failed',
            'hls_error' => substr($error, 0, 1000),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('HLS conversion job failed permanently', [
            'video_id' => $this->video->id,
            'error' => $exception?->getMessage(),
        ]);

        $this->markAsFailed($this->video, $exception?->getMessage() ?? 'Unknown error');
    }
}
