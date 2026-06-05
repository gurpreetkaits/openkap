<?php

namespace App\Http\Controllers;

use App\Data\DownloadOptionsData;
use App\Http\Resources\DownloadResource;
use App\Managers\DownloadManager;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function __construct(
        protected DownloadManager $downloadManager,
    ) {}

    /**
     * Request a new download. Returns 202 with a download token, 200 if cached,
     * or {mode: "redirect"} for Bunny-hosted videos.
     * POST /api/downloads
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'video_id' => 'required|integer|exists:videos,id',
            'quality' => 'sometimes|string|in:1080p,720p,480p,original',
            'include_camera' => 'sometimes|boolean',
            'camera_position' => 'sometimes|string|in:bottom-right,bottom-left,top-right,top-left',
            'camera_size' => 'sometimes|numeric|between:0.1,0.4',
            'include_captions' => 'sometimes|boolean',
        ]);

        $user = Auth::user();
        $video = Video::findOrFail($request->video_id);

        if ($video->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Bunny still encoding — frontend should retry
        if ($video->isBunnyVideo() && $video->bunny_status && ! in_array($video->bunny_status, ['ready', 'error'])) {
            return response()->json([
                'mode' => 'processing',
                'bunny_status' => $video->bunny_status,
                'message' => 'Video is being processed by Bunny CDN. Try again in a moment.',
            ], 202);
        }

        // Bunny ready — return a signed proxy URL; no Download record needed.
        if ($video->isBunnyVideo() && $video->bunny_video_id && $video->bunny_status === 'ready') {
            $proxyUrl = URL::temporarySignedRoute(
                'videos.download-bunny',
                now()->addMinutes(15),
                ['id' => $video->id]
            );

            return response()->json([
                'mode' => 'redirect',
                'url' => $proxyUrl,
                'file_name' => ($video->title ?? 'video').'.mp4',
            ]);
        }

        $options = new DownloadOptionsData(
            include_camera: $request->boolean('include_camera', true),
            camera_position: $request->string('camera_position', 'bottom-right')->toString(),
            camera_size: (float) $request->input('camera_size', 0.25),
            include_captions: $request->boolean('include_captions', false),
            quality: $request->string('quality', '1080p')->toString(),
        );

        $download = $this->downloadManager->request($video, $user, $options);

        Log::info('DownloadController::store', [
            'download_id' => $download->id,
            'video_id' => $video->id,
            'user_id' => $user->id,
            'cached' => $download->isReady(),
        ]);

        return (new DownloadResource($download))
            ->response()
            ->setStatusCode($download->isReady() ? 200 : 202);
    }

    /**
     * Check download status by token (public — no auth required).
     * GET /api/downloads/{token}
     */
    public function show(string $token): JsonResponse
    {
        $download = $this->downloadManager->findByToken($token);

        if (! $download) {
            return response()->json(['message' => 'Download not found'], 404);
        }

        return response()->json([
            'data' => new DownloadResource($download),
        ]);
    }

    /**
     * Get real-time progress. Uses short polling with cache.
     * GET /api/downloads/{id}/progress
     */
    public function progress(int $id): JsonResponse
    {
        $download = \App\Models\Download::find($id);

        if (! $download) {
            return response()->json(['message' => 'Download not found'], 404);
        }

        $progress = $this->downloadManager->getProgress($download);

        return response()->json($progress);
    }

    /**
     * Download the file (token-based, no session auth needed).
     * Supports Range headers for resumable downloads.
     * GET /api/downloads/{token}/file
     */
    public function file(string $token): BinaryFileResponse|StreamedResponse|JsonResponse
    {
        $download = $this->downloadManager->findByToken($token);

        if (! $download) {
            return response()->json(['message' => 'Download not found'], 404);
        }

        if ($download->isExpired()) {
            return response()->json(['message' => 'Download link has expired. Please request a new download.'], 410);
        }

        if (! $download->isReady()) {
            return response()->json([
                'message' => 'Download is not ready yet',
                'status' => $download->status,
                'progress' => $download->progress,
            ], 202);
        }

        $filePath = $this->downloadManager->getFilePath($download);

        if (! $filePath) {
            return response()->json(['message' => 'Download file not found. Please request a new download.'], 404);
        }

        $fileName = 'video.mp4';

        // Mark as downloaded
        $this->downloadManager->markDownloaded($download);

        // Stream with range support for resumable downloads
        return response()->file($filePath, [
            'Content-Type' => 'video/mp4',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Accept-Ranges' => 'bytes',
        ]);
    }

    /**
     * Request a download for a shared video (no auth, share token based).
     * POST /api/downloads/shared
     */
    public function storeShared(Request $request): JsonResponse
    {
        $request->validate([
            'share_token' => 'required|string|size:64',
        ]);

        $video = Video::where('share_token', $request->share_token)
            ->where('is_public', true)
            ->first();

        if (! $video) {
            return response()->json(['message' => 'Video not found or is private'], 404);
        }

        if ($video->share_expires_at && $video->share_expires_at->isPast()) {
            return response()->json(['message' => 'This share link has expired'], 410);
        }

        $download = $this->downloadManager->request($video);

        Log::info('DownloadController::storeShared', [
            'download_id' => $download->id,
            'video_id' => $video->id,
            'share_token' => $request->share_token,
        ]);

        return (new DownloadResource($download))
            ->response()
            ->setStatusCode($download->isReady() ? 200 : 202);
    }
}
