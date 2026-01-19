<?php

namespace App\Http\Controllers;

use App\Jobs\ConvertVideoToMp4Job;
use App\Jobs\GenerateThumbnailJob;
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
        protected BunnyStreamService $bunnyService,
        protected UserSettingRepository $userSettings
    ) {}

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
            'use_bunny' => $this->bunnyService->isConfigured(), // Flag for Bunny upload
        ];

        file_put_contents("{$sessionDir}/metadata.json", json_encode($metadata));

        return response()->json([
            'session_id' => $sessionId,
            'storage_type' => 'local', // Always local during recording
            'will_use_bunny' => $this->bunnyService->isConfigured(),
            'message' => 'Upload session started',
        ]);
    }

    /**
     * Receive a video chunk during recording.
     * Chunks are appended directly to video file in order.
     */
    public function uploadChunk(Request $request, $sessionId)
    {
        $request->validate([
            'chunk' => 'required|file',
            'chunk_index' => 'required|integer|min:0',
        ]);

        $sessionDir = storage_path("app/temp/stream-uploads/{$sessionId}");
        $metadataPath = "{$sessionDir}/metadata.json";
        $videoPath = "{$sessionDir}/video.webm";

        // Verify session exists
        if (! file_exists($metadataPath)) {
            return response()->json(['message' => 'Invalid session'], 404);
        }

        // Read metadata
        $metadata = json_decode(file_get_contents($metadataPath), true);

        // Verify user owns this session
        $userId = Auth::id();
        if (! $userId || $metadata['user_id'] !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $chunkIndex = (int) $request->chunk_index;
        $chunkFile = $request->file('chunk');
        $chunkSize = $chunkFile->getSize();

        // If this is the expected next chunk, append directly to video file
        if ($chunkIndex === $metadata['next_expected_chunk']) {
            // Append this chunk to video file
            $this->appendChunkToVideo($videoPath, $chunkFile->getRealPath());
            $metadata['next_expected_chunk']++;
            $metadata['total_size'] += $chunkSize;

            // Check if we have pending chunks that can now be appended
            while (isset($metadata['pending_chunks'][$metadata['next_expected_chunk']])) {
                $pendingPath = "{$sessionDir}/pending_{$metadata['next_expected_chunk']}.webm";
                if (file_exists($pendingPath)) {
                    $this->appendChunkToVideo($videoPath, $pendingPath);
                    $metadata['total_size'] += $metadata['pending_chunks'][$metadata['next_expected_chunk']];
                    unlink($pendingPath);
                }
                unset($metadata['pending_chunks'][$metadata['next_expected_chunk']]);
                $metadata['next_expected_chunk']++;
            }
        } else {
            // Out of order chunk - save temporarily
            $pendingPath = "{$sessionDir}/pending_{$chunkIndex}.webm";
            $chunkFile->move($sessionDir, "pending_{$chunkIndex}.webm");
            $metadata['pending_chunks'][$chunkIndex] = $chunkSize;
        }

        $metadata['chunks_received']++;
        $metadata['last_chunk_at'] = now()->toISOString();

        file_put_contents($metadataPath, json_encode($metadata));

        return response()->json([
            'message' => 'Chunk received',
            'chunk_index' => $chunkIndex,
            'chunk_size' => $chunkSize,
            'total_size' => $metadata['total_size'],
            'chunks_received' => $metadata['chunks_received'],
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
        $request->validate([
            'duration' => 'nullable|integer',
            'title' => 'nullable|string|max:255',
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
            return response()->json(['message' => 'Invalid session'], 404);
        }

        // Read metadata
        $metadata = json_decode(file_get_contents($metadataPath), true);

        // Verify user owns this session
        $userId = Auth::id();
        if (! $userId || $metadata['user_id'] !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Verify video file exists and has content
        if (! file_exists($videoPath) || filesize($videoPath) === 0) {
            return response()->json(['message' => 'No video data received'], 400);
        }

        // Append any remaining pending chunks
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

        // Determine storage type based on Bunny availability
        $useBunny = $metadata['use_bunny'] ?? $this->bunnyService->isConfigured();

        // Create Video record
        $title = $request->title ?? $metadata['title'];
        $videoData = [
            'user_id' => $userId,
            'title' => $title,
            'description' => null,
            'duration' => $request->duration ?? 0,
            'is_public' => true,
            'storage_type' => $useBunny ? 'bunny' : 'local',
        ];

        if ($useBunny) {
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

        // Increment user's video count
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
                'bunny_status' => $video->bunny_status,
                'created_at' => $video->created_at->toISOString(),
            ],
        ], 201);

        // Dispatch background jobs AFTER response is sent
        dispatch(function () use ($video, $useBunny) {
            // Generate thumbnail
            Log::info('Dispatching GenerateThumbnailJob', ['video_id' => $video->id]);
            GenerateThumbnailJob::dispatch($video);

            if ($useBunny) {
                // Upload to Bunny in background (HLS will be ready from Bunny)
                Log::info('Dispatching UploadToBunnyJob', ['video_id' => $video->id]);
                UploadToBunnyJob::dispatch($video);
            } else {
                // Convert locally
                Log::info('Dispatching ConvertVideoToMp4Job', ['video_id' => $video->id]);
                ConvertVideoToMp4Job::dispatch($video);
            }
        })->afterResponse();

        return $response;
    }

    /**
     * Cancel/abort an upload session.
     */
    public function cancelUpload(Request $request, $sessionId)
    {
        $chunkDir = storage_path("app/temp/stream-uploads/{$sessionId}");

        if (file_exists("{$chunkDir}/metadata.json")) {
            $metadata = json_decode(file_get_contents("{$chunkDir}/metadata.json"), true);

            // Verify user owns this session
            $userId = Auth::id();
            if (! $userId || $metadata['user_id'] !== $userId) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
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
