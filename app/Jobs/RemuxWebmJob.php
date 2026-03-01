<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RemuxWebmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 600; // 10 minutes

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 30;

    public function __construct(
        public Video $video
    ) {}

    /**
     * Remux the raw WebM file to fix missing Duration and Cues (seek index).
     *
     * MediaRecorder outputs WebM without proper container metadata because
     * chunks are streamed live. This fast remux (codec copy, no re-encoding)
     * fixes the container so the video is playable/seekable in browsers
     * before full MP4 conversion or Bunny encoding completes.
     */
    public function handle(): void
    {
        $video = $this->video;
        $video->refresh();

        $media = $video->getFirstMedia('videos');

        if (! $media) {
            Log::warning('RemuxWebmJob: No media found, skipping', ['video_id' => $video->id]);

            return;
        }

        $inputPath = $media->getPath();
        $extension = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));

        // Only remux WebM files
        if ($extension !== 'webm' && $media->mime_type !== 'video/webm') {
            Log::info('RemuxWebmJob: Not a WebM file, skipping', [
                'video_id' => $video->id,
                'extension' => $extension,
            ]);

            return;
        }

        if (! file_exists($inputPath)) {
            Log::warning('RemuxWebmJob: File not found, skipping', [
                'video_id' => $video->id,
                'path' => $inputPath,
            ]);

            return;
        }

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $outputPath = $tempDir.'/remuxed_'.$video->id.'_'.time().'.webm';

        try {
            $ffmpegPath = config('media-library.ffmpeg_path');

            // Remux: copy all streams, no re-encoding — just fixes the container
            // This adds Duration metadata and Cues (seek index) to the WebM
            $command = sprintf(
                '%s -y -i %s -c copy %s 2>&1',
                escapeshellarg($ffmpegPath),
                escapeshellarg($inputPath),
                escapeshellarg($outputPath)
            );

            $inputSize = filesize($inputPath);

            Log::info('RemuxWebmJob: Remuxing WebM to fix container metadata', [
                'video_id' => $video->id,
                'input_size' => $inputSize,
            ]);

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \RuntimeException('FFmpeg remux failed with code '.$returnCode.': '.substr(implode("\n", $output), -500));
            }

            if (! file_exists($outputPath)) {
                throw new \RuntimeException('Remuxed output file was not created');
            }

            $outputSize = filesize($outputPath);
            if ($outputSize < 1000) {
                throw new \RuntimeException("Remuxed file is too small: {$outputSize} bytes");
            }

            // Extract duration from the remuxed file
            $ffprobePath = config('media-library.ffprobe_path');
            $probeCommand = sprintf(
                '%s -v quiet -show_entries format=duration -of json %s',
                escapeshellarg($ffprobePath),
                escapeshellarg($outputPath)
            );

            $probeOutput = [];
            exec($probeCommand, $probeOutput);
            $probeData = json_decode(implode('', $probeOutput), true);
            $duration = isset($probeData['format']['duration'])
                ? (float) $probeData['format']['duration']
                : null;

            // Replace the original media with the remuxed file
            $video->clearMediaCollection('videos');

            $video->addMedia($outputPath)
                ->usingFileName('video_'.$video->id.'.webm')
                ->toMediaCollection('videos');

            // Update duration and mark conversion as completed
            // (no MP4/HLS conversion — remuxed WebM is served directly)
            $updateData = [
                'conversion_status' => 'completed',
                'conversion_progress' => 100,
                'converted_at' => now(),
                'file_size_bytes' => $outputSize,
            ];

            if ($duration && (! $video->duration || $video->duration === 0)) {
                $updateData['duration'] = round($duration);
            }

            $video->update($updateData);

            Log::info('RemuxWebmJob: WebM remuxed successfully', [
                'video_id' => $video->id,
                'original_size' => $inputSize,
                'remuxed_size' => $outputSize,
                'duration' => $duration,
            ]);

        } catch (\Exception $e) {
            Log::error('RemuxWebmJob: Failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            // Clean up temp file
            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }

            // Non-critical — don't re-throw. The video will still work
            // via MP4 conversion or Bunny encoding.
        }
    }
}
