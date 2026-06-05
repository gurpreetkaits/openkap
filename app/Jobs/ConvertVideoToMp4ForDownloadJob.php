<?php

namespace App\Jobs;

use App\Data\DownloadOptionsData;
use App\Managers\DownloadManager;
use App\Managers\NotificationManager;
use App\Managers\VideoManager;
use App\Models\Download;
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
        protected Video $video,
        protected ?Download $download = null,
        protected ?DownloadOptionsData $options = null,
    ) {}

    public function handle(
        NotificationManager $notificationManager,
        VideoManager $videoManager,
        DownloadManager $downloadManager,
    ): void {
        $video = $this->video;
        $download = $this->download;

        Log::info('ConvertVideoToMp4ForDownloadJob started', [
            'video_id' => $video->id,
            'download_id' => $download?->id,
        ]);

        $media = $video->getFirstMedia('videos');
        if (! $media) {
            Log::error('No media found for video', ['video_id' => $video->id]);
            if ($download) {
                $downloadManager->markFailed($download, 'No media file found');
            }
            return;
        }

        $inputPath = $media->getPath();
        if (! file_exists($inputPath)) {
            Log::error('Video file not found on disk', ['video_id' => $video->id, 'path' => $inputPath]);
            if ($download) {
                $downloadManager->markFailed($download, 'Video file not found on disk');
            }
            return;
        }

        $outputDir = storage_path('app/downloads');
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $downloadId = $download?->id ?? $video->id;
        $outputPath = $outputDir . '/video_' . $downloadId . '_' . time() . '.mp4';

        // Update status to converting
        if ($download) {
            $download->update(['status' => 'converting']);
            $downloadManager->updateProgress($download, 10);
        }

        try {
            $command = $videoManager->buildMp4DownloadCommand($video, $inputPath, $outputPath, $this->options);

            Log::info('Running FFmpeg MP4 download conversion', [
                'video_id' => $video->id,
                'download_id' => $download?->id,
            ]);

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            // Clean up temp SRT file
            $videoManager->cleanupTempSrt($video->id);

            if ($returnCode !== 0) {
                $outputText = implode("\n", $output);
                throw new \Exception("FFmpeg failed with code $returnCode: " . substr($outputText, -500));
            }

            if (! file_exists($outputPath) || filesize($outputPath) < 1000) {
                throw new \Exception('Output file is missing or too small');
            }

            $outputSize = filesize($outputPath);
            $relativePath = 'downloads/' . basename($outputPath);

            Log::info('MP4 download conversion completed', [
                'video_id' => $video->id,
                'download_id' => $download?->id,
                'output_size' => $outputSize,
            ]);

            if ($download) {
                // New system: mark download as ready
                $downloadManager->markReady($download, $relativePath, $outputSize);
            } else {
                // Legacy: create notification
                $downloadLink = "/api/videos/{$video->id}/download-mp4";
                $notificationManager->createDownloadReadyNotification($video, $downloadLink);
            }

        } catch (\Exception $e) {
            Log::error('MP4 download conversion failed', [
                'video_id' => $video->id,
                'download_id' => $download?->id,
                'error' => $e->getMessage(),
            ]);

            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }

            if ($download) {
                $downloadManager->markFailed($download, $e->getMessage());
            } else {
                $message = "MP4 conversion failed for \"{$video->title}\". Please try downloading again.";
                $notificationManager->createInfoNotification($video->user, $message);
            }

            throw $e;
        }
    }
}
