<?php

namespace App\Http\Controllers;

use App\Data\CompleteStreamUploadData;
use App\Data\StartStreamSessionData;
use App\Data\StreamChunkData;
use App\Exceptions\MissingChunksException;
use App\Exceptions\StreamSessionException;
use App\Managers\StreamUploadManager;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StreamVideoController extends Controller
{
    public function __construct(
        protected StreamUploadManager $uploads
    ) {}

    /**
     * Start a new streaming upload session.
     */
    public function startUpload(Request $request): JsonResponse
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'mime_type' => 'nullable|string',
            'has_camera' => 'nullable|boolean',
            'quality' => 'nullable|string|max:20',
        ]);

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

        $session = $this->uploads->startSession(new StartStreamSessionData(
            user_id: $user->id,
            title: $request->string('title')->toString(),
            mime_type: $request->input('mime_type') ?? 'video/webm',
            has_camera: $request->boolean('has_camera'),
            quality: $request->input('quality'),
        ));

        return response()->json($session + ['message' => 'Upload session started']);
    }

    /**
     * Receive one chunk of a recording in progress.
     */
    public function uploadChunk(Request $request, string $sessionId): JsonResponse
    {
        $request->validate([
            'chunk' => 'required|file|max:51200',
            'chunk_index' => 'required|integer|min:0|max:'.\App\Services\ChunkStorageService::MAX_CHUNK_INDEX,
            'type' => 'nullable|string|in:video,camera',
        ]);

        try {
            $receipt = $this->uploads->receiveChunk(new StreamChunkData(
                session_id: $sessionId,
                user_id: (int) Auth::id(),
                chunk_index: (int) $request->input('chunk_index'),
                source_path: (string) $request->file('chunk')->getRealPath(),
                type: (string) $request->input('type', 'video'),
            ));
        } catch (StreamSessionException $e) {
            return $this->sessionError($e);
        }

        return response()->json($receipt->toArray() + ['message' => 'Chunk received']);
    }

    /**
     * Finalise a recording and turn it into a Video.
     */
    public function completeUpload(Request $request, string $sessionId): JsonResponse
    {
        $request->validate([
            'duration' => 'nullable|integer|min:1',
            'title' => 'nullable|string|max:255',
            'expected_chunks' => 'nullable|integer|min:0',
            'expected_camera_chunks' => 'nullable|integer|min:0',
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

        try {
            $video = $this->uploads->complete(new CompleteStreamUploadData(
                session_id: $sessionId,
                user_id: (int) Auth::id(),
                title: $request->input('title'),
                duration: $request->integer('duration') ?: null,
                expected_chunks: $request->has('expected_chunks') ? $request->integer('expected_chunks') : null,
                expected_camera_chunks: $request->has('expected_camera_chunks') ? $request->integer('expected_camera_chunks') : null,
                has_camera: $request->has('has_camera') ? $request->boolean('has_camera') : null,
                zoom_level: $request->has('zoom_level') ? (float) $request->input('zoom_level') : null,
                zoom_duration_ms: $request->has('zoom_duration_ms') ? $request->integer('zoom_duration_ms') : null,
                zoom_events: $request->input('zoom_events'),
            ));
        } catch (MissingChunksException $e) {
            // 409 rather than 201-with-a-broken-video: the extension still
            // holds these chunks locally and will re-upload them.
            return response()->json([
                'error' => 'missing_chunks',
                'message' => 'Some parts of this recording have not reached the server yet.',
                'missing' => $e->missing,
                'missing_count' => count($e->missing),
                'expected_chunks' => $e->expectedChunks,
                'received_chunks' => $e->receivedChunks,
            ], 409);
        } catch (StreamSessionException $e) {
            return $this->sessionError($e);
        }

        return response()->json([
            'message' => 'Video uploaded successfully',
            'video' => $this->videoPayload($video),
        ], 201);
    }

    /**
     * Cancel/abort an upload session.
     */
    public function cancelUpload(Request $request, string $sessionId): JsonResponse
    {
        try {
            $this->uploads->cancel($sessionId, (int) Auth::id());
        } catch (StreamSessionException $e) {
            return $this->sessionError($e);
        }

        return response()->json(['message' => 'Upload cancelled']);
    }

    /**
     * Report which chunks the server actually holds, so the client can
     * reconcile before completing.
     */
    public function getStatus(Request $request, string $sessionId): JsonResponse
    {
        $request->validate([
            'expected_chunks' => 'nullable|integer|min:0',
        ]);

        try {
            $status = $this->uploads->status(
                $sessionId,
                (int) Auth::id(),
                $request->has('expected_chunks') ? $request->integer('expected_chunks') : null
            );
        } catch (StreamSessionException $e) {
            return $this->sessionError($e);
        }

        return response()->json($status->toArray());
    }

    private function sessionError(StreamSessionException $e): JsonResponse
    {
        $payload = ['message' => $e->getMessage()] + $e->extra;

        if ($e->errorCode !== null) {
            $payload['error'] = $e->errorCode;
        }

        return response()->json($payload, $e->status);
    }

    /**
     * @return array<string, mixed>
     */
    private function videoPayload(Video $video): array
    {
        return [
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
        ];
    }
}
