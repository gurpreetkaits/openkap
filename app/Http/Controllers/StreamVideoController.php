<?php

namespace App\Http\Controllers;

use App\Jobs\ConvertVideoToMp4Job;
use App\Jobs\GenerateThumbnailJob;
use App\Models\User;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StreamVideoController extends Controller
{
    /**
     * Start a new streaming upload session.
     * Returns a session ID that will be used to upload chunks.
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

        // Generate a unique session ID
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
            'pending_chunks' => [], // For out-of-order chunks
        ];

        file_put_contents("{$sessionDir}/metadata.json", json_encode($metadata));

        return response()->json([
            'session_id' => $sessionId,
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
     * Video is already assembled - just create the record and dispatch jobs.
     */
    public function completeUpload(Request $request, $sessionId)
    {
        $request->validate([
            'duration' => 'nullable|integer',
            'title' => 'nullable|string|max:255',
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

        // Append any remaining pending chunks (handle out-of-order arrivals)
        // This is fast - just appending any late-arriving chunks
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

        // Create Video record
        $title = $request->title ?? $metadata['title'];
        $video = Video::create([
            'user_id' => $userId,
            'title' => $title,
            'description' => null,
            'duration' => $request->duration ?? 0,
            'is_public' => true,
        ]);

        // Add video to media library (already assembled from chunks!)
        // This is fast - just copying the file, no processing
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

        // NOW return response immediately - video is ready to play!
        // Background jobs will generate thumbnail and convert to MP4/HLS
        $response = response()->json([
            'message' => 'Video uploaded successfully',
            'video' => [
                'id' => $video->id,
                'title' => $video->title,
                'duration' => $video->duration,
                'url' => url("/api/share/video/{$video->share_token}/stream"),
                'thumbnail' => $video->getThumbnailUrl(),
                'share_url' => $video->getShareUrl(),
                'is_public' => $video->is_public,
                'created_at' => $video->created_at->toISOString(),
            ],
        ], 201);

        // Dispatch background jobs AFTER response is sent
        // These are the slow operations (FFmpeg)
        dispatch(function () use ($video, $userId) {
            // Dispatch thumbnail generation job
            Log::info('Dispatching GenerateThumbnailJob from StreamVideoController', [
                'video_id' => $video->id,
                'title' => $video->title,
                'user_id' => $userId,
            ]);
            GenerateThumbnailJob::dispatch($video);

            // Dispatch conversion job
            Log::info('Dispatching ConvertVideoToMp4Job from StreamVideoController', [
                'video_id' => $video->id,
                'title' => $video->title,
                'user_id' => $userId,
            ]);
            ConvertVideoToMp4Job::dispatch($video);
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
            'chunks_received' => count($metadata['chunks']),
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
            // Delete all files in directory
            $files = glob("{$chunkDir}/*");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            // Remove directory
            rmdir($chunkDir);
        }
    }
}
