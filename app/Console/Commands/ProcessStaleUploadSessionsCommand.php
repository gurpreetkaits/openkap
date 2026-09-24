<?php

namespace App\Console\Commands;

use App\Data\CompleteStreamUploadData;
use App\Managers\StreamUploadManager;
use App\Services\ChunkStorageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Safety net for recordings whose client disappeared before completing —
 * a crashed browser, a closed laptop, a lost connection at the last second.
 *
 * Anything with chunks on disk and no activity for a while is assembled and
 * turned into a video rather than being thrown away.
 */
class ProcessStaleUploadSessionsCommand extends Command
{
    protected $signature = 'uploads:process-stale
                            {--timeout=300 : Seconds of inactivity before auto-completing}
                            {--cleanup=3600 : Seconds before deleting empty/failed sessions}';

    protected $description = 'Auto-complete stale upload sessions that have chunks but no final complete call';

    public function handle(ChunkStorageService $chunks, StreamUploadManager $uploads): int
    {
        $timeout = (int) $this->option('timeout');
        $cleanupTimeout = (int) $this->option('cleanup');

        if (! is_dir($chunks->baseDir())) {
            $this->info('No upload sessions directory found.');

            return self::SUCCESS;
        }

        $processed = 0;
        $cleaned = 0;

        foreach ($chunks->allSessionIds() as $sessionId) {
            $session = $chunks->readSession($sessionId);

            if ($session === null) {
                $chunks->deleteSession($sessionId);
                $cleaned++;

                continue;
            }

            $lastActivity = $chunks->lastChunkAt($sessionId)
                ?? strtotime((string) ($session['started_at'] ?? 'now'));

            $inactiveSeconds = time() - $lastActivity;
            $receivedChunks = count($chunks->receivedIndexes($sessionId));

            // Anything recoverable counts, including a legacy session whose
            // video.webm is empty but whose pending_* files hold the whole
            // recording — the exact wreckage the old metadata race produced.
            $bytes = $chunks->recoverableBytes($sessionId);
            $hasData = $bytes > 0;

            if ($hasData && $inactiveSeconds >= $timeout) {
                $this->info("Auto-completing {$sessionId} ({$receivedChunks} chunks, inactive {$inactiveSeconds}s)");

                try {
                    // expected_chunks is deliberately null: the client is gone
                    // and cannot tell us what it recorded, so we salvage
                    // everything that did arrive rather than refusing.
                    $video = $uploads->complete(new CompleteStreamUploadData(
                        session_id: $sessionId,
                        user_id: (int) $session['user_id'],
                        title: $session['title'] ?? 'Recovered Recording',
                        duration: null,
                        expected_chunks: null,
                        expected_camera_chunks: null,
                        has_camera: (bool) ($session['has_camera'] ?? false),
                        zoom_level: null,
                        zoom_duration_ms: null,
                        zoom_events: null,
                    ));

                    Log::info('Recovered stale upload session', [
                        'session_id' => $sessionId,
                        'video_id' => $video->id,
                        'chunks' => $receivedChunks,
                    ]);

                    $processed++;
                } catch (\Throwable $e) {
                    Log::error("Failed to auto-complete session {$sessionId}", [
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("Failed: {$e->getMessage()}");
                }

                continue;
            }

            if (! $hasData && $inactiveSeconds >= $cleanupTimeout) {
                $this->info("Cleaning up empty session {$sessionId}");
                $chunks->deleteSession($sessionId);
                $cleaned++;
            }
        }

        $this->info("Processed: {$processed}, Cleaned: {$cleaned}");

        return self::SUCCESS;
    }
}
