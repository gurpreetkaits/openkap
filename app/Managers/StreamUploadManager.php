<?php

namespace App\Managers;

use App\Data\CompleteStreamUploadData;
use App\Data\StartStreamSessionData;
use App\Data\StreamChunkData;
use App\Data\StreamChunkReceiptData;
use App\Data\StreamSessionStatusData;
use App\Exceptions\MissingChunksException;
use App\Exceptions\StreamSessionException;
use App\Jobs\ConvertCameraToMp4Job;
use App\Jobs\GenerateThumbnailJob;
use App\Jobs\GenerateTranscriptionJob;
use App\Jobs\RemuxWebmJob;
use App\Jobs\UploadToBunnyJob;
use App\Models\User;
use App\Models\Video;
use App\Repositories\UserSettingRepository;
use App\Repositories\VideoRepository;
use App\Repositories\VideoZoomSettingRepository;
use App\Services\BunnyStreamService;
use App\Services\ChunkStorageService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Owns the lifecycle of a chunked recording upload.
 *
 * The correctness rules live here:
 *   - chunks are stored independently and never appended in place, so
 *     concurrent uploads cannot lose each other's data;
 *   - a session is only turned into a Video once the server can prove it
 *     holds every chunk the client recorded;
 *   - a session that fails that proof is kept on disk so the client can
 *     re-upload the gap and finish.
 */
class StreamUploadManager
{
    /**
     * How far past the plan limit a recording may run before it is worth
     * logging. Covers the service worker's twenty-second backup check.
     */
    public const DURATION_GRACE_SECONDS = 30;

    public function __construct(
        protected ChunkStorageService $chunks,
        protected VideoRepository $videos,
        protected VideoZoomSettingRepository $zoomSettings,
        protected UserSettingRepository $userSettings,
        protected BunnyStreamService $bunny,
    ) {}

    public function isValidSessionId(string $sessionId): bool
    {
        return (bool) preg_match(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/',
            $sessionId
        );
    }

    /**
     * @return array{session_id: string, storage_type: string, will_use_bunny: bool}
     */
    public function startSession(StartStreamSessionData $data): array
    {
        $sessionId = Str::uuid()->toString();

        $this->chunks->createSession($sessionId, [
            'user_id' => $data->user_id,
            'title' => $data->title,
            'mime_type' => $data->mime_type,
            'has_camera' => $data->has_camera,
            'quality' => $data->quality,
            'started_at' => now()->toISOString(),
        ]);

        return [
            'session_id' => $sessionId,
            'storage_type' => 'local',
            'will_use_bunny' => $this->bunny->isConfigured(),
        ];
    }

    /**
     * Store a single chunk.
     *
     * Every call is independent: no shared counter is read, mutated or
     * written, which is what makes parallel chunk uploads safe.
     */
    public function receiveChunk(StreamChunkData $data): StreamChunkReceiptData
    {
        $this->authorize($data->session_id, $data->user_id);

        $type = $data->type === ChunkStorageService::TYPE_CAMERA
            ? ChunkStorageService::TYPE_CAMERA
            : ChunkStorageService::TYPE_VIDEO;

        $size = $this->chunks->storeChunk(
            $data->session_id,
            $data->chunk_index,
            $data->source_path,
            $type
        );

        $received = $this->chunks->receivedIndexes($data->session_id, $type);

        return new StreamChunkReceiptData(
            chunk_index: $data->chunk_index,
            chunk_size: $size,
            type: $type,
            chunks_received: count($received),
            total_size: $this->chunks->totalSize($data->session_id, $type),
        );
    }

    public function status(string $sessionId, int $userId, ?int $expectedChunks = null): StreamSessionStatusData
    {
        $session = $this->authorize($sessionId, $userId);

        $received = $this->chunks->receivedIndexes($sessionId);
        $lastChunkAt = $this->chunks->lastChunkAt($sessionId);

        return new StreamSessionStatusData(
            session_id: $sessionId,
            title: (string) ($session['title'] ?? 'Recording'),
            chunks_received: count($received),
            received_indexes: $received,
            missing_indexes: $expectedChunks !== null
                ? $this->chunks->missingIndexes($sessionId, $expectedChunks)
                : null,
            total_size: $this->chunks->totalSize($sessionId),
            started_at: $session['started_at'] ?? null,
            last_chunk_at: $lastChunkAt !== null ? date(DATE_ATOM, $lastChunkAt) : null,
        );
    }

    public function cancel(string $sessionId, int $userId): void
    {
        $this->authorize($sessionId, $userId);

        $this->chunks->deleteSession($sessionId);
    }

