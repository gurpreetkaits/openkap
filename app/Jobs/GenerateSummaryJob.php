<?php

namespace App\Jobs;

use App\Models\Video;
use App\Services\TranscriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    public function __construct(
        public Video $video
    ) {}

    public function handle(TranscriptionService $service): void
    {
        $video = $this->video;

        Log::info('Starting summary job', [
            'video_id' => $video->id,
            'title' => $video->title,
        ]);

        // Verify transcription is available
        if (! $video->isTranscriptionReady()) {
            Log::warning('Transcription not ready, cannot generate summary', [
                'video_id' => $video->id,
                'transcription_status' => $video->transcription_status,
            ]);

            $this->markAsFailed($video, 'Transcription not available');

            return;
        }

        // Skip if transcription is empty
        if (empty(trim($video->transcription))) {
            Log::warning('Transcription is empty, skipping summary', [
                'video_id' => $video->id,
            ]);

            $video->update([
                'summary_status' => 'completed',
                'summary' => 'No spoken content detected in this video.',
                'summary_generated_at' => now(),
            ]);

            return;
        }

        // Mark as processing
        $video->update([
            'summary_status' => 'processing',
        ]);

        try {
            // Configure service with video owner context
            $service->forUser($video->user);

            // Generate summary
            $summaryData = $service->generateSummary($video->transcription, $video);

            // Save summary
            $video->update([
                'summary' => $summaryData->summary,
                'summary_status' => 'completed',
                'summary_error' => null,
                'summary_generated_at' => now(),
            ]);

            Log::info('Summary completed successfully', [
                'video_id' => $video->id,
                'summary_length' => strlen($summaryData->summary),
                'tokens_used' => $summaryData->totalTokens,
            ]);

        } catch (\Exception $e) {
            Log::error('Summary job failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            $this->markAsFailed($video, $e->getMessage());

            throw $e;
        }
    }

    protected function markAsFailed(Video $video, string $error): void
    {
        $video->update([
            'summary_status' => 'failed',
            'summary_error' => substr($error, 0, 1000),
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Summary job failed permanently', [
            'video_id' => $this->video->id,
            'error' => $exception?->getMessage(),
        ]);

        $this->markAsFailed($this->video, $exception?->getMessage() ?? 'Unknown error');
    }
}
