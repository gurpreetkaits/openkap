<?php

namespace App\Repositories;

use App\Models\Video;
use App\Models\VideoView;

class VideoViewRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new VideoView);
    }

    public function findRecentView(int $videoId, ?int $userId, ?string $ipAddress): ?VideoView
    {
        return VideoView::where('video_id', $videoId)
            ->where(function ($query) use ($userId, $ipAddress) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('ip_address', $ipAddress);
                }
            })
            ->where('viewed_at', '>', now()->subHour())
            ->first();
    }

    public function createView(array $data): VideoView
    {
        return VideoView::create($data);
    }

    public function updateView(VideoView $view, array $data): bool
    {
        return $view->update($data);
    }

    public function userHasPreviousViews(int $videoId, int $userId, int $excludeViewId): bool
    {
        return VideoView::where('video_id', $videoId)
            ->where('user_id', $userId)
            ->where('id', '!=', $excludeViewId)
            ->exists();
    }

    public function getTotalViews(Video $video): int
    {
        return $video->views()->count();
    }

    public function getUniqueViewers(Video $video): int
    {
        return $video->views()
            ->selectRaw('COUNT(DISTINCT COALESCE(user_id, ip_address)) as count')
            ->value('count');
    }

    public function getAuthenticatedViews(Video $video): int
    {
        return $video->views()->whereNotNull('user_id')->count();
    }

    public function getAnonymousViews(Video $video): int
    {
        return $video->views()->whereNull('user_id')->count();
    }

    public function getAverageWatchDuration(Video $video): ?float
    {
        return $video->views()->avg('watch_duration');
    }

    public function getCompletedViewsCount(Video $video): int
    {
        return $video->views()->where('completed', true)->count();
    }

    public function getRecentViewers(Video $video, int $limit = 10): array
    {
        return $video->views()
            ->whereNotNull('user_id')
            ->with('user:id,name')
            ->latest('viewed_at')
            ->limit($limit)
            ->get()
            ->map(function ($view) {
                return [
                    'user_name' => $view->user->name ?? 'Unknown',
                    'viewed_at' => $view->viewed_at->toISOString(),
                    'watch_duration' => $view->watch_duration,
                    'completed' => $view->completed,
                ];
            })
            ->toArray();
    }
}
