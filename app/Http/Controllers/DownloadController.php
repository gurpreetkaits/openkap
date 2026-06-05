<?php

namespace App\Http\Controllers;

use App\Http\Resources\DownloadResource;
use App\Managers\DownloadManager;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function __construct(
        protected DownloadManager $downloadManager,
    ) {}

    /**
     * Request a new download. Always returns 202 with a download token.
     * POST /api/downloads
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'video_id' => 'required|integer|exists:videos,id',
        ]);

        $user = Auth::user();
        $video = Video::findOrFail($request->video_id);

        // For owner-only downloads, verify ownership
        if ($video->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $download = $this->downloadManager->request($video, $user);

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
