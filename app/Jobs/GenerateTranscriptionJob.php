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

class GenerateTranscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     * Set to 20 minutes for long videos.
     */
    public int $timeout = 1200;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 120;

    /**
     * Maximum number of times to reschedule when waiting for conversion.
     */
    private int $maxReschedules = 10;

    public function __construct(
        public Video $video,
        public bool $generateSummary = true,
        public bool $generateTitle = true,
        public bool $generateDescription = false,
        public int $rescheduleCount = 0
    ) {}

    public function handle(TranscriptionService $service): void
    {
        $video = $this->video;

        Log::info('Starting transcription job', [
            'video_id' => $video->id,
            'title' => $video->title,
        ]);

        // Refresh model to get latest status from DB
        $video->refresh();

        // Verify video conversion is complete
        if (! $video->isConversionComplete()) {
            // Abort if conversion permanently failed
            if ($video->conversion_status === 'failed') {
                Log::error('Video conversion failed, aborting transcription', [
                    'video_id' => $video->id,
                ]);
                $this->markAsFailed($video, 'Video conversion failed, cannot transcribe');

                return;
            }

            // Abort if rescheduled too many times
            if ($this->rescheduleCount >= $this->maxReschedules) {
                Log::error('Transcription rescheduled too many times, aborting', [
                    'video_id' => $video->id,
                    'reschedule_count' => $this->rescheduleCount,
                ]);
                $this->markAsFailed($video, 'Video conversion did not complete in time');

                return;
            }

            Log::warning('Video conversion not complete, rescheduling transcription', [
                'video_id' => $video->id,
                'conversion_status' => $video->conversion_status,
                'reschedule_count' => $this->rescheduleCount + 1,
            ]);

            // Reschedule for later with incremented counter
            self::dispatch($video, $this->generateSummary, $this->generateTitle, $this->rescheduleCount + 1)
                ->delay(now()->addMinutes(2));

            return;
        }

        // Check if video has audio streams
        if (! $this->videoHasAudio($video)) {
            Log::info('Video has no audio, skipping transcription pipeline', [
                'video_id' => $video->id,
            ]);

            $video->update([
                'has_audio' => false,
                'transcription_status' => 'skipped',
                'summary_status' => 'skipped',
                'bug_detection_status' => 'skipped',
            ]);

            return;
        }

        $video->update(['has_audio' => true]);

        // Mark as processing
        $video->update([
            'transcription_status' => 'processing',
            'transcription_progress' => 10,
        ]);

        $audioPath = null;

        try {
            // Configure service with video owner context
            $service->forUser($video->user);

            // Extract audio (30%)
            $video->update(['transcription_progress' => 20]);
            $audioPath = $service->extractAudio($video);
            $video->update(['transcription_progress' => 40]);

            // Transcribe audio (70%)
            $transcriptionData = $service->transcribe($audioPath, $video);
            $video->update(['transcription_progress' => 80]);

            // Build word timestamp index for precise caption sync
            $wordTimestamps = $transcriptionData->words ?? [];

            // Format segments for storage with word-level timestamps
            $segments = null;
            if (! empty($transcriptionData->segments)) {
                $segments = array_map(function ($segment) use ($wordTimestamps) {
                    $segStart = round($segment['start'], 2);
                    $segEnd = round($segment['end'], 2);

                    // Find words that fall within this segment's time range
                    $segWords = [];
                    foreach ($wordTimestamps as $w) {
                        $wStart = round($w['start'] ?? 0, 2);
                        $wEnd = round($w['end'] ?? 0, 2);
                        if ($wStart >= $segStart && $wEnd <= $segEnd + 0.05) {
                            $segWords[] = [
                                'start' => $wStart,
                                'end' => $wEnd,
                                'text' => trim($w['word'] ?? ''),
                            ];
                        }
                    }

                    return [
                        'start' => $segStart,
                        'end' => $segEnd,
                        'text' => trim($segment['text']),
                        'words' => ! empty($segWords) ? $segWords : null,
                    ];
                }, $transcriptionData->segments);
            }

            // Save transcription with segments
            $video->update([
                'transcription' => $transcriptionData->text,
                'transcription_segments' => $segments,
                'transcription_status' => 'completed',
                'transcription_progress' => 100,
                'transcription_error' => null,
                'transcription_generated_at' => now(),
            ]);

            Log::info('Transcription completed successfully', [
                'video_id' => $video->id,
                'text_length' => strlen($transcriptionData->text),
                'language' => $transcriptionData->language,
                'segments_count' => count($segments ?? []),
            ]);

            // Clean up audio file
            $service->cleanupAudio($audioPath);

            // Generate title if enabled and transcription has content
            if ($this->generateTitle && ! empty($transcriptionData->text)) {
                try {
                    $newTitle = $service->generateTitle($transcriptionData->text, $video);
                    if ($newTitle !== null && $newTitle !== $video->title) {
                        $video->update(['title' => $newTitle]);
                        Log::info('Video title updated', [
                            'video_id' => $video->id,
                            'old_title' => $video->title,
                            'new_title' => $newTitle,
                        ]);
                    }
                } catch (\Exception $e) {
                    // Title generation failure should not fail the whole job
                    Log::warning('Title generation failed', [
                        'video_id' => $video->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Dispatch summary job if enabled
            if ($this->generateSummary && ! empty($transcriptionData->text)) {
                Log::info('Dispatching summary job', ['video_id' => $video->id]);
                GenerateSummaryJob::dispatch($video)->delay(now()->addSeconds(5));
            }

        } catch (\Exception $e) {
            Log::error('Transcription job failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            // Clean up audio file if exists
            if ($audioPath) {
                $service->cleanupAudio($audioPath);
            }

            $this->markAsFailed($video, $e->getMessage());

            throw $e;
        }
    }

    protected function videoHasAudio(Video $video): bool
    {
        $media = $video->getFirstMedia('videos');

        if (! $media) {
            return false;
        }

        $ffprobePath = config('media-library.ffprobe_path');
        $videoPath = $media->getPath();

        $command = sprintf(
            '%s -v quiet -select_streams a -show_entries stream=codec_type -of json %s 2>&1',
            escapeshellarg($ffprobePath),
            escapeshellarg($videoPath)
        );

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            Log::warning('ffprobe audio detection failed, assuming audio present', [
                'video_id' => $video->id,
                'return_code' => $returnCode,
            ]);

            return true;
        }

        $result = json_decode(implode('', $output), true);
        $streams = $result['streams'] ?? [];

        return count($streams) > 0;
    }

    protected function markAsFailed(Video $video, string $error): void
    {
        $video->update([
            'transcription_status' => 'failed',
            'transcription_error' => substr($error, 0, 1000),
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Transcription job failed permanently', [
            'video_id' => $this->video->id,
            'error' => $exception?->getMessage(),
        ]);

        $this->markAsFailed($this->video, $exception?->getMessage() ?? 'Unknown error');
    }
}
