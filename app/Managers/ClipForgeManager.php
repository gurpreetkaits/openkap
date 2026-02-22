<?php

namespace App\Managers;

use App\Services\ClipForgeService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ClipForgeManager
{
    public function __construct(
        protected ClipForgeService $clipForgeService
    ) {}

    /**
     * Download a YouTube video and return its metadata
     */
    public function fetchYouTubeVideo(string $url): array
    {
        if (! $this->clipForgeService->validateYouTubeUrl($url)) {
            throw new \InvalidArgumentException('Invalid YouTube URL');
        }

        // Cleanup old files before downloading new ones
        $this->clipForgeService->cleanupStaleFiles();

        return $this->clipForgeService->downloadYouTube($url);
    }

    /**
     * Handle a local file upload
     */
    public function handleUpload(UploadedFile $file): array
    {
        $allowedMimes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'];
        $maxSize = 500 * 1024 * 1024; // 500MB

        if (! in_array($file->getMimeType(), $allowedMimes)) {
            throw new \InvalidArgumentException('Unsupported video format. Allowed: MP4, WebM, MOV, AVI, MKV');
        }

        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('File too large. Maximum size is 500MB');
        }

        // Cleanup old files
        $this->clipForgeService->cleanupStaleFiles();

        $sessionId = Str::uuid()->toString();
        $extension = $file->getClientOriginalExtension() ?: 'mp4';
        $filename = $sessionId.'.'.$extension;

        $file->move($this->clipForgeService->getTmpDir(), $filename);

        $filePath = $this->clipForgeService->getFilePath($filename);
        $metadata = $this->clipForgeService->probeVideo($filePath);

        return [
            'session_id' => $sessionId,
            'filename' => $filename,
            'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'duration' => $metadata['duration'],
            'size' => $metadata['size'],
            'width' => $metadata['width'],
            'height' => $metadata['height'],
        ];
    }

    /**
     * Extract a clip from a source video
     */
    public function extractClip(string $sourceFilename, float $startTime, float $endTime, string $format): array
    {
        // Validate format
        $allowedFormats = ['mp4', 'webm', 'gif', 'mp3'];
        if (! in_array($format, $allowedFormats)) {
            throw new \InvalidArgumentException('Unsupported format. Allowed: '.implode(', ', $allowedFormats));
        }

        // Validate timestamps
        if ($startTime < 0) {
            throw new \InvalidArgumentException('Start time cannot be negative');
        }

        if ($endTime <= $startTime) {
            throw new \InvalidArgumentException('End time must be greater than start time');
        }

        if ($endTime - $startTime > 600) {
            throw new \InvalidArgumentException('Clip duration cannot exceed 10 minutes');
        }

        // Check source file exists
        if (! $this->clipForgeService->fileExists($sourceFilename)) {
            throw new \RuntimeException('Source video not found. It may have expired.');
        }

        return $this->clipForgeService->extractClip($sourceFilename, $startTime, $endTime, $format);
    }

    /**
     * Get the path to a file for serving
     */
    public function getFilePath(string $filename): ?string
    {
        $safeName = basename($filename);

        if (! $this->clipForgeService->fileExists($safeName)) {
            return null;
        }

        return $this->clipForgeService->getFilePath($safeName);
    }

    /**
     * Get MIME type for a format string
     */
    public function getMimeType(string $filename): string
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        return $this->clipForgeService->getMimeType($ext);
    }
}
