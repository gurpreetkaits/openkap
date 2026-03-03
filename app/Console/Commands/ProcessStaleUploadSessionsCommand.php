<?php

namespace App\Console\Commands;

use App\Jobs\ConvertVideoToMp4Job;
use App\Jobs\GenerateThumbnailJob;
use App\Models\User;
use App\Models\Video;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessStaleUploadSessionsCommand extends Command
{
    protected $signature = 'uploads:process-stale
                            {--timeout=300 : Seconds of inactivity before auto-completing}
                            {--cleanup=3600 : Seconds before deleting empty/failed sessions}';

    protected $description = 'Auto-complete stale upload sessions that have chunks but no final complete call';

    public function handle()
    {
        $timeout = (int) $this->option('timeout');
        $cleanupTimeout = (int) $this->option('cleanup');
        $baseDir = storage_path('app/temp/stream-uploads');

        if (! is_dir($baseDir)) {
            $this->info('No upload sessions directory found.');

            return 0;
        }

        $sessions = glob("{$baseDir}/*", GLOB_ONLYDIR);
        $processed = 0;
        $cleaned = 0;

        foreach ($sessions as $sessionDir) {
            $sessionId = basename($sessionDir);
            $metadataPath = "{$sessionDir}/metadata.json";

            if (! file_exists($metadataPath)) {
                // No metadata, clean up
                $this->cleanupSession($sessionDir);
                $cleaned++;

                continue;
            }

            $metadata = json_decode(file_get_contents($metadataPath), true);
            $lastActivity = isset($metadata['last_chunk_at'])
                ? strtotime($metadata['last_chunk_at'])
                : strtotime($metadata['started_at']);

            $inactiveSeconds = time() - $lastActivity;

            // Check if video file has content
            $videoPath = "{$sessionDir}/video.webm";
            $videoSize = file_exists($videoPath) ? filesize($videoPath) : 0;

            // Has video data and is stale - auto-complete
            if ($videoSize > 0 && $inactiveSeconds >= $timeout) {
                $this->info("Auto-completing session {$sessionId} ({$videoSize} bytes, inactive {$inactiveSeconds}s)");

                try {
                    $this->autoCompleteSession($sessionId, $sessionDir, $metadata);
                    $processed++;
                } catch (\Exception $e) {
                    Log::error("Failed to auto-complete session {$sessionId}", [
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("Failed: {$e->getMessage()}");
                }

                continue;
            }

            // No video data and very stale - cleanup
            if ($videoSize === 0 && $inactiveSeconds >= $cleanupTimeout) {
                $this->info("Cleaning up empty session {$sessionId}");
                $this->cleanupSession($sessionDir);
                $cleaned++;
            }
        }

        $this->info("Processed: {$processed}, Cleaned: {$cleaned}");

        return 0;
    }

    private function autoCompleteSession(string $sessionId, string $sessionDir, array $metadata): void
    {
        $videoPath = "{$sessionDir}/video.webm";

        // Append any remaining pending chunks in order
        if (! empty($metadata['pending_chunks'])) {
            ksort($metadata['pending_chunks']);
            foreach ($metadata['pending_chunks'] as $index => $size) {
                $pendingPath = "{$sessionDir}/pending_{$index}.webm";
                if (file_exists($pendingPath)) {
                    $videoFile = fopen($videoPath, 'ab');
                    $chunkFile = fopen($pendingPath, 'rb');
                    stream_copy_to_stream($chunkFile, $videoFile);
                    fclose($chunkFile);
                    fclose($videoFile);
                }
            }
        }

        // Verify video file exists and has content
        if (! file_exists($videoPath) || filesize($videoPath) === 0) {
            throw new \Exception('Video file is empty');
        }

        // Create Video record
        $userId = $metadata['user_id'];
        $title = $metadata['title'] ?? 'Auto-recovered Recording';

        $video = Video::create([
            'user_id' => $userId,
            'title' => $title,
            'description' => null,
            'duration' => 0, // Will be updated by conversion job
            'is_public' => true,
        ]);

        // Add video to media library (already assembled)
        // Explicitly set MIME type — assembled .webm files are often misdetected as text/plain
        $video->addMedia($videoPath)
            ->usingFileName("video_{$video->id}.webm")
            ->withCustomProperties(['mime_type' => 'video/webm'])
            ->setCustomHeaders(['ContentType' => 'video/webm'])
            ->toMediaCollection('videos');

        // Force-update the media record's mime_type since Spatie may have detected it wrong
        $addedMedia = $video->getFirstMedia('videos');
        if ($addedMedia && $addedMedia->mime_type !== 'video/webm') {
            $addedMedia->mime_type = 'video/webm';
            $addedMedia->save();
        }

        // Log recovery
        Log::info('Auto-completing stale upload session', [
            'session_id' => $sessionId,
            'video_id' => $video->id,
            'user_id' => $userId,
            'total_size' => $metadata['total_size'] ?? 0,
        ]);

        // Dispatch background jobs for thumbnail and conversion
        GenerateThumbnailJob::dispatch($video);
        ConvertVideoToMp4Job::dispatch($video);

        // Increment user's video count
        $user = User::find($userId);
        if ($user) {
            $user->increment('videos_count');
        }

        // Clean up session
        $this->cleanupSession($sessionDir);
    }

    private function cleanupSession(string $sessionDir): void
    {
        if (! is_dir($sessionDir)) {
            return;
        }

        $files = glob("{$sessionDir}/*");
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($sessionDir);
    }
}