    /**
     * Turn a finished session into a Video.
     *
     * @throws MissingChunksException when the upload has holes in it.
     * @throws StreamSessionException on ownership, quota or state problems.
     */
    public function complete(CompleteStreamUploadData $data): Video
    {
        $session = $this->authorize($data->session_id, $data->user_id);

        $video = $this->chunks->withCompletionLock(
            $data->session_id,
            fn () => $this->completeLocked($data, $session)
        );

        if ($video === null) {
            throw StreamSessionException::completionInProgress();
        }

        return $video;
    }

    private function completeLocked(CompleteStreamUploadData $data, array $session): Video
    {
        $this->guardAgainstMissingChunks($data);

        $user = User::find($data->user_id);

        if (! $user) {
            throw StreamSessionException::forbidden();
        }

        $this->guardQuota($user, $data);

        $sessionDir = $this->chunks->sessionDir($data->session_id);
        $videoPath = "{$sessionDir}/assembled/video.webm";

        $written = $this->chunks->assemble($data->session_id, $videoPath);

        if ($written === 0) {
            throw StreamSessionException::empty();
        }

        $hasCamera = (bool) ($data->has_camera ?? $session['has_camera'] ?? false);
        $cameraPath = null;

        if ($hasCamera) {
            $candidate = "{$sessionDir}/assembled/camera.webm";

            if ($this->chunks->assemble($data->session_id, $candidate, ChunkStorageService::TYPE_CAMERA) > 0) {
                $cameraPath = $candidate;
            }
        }

        $willUseBunny = $this->bunny->isConfigured();
        $workspace = $user->ownedWorkspaces()->first();

        $videoData = [
            'user_id' => $user->id,
            'workspace_id' => $workspace?->id,
            'title' => $data->title ?? $session['title'] ?? 'Recording',
            'description' => null,
            'duration' => (int) ($data->duration ?: 0),
            'is_public' => true,
            'storage_type' => $willUseBunny ? 'bunny' : 'local',
            'has_camera' => $cameraPath !== null,
            'camera_conversion_status' => $cameraPath !== null ? 'pending' : 'completed',
            'camera_conversion_progress' => $cameraPath !== null ? 0 : 100,
        ];

        if ($willUseBunny) {
            $videoData['bunny_status'] = 'pending';
        }

        $video = $this->videos->createVideo($videoData);

        $this->createZoomSettings($video, $user, $data);

        $video->addMedia($videoPath)
            ->usingFileName("video_{$video->id}.webm")
            ->toMediaCollection('videos');

        if ($cameraPath !== null) {
            $this->attachCamera($video, $cameraPath, $sessionDir);
        }

        $user->increment('videos_count');

        Log::info('Stream upload completed', [
            'session_id' => $data->session_id,
            'video_id' => $video->id,
            'user_id' => $user->id,
            'chunks' => count($this->chunks->receivedIndexes($data->session_id)),
            'expected_chunks' => $data->expected_chunks,
            'bytes' => $written,
        ]);

        $this->chunks->deleteSession($data->session_id);

        $this->dispatchPostProcessing($video, $willUseBunny);

        return $video;
    }

    /**
     * Refuse to build a video out of an incomplete upload.
     *
     * The session directory is intentionally left intact so the extension
     * can push the missing indexes and call complete again.
     */
    private function guardAgainstMissingChunks(CompleteStreamUploadData $data): void
    {
        if ($data->expected_chunks === null || $data->expected_chunks <= 0) {
            return;
        }

        $missing = $this->chunks->missingIndexes($data->session_id, $data->expected_chunks);

        if ($missing === []) {
            return;
        }

        $received = count($this->chunks->receivedIndexes($data->session_id));

        Log::warning('Stream upload incomplete — refusing to finalise', [
            'session_id' => $data->session_id,
            'user_id' => $data->user_id,
            'expected_chunks' => $data->expected_chunks,
            'received_chunks' => $received,
            'missing_count' => count($missing),
            'missing_sample' => array_slice($missing, 0, 20),
        ]);

        throw new MissingChunksException($missing, $data->expected_chunks, $received);
    }

    private function guardQuota(User $user, CompleteStreamUploadData $data): void
    {
        if (! $user->canRecordVideo()) {
            $this->chunks->deleteSession($data->session_id);

            throw StreamSessionException::limitReached(
                'You have reached your video limit. Upgrade to Pro to continue recording.'
            );
        }

        $duration = $data->duration ?: null;

        if ($duration === null || $user->hasActiveSubscription()) {
            return;
        }

        $minDuration = $user->getMinRecordingSeconds();

        if ($duration < $minDuration) {
            $this->chunks->deleteSession($data->session_id);

            throw StreamSessionException::rejected(
                "Recording must be at least {$minDuration} seconds.",
                'duration_too_short'
            );
        }

        // An over-length recording is NOT rejected.
        //
        // The plan limit is a stop trigger, not a reason to destroy work the
        // user already waited to upload. The client stops the recorder when
        // the limit is reached, and it can only ever do so a beat late — the
        // in-page timer fires on a one-second tick and the service worker's
        // backup check runs every twenty. Rejecting `duration > limit` meant
        // any free user who recorded up to the cap uploaded the whole thing
        // and then lost it, which is exactly what happened in production.
        //
        // Monthly recording minutes remain enforced downstream from a
        // server-probed duration, so nothing here is load-bearing for
        // billing.
        $maxDuration = $user->getMaxRecordingSeconds();

        if ($duration > $maxDuration + self::DURATION_GRACE_SECONDS) {
            // Saved anyway, but worth seeing: either a client failed to stop
            // itself or someone is bypassing the limit.
            Log::warning('Recording saved well past the plan limit', [
                'session_id' => $data->session_id,
                'user_id' => $user->id,
                'duration' => $duration,
                'max_duration' => $maxDuration,
            ]);
        }
    }

