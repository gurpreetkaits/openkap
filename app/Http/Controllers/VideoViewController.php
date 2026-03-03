<?php

namespace App\Http\Controllers;

use App\Managers\VideoViewManager;
use App\Repositories\VideoRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoViewController extends Controller
{
    public function __construct(
        protected VideoViewManager $viewManager,
        protected VideoRepository $videoRepository
    ) {}

    /**
     * Record a video view.
     */
    public function recordView(Request $request, $id): JsonResponse
    {
        $request->validate([
            'watch_duration' => 'nullable|integer|min:0',
            'completed' => 'nullable|boolean',
        ]);

        $video = $this->videoRepository->findOrFail($id);

        if (! $video->is_public && (! Auth::check() || $video->user_id !== Auth::id())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $result = $this->viewManager->recordView(
            $video,
            Auth::check() ? Auth::id() : null,
            $request->ip(),
            $request->header('User-Agent'),
            $request->input('watch_duration', 0),
            $request->input('completed', false)
        );

        $statusCode = isset($result['created']) && $result['created'] ? 201 : 200;

        return response()->json($result, $statusCode);
    }

    /**
     * Get view statistics for a video.
     */
    public function getStats($id): JsonResponse
    {
        $video = $this->videoRepository->findOrFail($id);

        if ($video->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $stats = $this->viewManager->getVideoStats($video);

        return response()->json($stats);
    }

    /**
     * Record a view for a shared video.
     */
    public function recordSharedView(Request $request, $token): JsonResponse
    {
        $video = $this->videoRepository->findByShareToken($token);

        if (! $video || ! $video->is_public || ! $video->isShareLinkValid()) {
            return response()->json(['message' => 'Video not available'], 403);
        }

        $result = $this->viewManager->recordSharedView(
            $video,
            Auth::check() ? Auth::id() : null,
            $request->ip(),
            $request->header('User-Agent')
        );

        $statusCode = isset($result['created']) && $result['created'] ? 201 : 200;

        return response()->json($result, $statusCode);
    }
}
