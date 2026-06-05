<?php

namespace App\Repositories;

use App\Models\Download;
use Illuminate\Database\Eloquent\Collection;

class DownloadRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Download);
    }

    public function findByToken(string $token): ?Download
    {
        return Download::where('token', $token)->first();
    }

    public function findByTokenOrFail(string $token): Download
    {
        return Download::where('token', $token)->firstOrFail();
    }

    public function findReadyByVideoAndFormat(int $videoId, string $format = 'mp4'): ?Download
    {
        return Download::where('video_id', $videoId)
            ->where('format', $format)
            ->where('status', 'ready')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();
    }

    public function findExpired(): Collection
    {
        return Download::where('expires_at', '<', now())
            ->where('status', 'ready')
            ->get();
    }

    public function markAsExpired(Download $download): bool
    {
        return $download->update(['status' => 'expired']);
    }

    public function updateProgress(Download $download, int $progress): bool
    {
        return $download->update(['progress' => $progress]);
    }

    public function markAsReady(Download $download, string $filePath, int $fileSize): bool
    {
        return $download->update([
            'status' => 'ready',
            'progress' => 100,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'expires_at' => now()->addHours(24),
        ]);
    }

    public function markAsFailed(Download $download, string $errorMessage): bool
    {
        return $download->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    public function markAsDownloaded(Download $download): bool
    {
        return $download->update(['downloaded_at' => now()]);
    }
}