    private function createZoomSettings(Video $video, User $user, CompleteStreamUploadData $data): void
    {
        $zoomEnabled = $this->userSettings->isAutoZoomEnabled($user);
        $zoomEvents = $data->zoom_events;

        $this->zoomSettings->createForVideo($video, [
            'enabled' => $zoomEnabled,
            'zoom_level' => $data->zoom_level ?? $this->userSettings->get($user, 'default_zoom_level'),
            'duration_ms' => $data->zoom_duration_ms ?? $this->userSettings->get($user, 'default_zoom_duration_ms'),
            'events' => is_array($zoomEvents) ? ($zoomEvents['events'] ?? null) : null,
            'recording_resolution' => is_array($zoomEvents) ? ($zoomEvents['recording_resolution'] ?? null) : null,
            'status' => $zoomEnabled ? 'pending' : 'disabled',
            'progress' => 0,
        ]);
    }

    /**
     * Remux the camera track before attaching it — chunked WebM has no
     * seekable container header until it is rewritten.
     */
    private function attachCamera(Video $video, string $cameraPath, string $sessionDir): void
    {
        $ffmpegPath = config('media-library.ffmpeg_path');
        $remuxedPath = "{$sessionDir}/assembled/camera_remuxed.webm";

        $remuxCmd = sprintf(
            'timeout 60 %s -y -fflags +genpts -i %s -c copy %s 2>&1',
            escapeshellarg((string) $ffmpegPath),
            escapeshellarg($cameraPath),
            escapeshellarg($remuxedPath)
        );

        exec($remuxCmd, $remuxOutput, $remuxReturn);

        $finalCameraPath = ($remuxReturn === 0 && file_exists($remuxedPath) && filesize($remuxedPath) > 1000)
            ? $remuxedPath
            : $cameraPath;

        $video->addMedia($finalCameraPath)
            ->usingFileName("camera_{$video->id}.webm")
            ->toMediaCollection('camera');

        $this->videos->updateVideo($video, [
            'camera_conversion_status' => 'completed',
            'camera_conversion_progress' => 100,
        ]);
    }

    private function dispatchPostProcessing(Video $video, bool $willUseBunny): void
    {
        dispatch(function () use ($video, $willUseBunny) {
            try {
                if ($willUseBunny) {
                    $video->update([
                        'conversion_status' => 'completed',
                        'conversion_progress' => 100,
                    ]);

                    UploadToBunnyJob::dispatch($video);
                } else {
                    RemuxWebmJob::dispatch($video);
                }
            } catch (\Throwable $e) {
                Log::error('Failed to dispatch conversion job', [
                    'video_id' => $video->id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                GenerateThumbnailJob::dispatch($video);
            } catch (\Throwable $e) {
                Log::error('Failed to dispatch thumbnail job', [
                    'video_id' => $video->id,
                    'error' => $e->getMessage(),
                ]);
            }

            if ($video->has_camera) {
                try {
                    ConvertCameraToMp4Job::dispatch($video)->delay(now()->addSeconds(5));
                } catch (\Throwable $e) {
                    Log::error('Failed to dispatch camera conversion job', [
                        'video_id' => $video->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            try {
                GenerateTranscriptionJob::dispatch($video, generateSummary: false, generateTitle: true);
            } catch (\Throwable $e) {
                Log::error('Failed to dispatch transcription job', [
                    'video_id' => $video->id,
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();
    }

    /**
     * @return array<string, mixed> The session descriptor.
     */
    private function authorize(string $sessionId, int $userId): array
    {
        if (! $this->isValidSessionId($sessionId)) {
            throw StreamSessionException::notFound();
        }

        $session = $this->chunks->readSession($sessionId);

        if ($session === null) {
            throw StreamSessionException::notFound();
        }

        if (! isset($session['user_id']) || (int) $session['user_id'] !== $userId) {
            Log::warning('Stream session ownership mismatch', [
                'session_id' => $sessionId,
                'auth_user_id' => $userId,
                'session_user_id' => $session['user_id'] ?? null,
            ]);

            throw StreamSessionException::forbidden();
        }

        return $session;
    }
}
