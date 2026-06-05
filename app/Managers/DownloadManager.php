<?php

namespace App\Managers;

use App\Data\DownloadData;
use App\Jobs\ConvertVideoToMp4ForDownloadJob;
use App\Models\Download;
use App\Models\User;
use App\Models\Video;
use App\Repositories\DownloadRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DownloadManager
{
    public function __construct(
        protected DownloadRepository $downloads,
    ) {}

    /**
     * Request a download for a video. Checks cache first, then dispatches
     * a conversion job if needed. Always returns a Download record.
     */
    public function request(Video $video, ?User $user = null): Download
    {
        Log::info('DownloadManager::request', [
            'video_id' => $video->id,
            'user_id' => $user?->id,
        ]);

        // 1. Check if a cached ready download already exists
        $cached = $this->downloads->findReadyByVideoAndFormat($video->id, 'mp4');

        if ($cached) {
            Log::info('DownloadManager: cached download found', [
                'download_id' => $cached->id,
                'video_id' => $video->id,
            ]);

            return $cached;
        }

        // 2. Create a new download record
        $downloadData = new DownloadData(
            video_id: $video->id,
            user_id: $user?->id,
            status: 'queued',
        );

        $download = $this->downloads->create($downloadData->toArray());

        // 3. Dispatch the conversion job
        ConvertVideoToMp4ForDownloadJob::dispatch($video, $download);

        Log::info('DownloadManager: dispatched conversion job', [
            'download_id' => $download->id,
            'video_id' => $video->id,
        ]);

        return $download;
    }

    /**
     * Get download by token (public access — no auth required).
     */
    public function findByToken(string $token): ?Download
    {
        return $this->downloads->findByToken($token);
    }

    /**
     * Get the file path for a ready download.
     */
    public function getFilePath(Download $download): ?string
    {
        if (! $download->isReady() || ! $download->file_path) {
            return null;
        }

        $fullPath = storage_path('app/' . $download->file_path);

        if (! file_exists($fullPath)) {
            Log::warning('DownloadManager: cached file missing', [
                'download_id' => $download->id,
                'file_path' => $download->file_path,
            ]);
            $this->downloads->markAsFailed($download, 'Cached file not found on disk');

            return null;
        }

        return $fullPath;
    }

    /**
     * Mark a download as downloaded.
     */
    public function markDownloaded(Download $download): void
    {
        $this->downloads->markAsDownloaded($download);
    }

    /**
     * Store progress update in cache for real-time polling.
     * The SSE/polling endpoint reads from this cache key.
     */
    public function updateProgress(Download $download, int $progress): void
    {
        $this->downloads->updateProgress($download, $progress);

        // Also store in cache for fast reads from the progress endpoint
        Cache::put(
            "download_progress:{$download->id}",
            [
                'status' => $download->status,
                'progress' => $progress,
            ],
            now()->addHours(2)
        );
    }

    /**
     * Mark download as ready with file info.
     */
    public function markReady(Download $download, string $filePath, int $fileSize): void
    {
        $this->downloads->markAsReady($download, $filePath, $fileSize);

        Cache::put(
            "download_progress:{$download->id}",
            [
                'status' => 'ready',
                'progress' => 100,
                'token' => $download->token,
            ],
            now()->addHours(2)
        );
    }

    /**
     * Mark download as failed.
     */
    public function markFailed(Download $download, string $errorMessage): void
    {
        $this->downloads->markAsFailed($download, $errorMessage);

        Cache::put(
            "download_progress:{$download->id}",
            [
                'status' => 'failed',
                'progress' => $download->progress,
                'error_message' => $errorMessage,
            ],
            now()->addHours(2)
        );
    }

    /**
     * Get the current progress from cache or DB.
     */
    public function getProgress(Download $download): array
    {
        $cached = Cache::get("download_progress:{$download->id}");

        if ($cached) {
            return $cached;
        }

        return [
            'status' => $download->status,
            'progress' => $download->progress,
            'error_message' => $download->error_message,
        ];
    }

    /**
     * Clean up expired downloads (called by scheduled command).
     */
    public function cleanupExpired(): int
    {
        $expired = $this->downloads->findExpired();
        $count = 0;

        foreach ($expired as $download) {
            // Delete the cached file
            if ($download->file_path) {
                $fullPath = storage_path('app/' . $download->file_path);
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }

            // Mark as expired
            $this->downloads->markAsExpired($download);

            // Clear progress cache
            Cache::forget("download_progress:{$download->id}");

            $count++;
        }

        Log::info('DownloadManager: cleaned up expired downloads', ['count' => $count]);

        return $count;
    }
}
