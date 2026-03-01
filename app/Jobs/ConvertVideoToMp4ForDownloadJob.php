<?php

namespace App\Jobs;

use App\Managers\NotificationManager;
use App\Managers\VideoManager;
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

    public function handle(NotificationManager $notificationManager, VideoManager $videoManager): void
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
            $command = $videoManager->buildMp4DownloadCommand($video, $inputPath, $outputPath);

            Log::info('Running FFmpeg MP4 download conversion', [
                'video_id' => $video->id,
            ]);

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            // Clean up temp SRT file
            $videoManager->cleanupTempSrt($video->id);

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
