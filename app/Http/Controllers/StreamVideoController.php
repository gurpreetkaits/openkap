<?php

namespace App\Http\Controllers;

use App\Jobs\ConvertCameraToMp4Job;
use App\Jobs\GenerateThumbnailJob;
use App\Jobs\GenerateTranscriptionJob;
use App\Jobs\RemuxWebmJob;
use App\Jobs\UploadToBunnyJob;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoZoomSetting;
use App\Repositories\UserSettingRepository;
use App\Services\BunnyStreamService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StreamVideoController extends Controller
{
    public function __construct(
        protected UserSettingRepository $userSettings
    ) {}

    /**
     * Validate session ID is a strict UUID to prevent path traversal.
     */
    private function validateSessionId(string $sessionId): bool
    {
        return (bool) preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/', $sessionId);
    }

    /**
     * Start a new streaming upload session.
     * Always uses local chunked upload for speed during recording.
     * Bunny upload happens in background after recording completes.
     */
    public function startUpload(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'mime_type' => 'nullable|string',
            'has_camera' => 'nullable|boolean',
        ]);

        // Check if user can record video (subscription limit check)
        $user = Auth::user();
        if (! $user->canRecordVideo()) {
            return response()->json([
                'error' => 'video_limit_reached',
                'message' => 'Limit Reached',
                'detail' => 'You have reached your video limit. Upgrade to Pro to continue recording.',
                'current_plan' => $user->hasActiveSubscription() ? 'pro' : 'free',
                'videos_count' => $user->getVideosCount(),
                'remaining_quota' => $user->getRemainingVideoQuota(),
                'upgrade_url' => config('services.frontend.url').'/subscription',
            ], 403);
        }

        // Always use local chunked upload for fast recording
        // Bunny upload happens in background after complete
        $sessionId = Str::uuid()->toString();

        // Create temp directory
        $sessionDir = storage_path("app/temp/stream-uploads/{$sessionId}");
        if (! file_exists($sessionDir)) {
            mkdir($sessionDir, 0755, true);
        }

        // Create empty video file that chunks will be appended to
        touch("{$sessionDir}/video.webm");

        $hasCamera = (bool) $request->input('has_camera', false);

        // Create empty camera file if camera is enabled
        if ($hasCamera) {
            touch("{$sessionDir}/camera.webm");
        }

        // Store session metadata
        $metadata = [
            'user_id' => Auth::id(),
            'title' => $request->title,
            'mime_type' => $request->mime_type ?? 'video/webm',
            'started_at' => now()->toISOString(),
            'chunks_received' => 0,
            'next_expected_chunk' => 0,
            'total_size' => 0,
            'pending_chunks' => [],
            'has_camera' => $hasCamera,
            'camera_chunks_received' => 0,
            'camera_next_expected_chunk' => 0,
            'camera_total_size' => 0,
            'camera_pending_chunks' => [],
        ];

        file_put_contents("{$sessionDir}/metadata.json", json_encode($metadata));

        // All users get Bunny HLS if configured
        $willUseBunny = app(BunnyStreamService::class)->isConfigured();

        return response()->json([
            'session_id' => $sessionId,
            'storage_type' => 'local',
            'will_use_bunny' => $willUseBunny,
            'message' => 'Upload session started',
        ]);
    }

    /**
     * Receive a video chunk during recording.
     * Chunks are appended directly to video file in order.
     */
    public function uploadChunk(Request $request, $sessionId)
    {
        if (! $this->validateSessionId($sessionId)) {
            return response()->json(['message' => 'Invalid session'], 404);
        }

        $request->validate([
            'chunk' => 'required|file|max:51200',
            'chunk_index' => 'required|integer|min:0',
            'type' => 'nullable|string|in:video,camera',
        ]);

        $chunkIndex = (int) $request->chunk_index;

        $sessionDir = storage_path("app/temp/stream-uploads/{$sessionId}");
        $metadataPath = "{$sessionDir}/metadata.json";

        // Verify session exists
        if (! file_exists($metadataPath)) {
            Log::warning('Chunk upload: session metadata not found', [
                'session_id' => $sessionId,
                'user_id' => $userId ?? Auth::id(),
                'chunk_index' => $chunkIndex,
                'ip' => $request->ip(),
            ]);
            return response()->json(['message' => 'Invalid session'], 404);
        }

        // Read metadata
        $metadata = json_decode(file_get_contents($metadataPath), true);

        // Verify user owns this session
        $userId = Auth::id();
        if (! $userId || $metadata['user_id'] !== $userId) {
            Log::warning('Chunk upload: user mismatch', [
                'session_id' => $sessionId, 'auth_user_id' => $userId,
                'metadata_user_id' => $metadata['user_id'], 'chunk_index' => $chunkIndex,
            ]);
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $chunkFile = $request->file('chunk');
        $chunkSize = $chunkFile->getSize();
        $chunkType = $request->input('type', 'video');

        // Camera chunks go to a separate file with separate tracking
        if ($chunkType === 'camera') {
            $targetPath = "{$sessionDir}/camera.webm";
            $nextKey = 'camera_next_expected_chunk';
            $sizeKey = 'camera_total_size';
            $pendingKey = 'camera_pending_chunks';
            $countKey = 'camera_chunks_received';
            $pendingPrefix = 'camera_pending';
        } else {
            $targetPath = "{$sessionDir}/video.webm";
            $nextKey = 'next_expected_chunk';
            $sizeKey = 'total_size';
            $pendingKey = 'pending_chunks';
            $countKey = 'chunks_received';
            $pendingPrefix = 'pending';
        }

        // Ensure target file exists (camera file may not exist for older sessions)
        if (! file_exists($targetPath)) {
            touch($targetPath);
        }

        // Initialize metadata keys if missing (backward compat with sessions started before camera support)
        $metadata[$nextKey] = $metadata[$nextKey] ?? 0;
        $metadata[$sizeKey] = $metadata[$sizeKey] ?? 0;
        $metadata[$pendingKey] = $metadata[$pendingKey] ?? [];
        $metadata[$countKey] = $metadata[$countKey] ?? 0;

        // If this is the expected next chunk, append directly to target file
        if ($chunkIndex === $metadata[$nextKey]) {
            $this->appendChunkToVideo($targetPath, $chunkFile->getRealPath());
            $metadata[$nextKey]++;
            $metadata[$sizeKey] += $chunkSize;

            // Check if we have pending chunks that can now be appended
            while (isset($metadata[$pendingKey][$metadata[$nextKey]])) {
                $pendingPath = "{$sessionDir}/{$pendingPrefix}_{$metadata[$nextKey]}.webm";
                if (file_exists($pendingPath)) {
                    $this->appendChunkToVideo($targetPath, $pendingPath);
                    $metadata[$sizeKey] += $metadata[$pendingKey][$metadata[$nextKey]];
                    unlink($pendingPath);
                }
                unset($metadata[$pendingKey][$metadata[$nextKey]]);
                $metadata[$nextKey]++;
            }
        } else {
            // Out of order chunk - save temporarily
            $pendingPath = "{$sessionDir}/{$pendingPrefix}_{$chunkIndex}.webm";
            $chunkFile->move($sessionDir, "{$pendingPrefix}_{$chunkIndex}.webm");
            $metadata[$pendingKey][$chunkIndex] = $chunkSize;
        }

        $metadata[$countKey]++;
        $metadata['last_chunk_at'] = now()->toISOString();

        file_put_contents($metadataPath, json_encode($metadata));

        return response()->json([
            'message' => 'Chunk received',
            'chunk_index' => $chunkIndex,
            'chunk_size' => $chunkSize,
            'total_size' => $metadata[$sizeKey],
            'chunks_received' => $metadata[$countKey],
            'type' => $chunkType,
        ]);
    }

    /**
     * Append chunk data to video file efficiently.
     */
    private function appendChunkToVideo(string $videoPath, string $chunkPath): void
    {
        $videoFile = fopen($videoPath, 'ab');
        $chunkFile = fopen($chunkPath, 'rb');
        stream_copy_to_stream($chunkFile, $videoFile);
        fclose($chunkFile);
        fclose($videoFile);
    }

    /**
     * Complete the streaming upload.
     * Returns immediately with share URL.
     * Bunny upload happens in background.
     */
    public function completeUpload(Request $request, $sessionId)
    {
        if (! $this->validateSessionId($sessionId)) {
            return response()->json(['message' => 'Invalid session'], 404);
        }

        $request->validate([
            'duration' => 'nullable|integer|min:1',
            'title' => 'nullable|string|max:255',
            'has_camera' => 'nullable|boolean',
            'zoom_enabled' => 'nullable|boolean',
            'zoom_level' => 'nullable|numeric|min:1.2|max:4',
            'zoom_duration_ms' => 'nullable|integer|min:100|max:2000',
            'zoom_events' => 'nullable|array',
            'zoom_events.recording_resolution' => 'nullable|array',
            'zoom_events.recording_resolution.width' => 'nullable|integer',
            'zoom_events.recording_resolution.height' => 'nullable|integer',
            'zoom_events.events' => 'nullable|array',
        ]);

        $sessionDir = storage_path("app/temp/stream-uploads/{$sessionId}");
        $metadataPath = "{$sessionDir}/metadata.json";
        $videoPath = "{$sessionDir}/video.webm";

        // Verify session exists
        if (! file_exists($metadataPath)) {
            Log::warning('Chunk upload: session metadata not found', [
                'session_id' => $sessionId,
                'user_id' => $userId ?? Auth::id(),
                'chunk_index' => $chunkIndex,
                'ip' => $request->ip(),
            ]);
            return response()->json(['message' => 'Invalid session'], 404);
        }

        // Read metadata
        $metadata = json_decode(file_get_contents($metadataPath), true);

        // Verify user owns this session
        $userId = Auth::id();
        if (! $userId || $metadata['user_id'] !== $userId) {
            Log::warning('Chunk upload: user mismatch', [
                'session_id' => $sessionId, 'auth_user_id' => $userId,
                'metadata_user_id' => $metadata['user_id'], 'chunk_index' => $chunkIndex,
            ]);
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Verify video file exists and has content
        if (! file_exists($videoPath) || filesize($videoPath) === 0) {
            return response()->json(['message' => 'No video data received'], 400);
        }

        // Defense in depth: re-check subscription limit at completion time
        $user = User::find($userId);
        if (! $user || ! $user->canRecordVideo()) {
            $this->cleanupSession($sessionId);

            return response()->json([
                'error' => 'video_limit_reached',
                'message' => 'You have reached your video limit. Upgrade to Pro to continue recording.',
            ], 403);
        }

        // Duration enforcement for free users
        $duration = $request->duration ?: null;
        if ($duration !== null && ! $user->hasActiveSubscription()) {
            $minDuration = $user->getMinRecordingSeconds();
            $maxDuration = $user->getMaxRecordingSeconds();

            if ($duration < $minDuration) {
                $this->cleanupSession($sessionId);

                return response()->json([
                    'error' => 'duration_too_short',
                    'message' => "Recording must be at least {$minDuration} seconds.",
                ], 422);
            }

            if ($duration > $maxDuration) {
                $this->cleanupSession($sessionId);

                return response()->json([
                    'error' => 'duration_too_long',
                    'message' => 'Recording cannot exceed '.($maxDuration / 60).' minutes on the free plan.',
                ], 422);
            }
        }

        // Append any remaining pending chunks (video)
        if (! empty($metadata['pending_chunks'])) {
            ksort($metadata['pending_chunks']);
            foreach ($metadata['pending_chunks'] as $index => $size) {
                $pendingPath = "{$sessionDir}/pending_{$index}.webm";
                if (file_exists($pendingPath)) {
                    $this->appendChunkToVideo($videoPath, $pendingPath);
                    unlink($pendingPath);
                }
            }
        }

        // Append any remaining pending camera chunks
        $cameraPath = "{$sessionDir}/camera.webm";
        $cameraPendingChunks = $metadata['camera_pending_chunks'] ?? [];
        if (! empty($cameraPendingChunks)) {
            ksort($cameraPendingChunks);
            foreach ($cameraPendingChunks as $index => $size) {
                $pendingPath = "{$sessionDir}/camera_pending_{$index}.webm";
                if (file_exists($pendingPath)) {
                    $this->appendChunkToVideo($cameraPath, $pendingPath);
                    unlink($pendingPath);
                }
            }
        }

        // Determine if this recording has a camera track
        $hasCamera = (bool) ($request->input('has_camera') ?? $metadata['has_camera'] ?? false);
        $hasCameraFile = $hasCamera && file_exists($cameraPath) && filesize($cameraPath) > 0;

        $user = User::find($userId);

        // All users get Bunny HLS if configured
        $willUseBunny = app(BunnyStreamService::class)->isConfigured();

        // Create Video record
        $title = $request->title ?? $metadata['title'];
        $workspace = $user->ownedWorkspaces()->first();

        $videoData = [
            'user_id' => $userId,
            'workspace_id' => $workspace?->id,
            'title' => $title,
            'description' => null,
            'duration' => (int) ($request->duration ?: 0),
            'is_public' => true,
            'storage_type' => $willUseBunny ? 'bunny' : 'local',
            'has_camera' => $hasCameraFile,
            'camera_conversion_status' => $hasCameraFile ? 'pending' : 'completed',
            'camera_conversion_progress' => $hasCameraFile ? 0 : 100,
        ];

        if ($willUseBunny) {
            $videoData['bunny_status'] = 'pending';
        }

        $video = Video::create($videoData);

        // Get user for settings check
        $user = User::find($userId);

        // Always check zoom settings from user_settings only
        $zoomEnabled = $user ? $this->userSettings->isAutoZoomEnabled($user) : false;
        $userZoomLevel = $user ? $this->userSettings->get($user, 'default_zoom_level') : 2.0;
        $userZoomDuration = $user ? $this->userSettings->get($user, 'default_zoom_duration_ms') : 500;

        // Always create zoom settings to store events for every video
        // Events are tracked regardless of zoom enabled status
        // Zoom processing only happens if enabled is true
        $zoomEvents = $request->input('zoom_events');

        $zoomSettings = VideoZoomSetting::create([
            'video_id' => $video->id,
            'enabled' => $zoomEnabled,  // Only process zoom if enabled
            'zoom_level' => $request->input('zoom_level', $userZoomLevel),
            'duration_ms' => $request->input('zoom_duration_ms', $userZoomDuration),
            'events' => is_array($zoomEvents) ? ($zoomEvents['events'] ?? null) : null,
            'recording_resolution' => is_array($zoomEvents) ? ($zoomEvents['recording_resolution'] ?? null) : null,
            'status' => $zoomEnabled ? 'pending' : 'disabled',
            'progress' => 0,
        ]);

        // Add video to media library
        $video->addMedia($videoPath)
            ->usingFileName("video_{$video->id}.webm")
            ->toMediaCollection('videos');

        // Add camera track as separate media if available
        // Remux first to fix WebM container headers (same issue as main video)
        if ($hasCameraFile) {
            $ffmpegPath = config('media-library.ffmpeg_path');

            // Remux WebM to fix container headers from chunked recording.
            // Use -fflags +genpts to regenerate timestamps (MediaRecorder
            // produces variable framerate WebM that causes glitchy playback).
            $remuxedCameraPath = "{$sessionDir}/camera_remuxed.webm";
            // Bound the in-request remux with `timeout` so a large/slow camera track
            // can't stall the completeUpload request past upstream (Cloudflare/proxy)
            // timeouts. On non-zero exit we fall back to the raw camera file below.
            // (Deferring this to a queued job would be the fuller fix, but the session
            // dir is cleaned up right after, so that needs careful file-lifecycle work.)
            $remuxCmd = sprintf(
                'timeout 60 %s -y -fflags +genpts -i %s -c copy %s 2>&1',
                escapeshellarg($ffmpegPath),
                escapeshellarg($cameraPath),
                escapeshellarg($remuxedCameraPath)
            );
            exec($remuxCmd, $remuxOutput, $remuxReturn);

            $finalCameraPath = ($remuxReturn === 0 && file_exists($remuxedCameraPath) && filesize($remuxedCameraPath) > 1000)
                ? $remuxedCameraPath
                : $cameraPath;

            $video->addMedia($finalCameraPath)
                ->usingFileName("camera_{$video->id}.webm")
                ->toMediaCollection('camera');

            // Camera WebM is playable immediately after remux
            $video->update([
                'camera_conversion_status' => 'completed',
                'camera_conversion_progress' => 100,
            ]);
        }

        // Increment user's video count. Monthly recording minutes are
        // credited downstream using a server-probed duration:
        //   - Bunny path:  BunnyWebhookController on `ready`
        //   - Local path:  RemuxWebmJob after ffprobe
        // We never trust the client-supplied duration to charge the cap.
        $user = User::find($userId);
        if ($user) {
            $user->increment('videos_count');
        }

        // Clean up session directory
        $this->cleanupSession($sessionId);

        // Return response immediately - video is ready to share!
        $response = response()->json([
            'message' => 'Video uploaded successfully',
            'video' => [
                'id' => $video->id,
                'title' => $video->title,
                'duration' => $video->duration,
                'url' => url("/api/share/video/{$video->share_token}/stream"),
                'thumbnail' => $video->getThumbnailUrl(),
                'share_url' => $video->getShareUrl(),
                'share_token' => $video->share_token,
                'is_public' => $video->is_public,
                'storage_type' => $video->storage_type,
                'has_camera' => $video->has_camera,
                'camera_url' => $video->getCameraUrl(),
                'created_at' => $video->created_at->toISOString(),
            ],
        ], 201);

        // Dispatch background jobs AFTER response is sent
        // Each dispatch is isolated so one failure doesn't block the others
        dispatch(function () use ($video, $willUseBunny) {
            try {
                if ($willUseBunny) {
                    // Bunny handles encoding — skip local remux, mark conversion complete
                    // so transcription can start immediately from the raw WebM
                    $video->update([
                        'conversion_status' => 'completed',
                        'conversion_progress' => 100,
                    ]);

                    Log::info('Dispatching UploadToBunnyJob', ['video_id' => $video->id]);
                    UploadToBunnyJob::dispatch($video);
                } else {
                    // Local-only: remux WebM for seekable browser playback
                    Log::info('Dispatching RemuxWebmJob', ['video_id' => $video->id]);
                    RemuxWebmJob::dispatch($video);
                }
            } catch (\Throwable $e) {
                Log::error('Failed to dispatch conversion job', [
                    'video_id' => $video->id,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                Log::info('Dispatching GenerateThumbnailJob', ['video_id' => $video->id]);
                GenerateThumbnailJob::dispatch($video);
            } catch (\Throwable $e) {
                Log::error('Failed to dispatch thumbnail job', [
                    'video_id' => $video->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // Convert camera WebM to MP4 in background
            if ($video->has_camera) {
                try {
                    Log::info('Dispatching ConvertCameraToMp4Job', ['video_id' => $video->id]);
                    ConvertCameraToMp4Job::dispatch($video)->delay(now()->addSeconds(5));
                } catch (\Throwable $e) {
                    Log::error('Failed to dispatch camera conversion job', [
                        'video_id' => $video->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            try {
                Log::info('Dispatching GenerateTranscriptionJob', ['video_id' => $video->id]);
                GenerateTranscriptionJob::dispatch($video, generateSummary: false, generateTitle: true);
            } catch (\Throwable $e) {
                Log::error('Failed to dispatch transcription job', [
                    'video_id' => $video->id,
                    'error' => $e->getMessage(),
                ]);
            }
        })->afterResponse();

        return $response;
    }

    /**
     * Cancel/abort an upload session.
     */
    public function cancelUpload(Request $request, $sessionId)
    {
        if (! $this->validateSessionId($sessionId)) {
            return response()->json(['message' => 'Invalid session'], 404);
        }

        $chunkDir = storage_path("app/temp/stream-uploads/{$sessionId}");

        if (! file_exists("{$chunkDir}/metadata.json")) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        $metadata = json_decode(file_get_contents("{$chunkDir}/metadata.json"), true);

        // Verify user owns this session
        $userId = Auth::id();
        if (! $userId || $metadata['user_id'] !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->cleanupSession($sessionId);

        return response()->json([
            'message' => 'Upload cancelled',
        ]);
    }

    /**
     * Get upload session status.
     */
    public function getStatus($sessionId)
    {
        if (! $this->validateSessionId($sessionId)) {
            return response()->json(['message' => 'Invalid session'], 404);
        }

        $chunkDir = storage_path("app/temp/stream-uploads/{$sessionId}");

        if (! file_exists("{$chunkDir}/metadata.json")) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        $metadata = json_decode(file_get_contents("{$chunkDir}/metadata.json"), true);

        // Verify user owns this session
        $userId = Auth::id();
        if (! $userId || $metadata['user_id'] !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'session_id' => $sessionId,
            'title' => $metadata['title'],
            'chunks_received' => $metadata['chunks_received'] ?? 0,
            'total_size' => $metadata['total_size'],
            'started_at' => $metadata['started_at'],
            'last_chunk_at' => $metadata['last_chunk_at'] ?? null,
        ]);
    }

    /**
     * Clean up a session's temporary files.
     */
    private function cleanupSession($sessionId)
    {
        $chunkDir = storage_path("app/temp/stream-uploads/{$sessionId}");

        if (file_exists($chunkDir)) {
            $files = glob("{$chunkDir}/*");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($chunkDir);
        }
    }
}
