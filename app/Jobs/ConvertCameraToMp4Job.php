<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class ConvertCameraToMp4Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600;

    public int $backoff = 30;

    public function __construct(
        public Video $video
    ) {}

    public function handle(): void
    {
        $video = $this->video;
        $video->refresh();

        if (! $video->has_camera) {
            return;
        }

        $media = $video->getFirstMedia('camera');
        if (! $media) {
            $video->update(['camera_conversion_status' => 'failed']);

            return;
        }

        $inputPath = $media->getPath();
        if (! file_exists($inputPath)) {
            $video->update(['camera_conversion_status' => 'failed']);

            return;
        }

        // Skip if already MP4
        if (strtolower(pathinfo($inputPath, PATHINFO_EXTENSION)) === 'mp4') {
            $video->update([
                'camera_conversion_status' => 'completed',
                'camera_conversion_progress' => 100,
            ]);

            return;
        }

        $video->update([
            'camera_conversion_status' => 'processing',
            'camera_conversion_progress' => 10,
        ]);

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $outputPath = $tempDir.'/camera_'.$video->id.'_'.time().'.mp4';

        try {
            $ffmpegPath = config('media-library.ffmpeg_path');

            Log::info('ConvertCameraToMp4Job: Converting camera WebM to MP4', [
                'video_id' => $video->id,
                'input_size' => filesize($inputPath),
            ]);

            $video->update(['camera_conversion_progress' => 30]);

            // WebM from MediaRecorder has variable framerate which causes
            // glitchy playback when converted to MP4. Force constant framerate
            // and use vsync to fix timestamp issues.
            $process = new Process([
                $ffmpegPath,
                '-y',
                '-fflags', '+genpts',
                '-i', $inputPath,
                '-c:v', 'libx264',
                '-preset', 'fast',
                '-crf', '23',
                '-pix_fmt', 'yuv420p',
                '-r', '30',
                '-vsync', 'cfr',
                '-an',
                '-movflags', '+faststart',
                $outputPath,
            ]);
            $process->setTimeout($this->timeout);
            $process->run();

            $video->update(['camera_conversion_progress' => 70]);

            if (! $process->isSuccessful()) {
                throw new \RuntimeException(
                    'FFmpeg failed (exit '.$process->getExitCode().'): '.substr($process->getErrorOutput(), -500)
                );
            }

            if (! file_exists($outputPath) || filesize($outputPath) < 1000) {
                throw new \RuntimeException('Output file missing or too small');
            }

            $video->update(['camera_conversion_progress' => 90]);

            // Replace camera media with MP4
            $video->clearMediaCollection('camera');

            $video->addMedia($outputPath)
                ->usingFileName("camera_{$video->id}.mp4")
                ->toMediaCollection('camera');

            $video->update([
                'camera_conversion_status' => 'completed',
                'camera_conversion_progress' => 100,
            ]);

            Log::info('ConvertCameraToMp4Job: Camera converted successfully', [
                'video_id' => $video->id,
            ]);

        } catch (\Exception $e) {
            Log::error('ConvertCameraToMp4Job: Failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }

            $video->update([
                'camera_conversion_status' => 'failed',
                'camera_conversion_progress' => 0,
            ]);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('ConvertCameraToMp4Job: Job failed permanently', [
            'video_id' => $this->video->id,
            'error' => $exception?->getMessage(),
        ]);

        $this->video->update([
            'camera_conversion_status' => 'failed',
            'camera_conversion_progress' => 0,
        ]);
    }
}
