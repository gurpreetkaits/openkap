<?php

namespace App\Managers;

use App\Models\Video;
use App\Models\VideoView;
use App\Repositories\UserRepository;
use App\Repositories\VideoRepository;
use App\Repositories\VideoViewRepository;
use App\Support\AnalyticsEnricher;

class VideoViewManager
{
    public function __construct(
        protected VideoViewRepository $viewRepository,
        protected VideoRepository $videoRepository,
        protected NotificationManager $notificationManager,
        protected UserRepository $users
    ) {}

    /**
     * @param  array{referrer?: string|null, timezone?: string|null, session_id?: string|null}  $tracking
     */
    public function recordView(
        Video $video,
        ?int $userId,
        ?string $ipAddress,
        ?string $userAgent,
        int $watchDuration = 0,
        bool $completed = false,
        array $tracking = []
    ): ?array {
        if ($userId && $userId === $video->user_id) {
            return ['message' => 'Own video view not recorded', 'view' => null];
        }

        $sessionId = $tracking['session_id'] ?? null;
        $existingView = $this->viewRepository->findRecentView($video->id, $userId, $ipAddress, $sessionId);

        if ($existingView) {
            $this->viewRepository->updateView($existingView, [
                'watch_duration' => max($existingView->watch_duration, $watchDuration),
                'progress_max_seconds' => max($existingView->progress_max_seconds, $watchDuration),
                'completed' => $completed || $existingView->completed,
            ]);

            return ['message' => 'View updated', 'view' => $existingView];
        }

        $view = $this->viewRepository->createView(
            $this->buildViewPayload($video, $userId, $ipAddress, $userAgent, $watchDuration, $completed, $tracking)
        );

        $this->notifyVideoOwnerIfNeeded($video, $userId, $view);

        return ['message' => 'View recorded', 'view' => $view, 'created' => true];
    }

    /**
     * @param  array{referrer?: string|null, timezone?: string|null, session_id?: string|null}  $tracking
     */
    public function recordSharedView(
        Video $video,
        ?int $userId,
        ?string $ipAddress,
        ?string $userAgent,
        array $tracking = []
    ): ?array {
        if ($userId && $userId === $video->user_id) {
            return ['message' => 'Own video view not recorded', 'view' => null];
        }

        $sessionId = $tracking['session_id'] ?? null;
        $existingView = $this->viewRepository->findRecentView($video->id, $userId, $ipAddress, $sessionId);

        if ($existingView) {
            return ['message' => 'View already recorded', 'view' => $existingView];
        }

        $view = $this->viewRepository->createView(
            $this->buildViewPayload($video, $userId, $ipAddress, $userAgent, 0, false, $tracking)
        );

        $this->notifyVideoOwnerIfNeeded($video, $userId, $view);

        return ['message' => 'View recorded', 'view' => $view, 'created' => true];
    }

    /**
     * Heartbeat from the player — updates progress on the most recent view in the session.
     * Returns null if no matching session view is found.
     */
    public function recordProgress(
        Video $video,
        ?int $userId,
        ?string $ipAddress,
        string $sessionId,
        int $progressSeconds,
        int $watchDuration,
        bool $completed
    ): ?VideoView {
        if ($userId && $userId === $video->user_id) {
            return null;
        }

        $view = $this->viewRepository->findBySessionId($video->id, $sessionId);

        if (! $view) {
            return null;
        }

        $this->viewRepository->updateView($view, [
            'watch_duration' => max($view->watch_duration, $watchDuration),
            'progress_max_seconds' => max($view->progress_max_seconds, $progressSeconds),
            'completed' => $completed || $view->completed,
        ]);

        return $view->refresh();
    }

    /**
     * @param  array{referrer?: string|null, timezone?: string|null, session_id?: string|null}  $tracking
     */
    protected function buildViewPayload(
        Video $video,
        ?int $userId,
        ?string $ipAddress,
        ?string $userAgent,
        int $watchDuration,
        bool $completed,
        array $tracking
    ): array {
        $ua = AnalyticsEnricher::parseUserAgent($userAgent);
        $ref = AnalyticsEnricher::classifyReferrer($tracking['referrer'] ?? null);
        $country = AnalyticsEnricher::countryFromTimezone($tracking['timezone'] ?? null);

        return [
            'video_id' => $video->id,
            'user_id' => $userId,
            'ip_address' => $userId ? null : $ipAddress,
            'user_agent' => $userAgent,
            'country_code' => $country[0] ?? null,
            'country' => $country[1] ?? null,
            'device_type' => $ua['device_type'],
            'browser' => $ua['browser'],
            'os' => $ua['os'],
            'referrer_source' => $ref['referrer_source'],
            'referrer_url' => $ref['referrer_url'],
            'session_id' => $tracking['session_id'] ?? null,
            'watch_duration' => $watchDuration,
            'progress_max_seconds' => $watchDuration,
            'completed' => $completed,
            'viewed_at' => now(),
        ];
    }

    public function getVideoStats(Video $video): array
    {
        $totalViews = $this->viewRepository->getTotalViews($video);
        $uniqueViewers = $this->viewRepository->getUniqueViewers($video);
        $authenticatedViews = $this->viewRepository->getAuthenticatedViews($video);
        $anonymousViews = $this->viewRepository->getAnonymousViews($video);
        $averageWatchDuration = $this->viewRepository->getAverageWatchDuration($video);
        $completedViews = $this->viewRepository->getCompletedViewsCount($video);
        $completionRate = $totalViews > 0 ? ($completedViews / $totalViews) * 100 : 0;
        $recentViewers = $this->viewRepository->getRecentViewers($video);

        return [
            'total_views' => $totalViews,
            'unique_viewers' => $uniqueViewers,
            'authenticated_views' => $authenticatedViews,
            'anonymous_views' => $anonymousViews,
            'average_watch_duration' => round($averageWatchDuration ?? 0, 2),
            'completion_rate' => round($completionRate, 2),
            'recent_viewers' => $recentViewers,
        ];
    }

    protected function notifyVideoOwnerIfNeeded(Video $video, ?int $userId, VideoView $view): void
    {
        if (! $userId || $userId === $video->user_id) {
            return;
        }

        $viewer = $this->users->findById($userId);
        if (! $viewer) {
            return;
        }

        $hasPreviousViews = $this->viewRepository->userHasPreviousViews($video->id, $userId, $view->id);

        if (! $hasPreviousViews) {
            $this->notificationManager->createViewerNotification($video, $viewer);
        }
    }
}
