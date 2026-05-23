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

    public function recordView(Request $request, $id): JsonResponse
    {
        $request->validate([
            'watch_duration' => 'nullable|integer|min:0',
            'completed' => 'nullable|boolean',
            'referrer' => 'nullable|string|max:1000',
            'timezone' => 'nullable|string|max:64',
            'session_id' => 'nullable|string|max:64',
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
            (int) $request->input('watch_duration', 0),
            (bool) $request->input('completed', false),
            $this->trackingPayload($request),
        );

        $statusCode = isset($result['created']) && $result['created'] ? 201 : 200;

        return response()->json($result, $statusCode);
    }

    public function recordSharedView(Request $request, $token): JsonResponse
    {
        $request->validate([
            'referrer' => 'nullable|string|max:1000',
            'timezone' => 'nullable|string|max:64',
            'session_id' => 'nullable|string|max:64',
        ]);

        $video = $this->videoRepository->findByShareToken($token);

        if (! $video || ! $video->is_public || ! $video->isShareLinkValid()) {
            return response()->json(['message' => 'Video not available'], 403);
        }

        $result = $this->viewManager->recordSharedView(
            $video,
            Auth::check() ? Auth::id() : null,
            $request->ip(),
            $request->header('User-Agent'),
            $this->trackingPayload($request),
        );

        $statusCode = isset($result['created']) && $result['created'] ? 201 : 200;

        return response()->json($result, $statusCode);
    }

    /**
     * Heartbeat from the player — updates progress for an existing session view.
     */
    public function recordProgress(Request $request, $id): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string|max:64',
            'progress_seconds' => 'required|integer|min:0',
            'watch_duration' => 'nullable|integer|min:0',
            'completed' => 'nullable|boolean',
        ]);

        $video = $this->videoRepository->findOrFail($id);

        if (! $video->is_public && (! Auth::check() || $video->user_id !== Auth::id())) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $view = $this->viewManager->recordProgress(
            $video,
            Auth::check() ? Auth::id() : null,
            $request->ip(),
            $request->input('session_id'),
            (int) $request->input('progress_seconds'),
            (int) $request->input('watch_duration', $request->input('progress_seconds')),
            (bool) $request->input('completed', false),
        );

        return response()->json([
            'message' => $view ? 'Progress recorded' : 'No active session view',
            'updated' => (bool) $view,
        ]);
    }

    public function recordSharedProgress(Request $request, $token): JsonResponse
    {
        $request->validate([
            'session_id' => 'required|string|max:64',
            'progress_seconds' => 'required|integer|min:0',
            'watch_duration' => 'nullable|integer|min:0',
            'completed' => 'nullable|boolean',
        ]);

        $video = $this->videoRepository->findByShareToken($token);

        if (! $video || ! $video->is_public || ! $video->isShareLinkValid()) {
            return response()->json(['message' => 'Video not available'], 403);
        }

        $view = $this->viewManager->recordProgress(
            $video,
            Auth::check() ? Auth::id() : null,
            $request->ip(),
            $request->input('session_id'),
            (int) $request->input('progress_seconds'),
            (int) $request->input('watch_duration', $request->input('progress_seconds')),
            (bool) $request->input('completed', false),
        );

        return response()->json([
            'message' => $view ? 'Progress recorded' : 'No active session view',
            'updated' => (bool) $view,
        ]);
    }

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
     * @return array{referrer: string|null, timezone: string|null, session_id: string|null}
     */
    protected function trackingPayload(Request $request): array
    {
        return [
            'referrer' => $request->input('referrer') ?: $request->header('Referer'),
            'timezone' => $request->input('timezone'),
            'session_id' => $request->input('session_id'),
        ];
    }
}
