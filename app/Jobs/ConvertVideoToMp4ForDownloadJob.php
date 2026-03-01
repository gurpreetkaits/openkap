<?php

namespace App\Jobs;

use App\Managers\NotificationManager;
use App\Models\Video;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConvertVideoToMp4ForDownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 7200;

    public int $tries = 2;

    public function __construct(
        protected Video $video
    ) {}

    public function handle(NotificationManager $notificationManager): void
    {
        $video = $this->video;

        Log::info('ConvertVideoToMp4ForDownloadJob started', ['video_id' => $video->id]);

        $media = $video->getFirstMedia('videos');
        if (! $media) {
            Log::error('No media found for video', ['video_id' => $video->id]);

            return;
        }

        $inputPath = $media->getPath();
        if (! file_exists($inputPath)) {
            Log::error('Video file not found on disk', ['video_id' => $video->id, 'path' => $inputPath]);

            return;
        }

        $outputDir = storage_path('app/mp4-downloads');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputPath = $outputDir.'/video_'.$video->id.'_'.time().'.mp4';

        try {
            $ffmpegPath = config('media-library.ffmpeg_path');

            $command = sprintf(
                '%s -y -threads 1 -i %s -vf "scale=min(iw\,1920):min(ih\,1080):force_original_aspect_ratio=decrease,pad=ceil(iw/2)*2:ceil(ih/2)*2" -c:v libx264 -preset fast -crf 22 -maxrate 5M -bufsize 3M -pix_fmt yuv420p -c:a aac -b:a 128k -max_muxing_queue_size 1024 -movflags +faststart %s 2>&1',
                escapeshellarg($ffmpegPath),
                escapeshellarg($inputPath),
                escapeshellarg($outputPath)
            );

            Log::info('Running FFmpeg MP4 download conversion', [
                'video_id' => $video->id,
                'command' => $command,
            ]);

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                $outputText = implode("\n", $output);
                throw new \Exception("FFmpeg failed with code $returnCode: ".substr($outputText, -500));
            }

            if (! file_exists($outputPath) || filesize($outputPath) < 1000) {
                throw new \Exception('Output file is missing or too small');
            }

            Log::info('MP4 download conversion completed', [
                'video_id' => $video->id,
                'output_size' => filesize($outputPath),
            ]);

            $downloadLink = "/api/videos/{$video->id}/download-mp4";

            $notificationManager->createDownloadReadyNotification($video, $downloadLink);

        } catch (\Exception $e) {
            Log::error('MP4 download conversion failed', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }

            $message = "MP4 conversion failed for \"{$video->title}\". Please try downloading again.";
            $notificationManager->createInfoNotification($video->user, $message);

            throw $e;
        }
    }
}
