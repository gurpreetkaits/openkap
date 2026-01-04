<?php

namespace App\Managers;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoView;
use App\Repositories\VideoRepository;
use App\Repositories\VideoViewRepository;

class VideoViewManager
{
    public function __construct(
        protected VideoViewRepository $viewRepository,
        protected VideoRepository $videoRepository,
        protected NotificationManager $notificationManager
    ) {}

    public function recordView(
        Video $video,
        ?int $userId,
        ?string $ipAddress,
        ?string $userAgent,
        int $watchDuration = 0,
        bool $completed = false
    ): ?array {
        // Don't record view if owner is viewing their own video
        if ($userId && $userId === $video->user_id) {
            return ['message' => 'Own video view not recorded', 'view' => null];
        }

        // Check if this view already exists (within last hour to prevent spam)
        $existingView = $this->viewRepository->findRecentView($video->id, $userId, $ipAddress);

        if ($existingView) {
            $this->viewRepository->updateView($existingView, [
                'watch_duration' => max($existingView->watch_duration, $watchDuration),
                'completed' => $completed || $existingView->completed,
            ]);

            return ['message' => 'View updated', 'view' => $existingView];
        }

        // Create new view
        $view = $this->viewRepository->createView([
            'video_id' => $video->id,
            'user_id' => $userId,
            'ip_address' => $userId ? null : $ipAddress, // Only store IP for anonymous users
            'user_agent' => $userAgent,
            'watch_duration' => $watchDuration,
            'completed' => $completed,
            'viewed_at' => now(),
        ]);

        // Send notification to video owner if viewer is authenticated and different from owner
        $this->notifyVideoOwnerIfNeeded($video, $userId, $view);

        return ['message' => 'View recorded', 'view' => $view, 'created' => true];
    }

    public function recordSharedView(
        Video $video,
        ?int $userId,
        ?string $ipAddress,
        ?string $userAgent
    ): ?array {
        // Don't record view if owner is viewing their own video
        if ($userId && $userId === $video->user_id) {
            return ['message' => 'Own video view not recorded', 'view' => null];
        }

        // Check if this view already exists (within last hour to prevent spam)
        $existingView = $this->viewRepository->findRecentView($video->id, $userId, $ipAddress);

        if ($existingView) {
            return ['message' => 'View already recorded'];
        }

        // Create new view
        $view = $this->viewRepository->createView([
            'video_id' => $video->id,
            'user_id' => $userId,
            'ip_address' => $userId ? null : $ipAddress,
            'user_agent' => $userAgent,
            'watch_duration' => 0,
            'completed' => false,
            'viewed_at' => now(),
        ]);

        // Send notification to video owner if viewer is authenticated and different from owner
        $this->notifyVideoOwnerIfNeeded($video, $userId, $view);

        return ['message' => 'View recorded', 'view' => $view, 'created' => true];
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

        $viewer = User::find($userId);
        if (! $viewer) {
            return;
        }

        // Check if this is the first time this user views this video
        $hasPreviousViews = $this->viewRepository->userHasPreviousViews($video->id, $userId, $view->id);

        if (! $hasPreviousViews) {
            $this->notificationManager->createViewerNotification($video, $viewer);
        }
    }
}
