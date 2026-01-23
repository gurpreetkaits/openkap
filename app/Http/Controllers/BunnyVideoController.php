<?php

namespace App\Http\Controllers;

use App\Models\Video;
use App\Services\BunnyStreamService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BunnyVideoController extends Controller
{
    public function __construct(
        protected BunnyStreamService $bunnyService
    ) {}

    /**
     * Initialize a new video upload
     * Creates video entry in Bunny and returns TUS upload credentials
     *
     * POST /api/bunny/videos/create
     */
    public function create(Request $request): JsonResponse
    {
        // Validate request
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        // Check if user has active subscription for Bunny encoding
        // Free users cannot use Bunny encoding - they should use local storage
        // This check comes FIRST so free users get a clear message before any Bunny checks
        if (! $user->shouldEncodeVideos()) {
            return response()->json([
                'error' => 'subscription_required',
                'message' => 'Video encoding requires an active subscription. Free accounts can upload up to 10 videos without encoding.',
                'use_local_storage' => true,
                'upgrade_url' => config('services.frontend.url').'/subscription',
            ], 403);
        }

        // Check user quota
        if (! $user->canRecordVideo()) {
            return response()->json([
                'error' => 'video_limit_reached',
                'message' => 'You have reached your video limit. Upgrade to Pro to continue recording.',
                'videos_count' => $user->getVideosCount(),
                'remaining_quota' => $user->getRemainingVideoQuota(),
                'upgrade_url' => config('services.frontend.url').'/subscription',
            ], 403);
        }

        // Check if Bunny is configured
        if (! $this->bunnyService->isConfigured()) {
            return response()->json([
                'error' => 'bunny_not_configured',
                'message' => 'Bunny Stream is not configured. Please contact support.',
            ], 503);
        }

        try {
            // Create video in Bunny Stream
            $bunnyVideo = $this->bunnyService->createVideo($validated['title']);

            // Create video record in our database
            $video = Video::create([
                'user_id' => $user->id,
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'bunny_video_id' => $bunnyVideo['guid'],
                'bunny_library_id' => $this->bunnyService->getLibraryId(),
                'bunny_status' => 'pending',
                'storage_type' => 'bunny',
                'is_public' => true,
                'share_token' => Str::random(64),
            ]);

            // Generate TUS upload credentials
            $uploadCredentials = $this->bunnyService->generateUploadCredentials($bunnyVideo['guid']);

            Log::info('Bunny video created', [
                'user_id' => $user->id,
                'video_id' => $video->id,
                'bunny_video_id' => $bunnyVideo['guid'],
            ]);

            return response()->json([
                'success' => true,
                'video' => [
                    'id' => $video->id,
                    'title' => $video->title,
                    'share_token' => $video->share_token,
                ],
                'bunny_video_id' => $bunnyVideo['guid'],
                'upload_credentials' => $uploadCredentials,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create Bunny video', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'creation_failed',
                'message' => 'Failed to initialize video upload. Please try again.',
            ], 500);
        }
    }

    /**
     * Mark upload as complete
     * Called by client after TUS upload finishes
     *
     * POST /api/bunny/videos/{id}/complete
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $video = Video::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('storage_type', 'bunny')
            ->first();

        if (! $video) {
            return response()->json([
                'error' => 'not_found',
                'message' => 'Video not found',
            ], 404);
        }

        if (! $video->bunny_video_id) {
            return response()->json([
                'error' => 'invalid_state',
                'message' => 'Video does not have a Bunny video ID',
            ], 400);
        }

        try {
            // Update status to processing
            $video->update([
                'bunny_status' => 'processing',
            ]);

            // Get initial status from Bunny
            $bunnyStatus = $this->bunnyService->getVideoStatus($video->bunny_video_id);

            // Update with any available metadata
            $video->update([
                'bunny_status' => $bunnyStatus['status'],
                'duration' => $bunnyStatus['duration'] > 0 ? $bunnyStatus['duration'] : $video->duration,
                'bunny_file_size' => $bunnyStatus['size'],
                'bunny_resolution' => $bunnyStatus['height'] > 0 ? "{$bunnyStatus['height']}p" : null,
            ]);

            Log::info('Bunny upload completed', [
                'video_id' => $video->id,
                'bunny_video_id' => $video->bunny_video_id,
                'status' => $bunnyStatus['status'],
            ]);

            return response()->json([
                'success' => true,
                'video' => [
                    'id' => $video->id,
                    'title' => $video->title,
                    'status' => $bunnyStatus['status'],
                    'share_url' => $video->getShareUrl(),
                    'share_token' => $video->share_token,
                ],
                'message' => 'Upload complete. Video is being processed.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to complete Bunny upload', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'completion_failed',
                'message' => 'Failed to complete upload. Please try again.',
            ], 500);
        }
    }

    /**
     * Get video status from Bunny
     *
     * GET /api/bunny/videos/{id}/status
     */
    public function status(int $id): JsonResponse
    {
        $video = Video::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('storage_type', 'bunny')
            ->first();

        if (! $video) {
            return response()->json([
                'error' => 'not_found',
                'message' => 'Video not found',
            ], 404);
        }

        if (! $video->bunny_video_id) {
            return response()->json([
                'status' => $video->bunny_status,
                'message' => 'Video not yet uploaded to Bunny',
            ]);
        }

        try {
            $bunnyStatus = $this->bunnyService->getVideoStatus($video->bunny_video_id);

            // Update local status if changed
            if ($video->bunny_status !== $bunnyStatus['status']) {
                $video->update([
                    'bunny_status' => $bunnyStatus['status'],
                    'duration' => $bunnyStatus['duration'] > 0 ? $bunnyStatus['duration'] : $video->duration,
                    'bunny_file_size' => $bunnyStatus['size'],
                    'bunny_resolution' => $bunnyStatus['height'] > 0 ? "{$bunnyStatus['height']}p" : null,
                ]);
            }

            return response()->json([
                'status' => $bunnyStatus['status'],
                'progress' => $bunnyStatus['encodeProgress'],
                'duration' => $bunnyStatus['duration'],
                'resolution' => $video->bunny_resolution,
                'is_ready' => $bunnyStatus['status'] === 'ready',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get Bunny status', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => $video->bunny_status,
                'message' => 'Could not fetch latest status from Bunny',
            ]);
        }
    }

    /**
     * Get signed playback URLs for a video
     *
     * GET /api/bunny/videos/{id}/playback
     */
    public function playback(int $id): JsonResponse
    {
        $video = Video::where('id', $id)
            ->where('storage_type', 'bunny')
            ->first();

        if (! $video) {
            return response()->json([
                'error' => 'not_found',
                'message' => 'Video not found',
            ], 404);
        }

        // Check access - owner can always access, others only if public
        $userId = Auth::id();
        if ($video->user_id !== $userId && ! $video->is_public) {
            return response()->json([
                'error' => 'access_denied',
                'message' => 'This video is private',
            ], 403);
        }

        if (! $video->bunny_video_id) {
            return response()->json([
                'error' => 'not_ready',
                'message' => 'Video is not yet available',
            ], 400);
        }

        // Check if video is ready or transcoding (both can be played)
        if (! in_array($video->bunny_status, ['ready', 'transcoding'])) {
            return response()->json([
                'error' => 'processing',
                'message' => 'Video is still being processed',
                'status' => $video->bunny_status,
            ], 202);
        }

        try {
            // Get current status from Bunny for available resolutions
            $bunnyStatus = $this->bunnyService->getVideoStatus($video->bunny_video_id);
            $playbackUrls = $this->bunnyService->generateSignedPlaybackUrl($video->bunny_video_id);

            // Parse available resolutions (Bunny returns "360p,480p,720p,1080p")
            $availableResolutions = [];
            if (! empty($bunnyStatus['availableResolutions'])) {
                $availableResolutions = array_map('trim', explode(',', $bunnyStatus['availableResolutions']));
            }

            return response()->json([
                'video' => [
                    'id' => $video->id,
                    'title' => $video->title,
                    'description' => $video->description,
                    'duration' => $bunnyStatus['duration'] ?: $video->duration,
                    'resolution' => $video->bunny_resolution,
                    'created_at' => $video->created_at->toISOString(),
                    'status' => $bunnyStatus['status'],
                    'encode_progress' => $bunnyStatus['encodeProgress'],
                    'available_resolutions' => $availableResolutions,
                ],
                'playback' => $playbackUrls,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate playback URLs', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'playback_failed',
                'message' => 'Failed to generate playback URLs',
            ], 500);
        }
    }

    /**
     * Get signed playback URLs for a shared video (by token)
     *
     * GET /api/bunny/share/{token}/playback
     */
    public function sharedPlayback(string $token): JsonResponse
    {
        $video = Video::where('share_token', $token)
            ->where('storage_type', 'bunny')
            ->where('is_public', true)
            ->first();

        if (! $video) {
            return response()->json([
                'error' => 'not_found',
                'message' => 'Video not found or is private',
            ], 404);
        }

        // Check share expiry
        if ($video->share_expires_at && $video->share_expires_at->isPast()) {
            return response()->json([
                'error' => 'expired',
                'message' => 'This share link has expired',
            ], 410);
        }

        if (! $video->bunny_video_id) {
            return response()->json([
                'error' => 'not_ready',
                'message' => 'Video is not yet available',
            ], 400);
        }

        // Check if video is ready or transcoding (both can be played)
        if (! in_array($video->bunny_status, ['ready', 'transcoding'])) {
            return response()->json([
                'error' => 'processing',
                'message' => 'Video is still being processed',
                'status' => $video->bunny_status,
            ], 202);
        }

        try {
            // Get current status from Bunny for available resolutions
            $bunnyStatus = $this->bunnyService->getVideoStatus($video->bunny_video_id);
            $playbackUrls = $this->bunnyService->generateSignedPlaybackUrl($video->bunny_video_id);

            // Parse available resolutions (Bunny returns "360p,480p,720p,1080p")
            $availableResolutions = [];
            if (! empty($bunnyStatus['availableResolutions'])) {
                $availableResolutions = array_map('trim', explode(',', $bunnyStatus['availableResolutions']));
            }

            return response()->json([
                'video' => [
                    'id' => $video->id,
                    'title' => $video->title,
                    'description' => $video->description,
                    'duration' => $bunnyStatus['duration'] ?: $video->duration,
                    'resolution' => $video->bunny_resolution,
                    'created_at' => $video->created_at->toISOString(),
                    'status' => $bunnyStatus['status'],
                    'encode_progress' => $bunnyStatus['encodeProgress'],
                    'available_resolutions' => $availableResolutions,
                    'owner' => [
                        'name' => $video->user->name,
                        'avatar' => $video->user->avatar_url,
                    ],
                ],
                'playback' => $playbackUrls,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate shared playback URLs', [
                'video_id' => $video->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'playback_failed',
                'message' => 'Failed to generate playback URLs',
            ], 500);
        }
    }

    /**
     * Delete a Bunny video
     *
     * DELETE /api/bunny/videos/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $video = Video::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('storage_type', 'bunny')
            ->first();

        if (! $video) {
            return response()->json([
                'error' => 'not_found',
                'message' => 'Video not found',
            ], 404);
        }

        try {
            // Delete from Bunny if video ID exists
            if ($video->bunny_video_id) {
                $this->bunnyService->deleteVideo($video->bunny_video_id);
            }

            // Delete from database
            $video->delete();

            Log::info('Bunny video deleted', [
                'video_id' => $id,
                'bunny_video_id' => $video->bunny_video_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Video deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete Bunny video', [
                'video_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'deletion_failed',
                'message' => 'Failed to delete video',
            ], 500);
        }
    }
}
