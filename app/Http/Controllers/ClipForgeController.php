<?php

namespace App\Http\Controllers;

use App\Managers\ClipForgeManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClipForgeController extends Controller
{
    public function __construct(
        protected ClipForgeManager $clipForgeManager
    ) {}

    /**
     * POST /api/clipforge/youtube
     * Download a YouTube video
     */
    public function youtube(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|string|url',
        ]);

        try {
            $result = $this->clipForgeManager->fetchYouTubeVideo($request->input('url'));

            return response()->json([
                'success' => true,
                'video' => $result,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            Log::error('ClipForge YouTube download failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to download video. Please check the URL and try again.',
            ], 500);
        }
    }

    /**
     * POST /api/clipforge/upload
     * Upload a local video file
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'video' => 'required|file|max:512000', // 500MB
        ]);

        try {
            $result = $this->clipForgeManager->handleUpload($request->file('video'));

            return response()->json([
                'success' => true,
                'video' => $result,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('ClipForge upload failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process uploaded video.',
            ], 500);
        }
    }

    /**
     * POST /api/clipforge/clip
     * Extract a clip from a source video
     */
    public function clip(Request $request): JsonResponse
    {
        $request->validate([
            'source' => 'required|string',
            'start' => 'required|numeric|min:0',
            'end' => 'required|numeric|gt:start',
            'format' => 'required|string|in:mp4,webm,gif,mp3',
        ]);

        try {
            $result = $this->clipForgeManager->extractClip(
                $request->input('source'),
                (float) $request->input('start'),
                (float) $request->input('end'),
                $request->input('format')
            );

            return response()->json([
                'success' => true,
                'clip' => $result,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\RuntimeException $e) {
            Log::error('ClipForge clip extraction failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to extract clip. Please try again.',
            ], 500);
        }
    }

    /**
     * GET /api/clipforge/video/{filename}
     * Serve a source video for preview (with Range support for seeking)
     */
    public function video(string $filename): BinaryFileResponse|JsonResponse
    {
        $path = $this->clipForgeManager->getFilePath($filename);

        if (! $path) {
            return response()->json(['message' => 'Video not found'], 404);
        }

        $mimeType = $this->clipForgeManager->getMimeType($filename);

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Accept-Ranges' => 'bytes',
        ]);
    }

    /**
     * GET /api/clipforge/download/{filename}
     * Download a clip as attachment
     */
    public function download(string $filename): BinaryFileResponse|JsonResponse
    {
        $path = $this->clipForgeManager->getFilePath($filename);

        if (! $path) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }
}
