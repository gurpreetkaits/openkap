<?php

namespace App\Repositories;

use App\Models\Comment;
use App\Models\Reaction;
use App\Models\Video;
use App\Models\VideoView;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * All read-side queries that feed the analytics dashboard.
 *
 * Returns scalar/array/Collection results; aggregation, ratios and
 * shaping into the API payload happen in AnalyticsManager.
 */
class AnalyticsRepository
{
    /**
     * @return object{total_views: int, unique_viewers: int, total_watch: int}
     */
    public function aggregateViews(Collection $videoIds, Carbon $start, Carbon $end): object
    {
        $row = VideoView::whereIn('video_id', $videoIds)
            ->whereBetween('viewed_at', [$start, $end])
            ->selectRaw('
                COUNT(*) as total_views,
                COUNT(DISTINCT COALESCE(CAST(user_id AS CHAR), ip_address, session_id)) as unique_viewers,
                COALESCE(SUM(watch_duration), 0) as total_watch
            ')
            ->first();

        return (object) [
            'total_views' => (int) ($row->total_views ?? 0),
            'unique_viewers' => (int) ($row->unique_viewers ?? 0),
            'total_watch' => (int) ($row->total_watch ?? 0),
        ];
    }

    /**
     * Avg engagement ratio (0-1) across all views in range, weighted by video duration.
     */
    public function averageEngagementRatio(Collection $videoIds, Carbon $start, Carbon $end): float
    {
        $value = DB::table('video_views as v')
            ->join('videos as vid', 'vid.id', '=', 'v.video_id')
            ->whereIn('v.video_id', $videoIds)
            ->whereBetween('v.viewed_at', [$start, $end])
            ->where('vid.duration', '>', 0)
            ->selectRaw('AVG(LEAST(v.progress_max_seconds / vid.duration, 1)) as ratio')
            ->value('ratio');

        return (float) ($value ?? 0);
    }

    public function countReactionsInRange(Collection $videoIds, Carbon $start, Carbon $end): int
    {
        return Reaction::whereIn('video_id', $videoIds)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    public function countCommentsInRange(Collection $videoIds, Carbon $start, Carbon $end): int
    {
        return Comment::whereIn('video_id', $videoIds)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    /**
     * @return array<string, int> date string => count
     */
    public function viewsByDay(Collection $videoIds, Carbon $start, Carbon $end): array
    {
        return VideoView::whereIn('video_id', $videoIds)
            ->whereBetween('viewed_at', [$start, $end])
            ->selectRaw('DATE(viewed_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->pluck('count', 'day')
            ->toArray();
    }

    /**
     * Average duration of the videos a user owns (used to scale the retention buckets).
     */
    public function averageVideoDuration(Collection $videoIds): int
    {
        return (int) Video::whereIn('id', $videoIds)
            ->where('duration', '>', 0)
            ->avg('duration');
    }

    /**
     * @return Collection<int, int> raw progress_max_seconds for each non-zero view
     */
    public function progressMaxSeconds(Collection $videoIds): Collection
    {
        return VideoView::whereIn('video_id', $videoIds)
            ->where('progress_max_seconds', '>', 0)
            ->pluck('progress_max_seconds');
    }

    public function countViewsAtProgressRatio(
        Collection $videoIds,
        Carbon $start,
        Carbon $end,
        float $minRatio
    ): int {
        return DB::table('video_views as v')
            ->join('videos as vid', 'vid.id', '=', 'v.video_id')
            ->whereIn('v.video_id', $videoIds)
            ->whereBetween('v.viewed_at', [$start, $end])
            ->where('vid.duration', '>', 0)
            ->whereRaw('v.progress_max_seconds / vid.duration >= ?', [$minRatio])
            ->count();
    }

    /**
     * @return EloquentCollection<int, object>
     */
    public function topVideos(Collection $videoIds, Carbon $start, Carbon $end, int $limit): EloquentCollection
    {
        return DB::table('videos as vid')
            ->leftJoin('video_views as v', function ($join) use ($start, $end) {
                $join->on('v.video_id', '=', 'vid.id')
                    ->whereBetween('v.viewed_at', [$start, $end]);
            })
            ->whereIn('vid.id', $videoIds)
            ->groupBy('vid.id', 'vid.title', 'vid.duration', 'vid.share_token')
            ->selectRaw('
                vid.id,
                vid.title,
                vid.duration,
                vid.share_token,
                COUNT(v.id) as views,
                COALESCE(AVG(v.watch_duration), 0) as avg_watch,
                COALESCE(AVG(CASE WHEN vid.duration > 0 THEN LEAST(v.progress_max_seconds / vid.duration, 1) ELSE NULL END), 0) as engagement
            ')
            ->orderByDesc('views')
            ->limit($limit)
            ->get()
            ->pipe(fn ($c) => new EloquentCollection($c->all()));
    }

    /**
     * @param  array<int, int>  $videoIdList
     * @return array<int, int> video_id => comment count
     */
    public function commentCountsByVideo(array $videoIdList, Carbon $start, Carbon $end): array
    {
        return Comment::whereIn('video_id', $videoIdList)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('video_id, COUNT(*) as count')
            ->groupBy('video_id')
            ->pluck('count', 'video_id')
            ->toArray();
    }

    /**
     * @return EloquentCollection<int, VideoView>
     */
    public function topCountries(Collection $videoIds, Carbon $start, Carbon $end, int $limit): EloquentCollection
    {
        return VideoView::whereIn('video_id', $videoIds)
            ->whereBetween('viewed_at', [$start, $end])
            ->whereNotNull('country_code')
            ->selectRaw('country_code, country, COUNT(*) as views')
            ->groupBy('country_code', 'country')
            ->orderByDesc('views')
            ->limit($limit)
            ->get();
    }

    /**
     * @return EloquentCollection<int, VideoView>
     */
    public function deviceBreakdown(Collection $videoIds, Carbon $start, Carbon $end): EloquentCollection
    {
        return VideoView::whereIn('video_id', $videoIds)
            ->whereBetween('viewed_at', [$start, $end])
            ->whereNotNull('device_type')
            ->selectRaw('device_type, COUNT(*) as views')
            ->groupBy('device_type')
            ->get();
    }

    /**
     * @return EloquentCollection<int, VideoView>
     */
    public function referrerBreakdown(Collection $videoIds, Carbon $start, Carbon $end): EloquentCollection
    {
        return VideoView::whereIn('video_id', $videoIds)
            ->whereBetween('viewed_at', [$start, $end])
            ->whereNotNull('referrer_source')
            ->selectRaw('referrer_source, COUNT(*) as views')
            ->groupBy('referrer_source')
            ->orderByDesc('views')
            ->get();
    }

    /**
     * @return EloquentCollection<int, VideoView>
     */
    public function recentViews(Collection $videoIds, int $limit): EloquentCollection
    {
        return VideoView::with(['video:id,title,duration', 'user:id,name'])
            ->whereIn('video_id', $videoIds)
            ->latest('viewed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return EloquentCollection<int, Comment>
     */
    public function recentComments(Collection $videoIds, int $limit): EloquentCollection
    {
        return Comment::with(['video:id,title', 'user:id,name'])
            ->whereIn('video_id', $videoIds)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * @return EloquentCollection<int, Reaction>
     */
    public function recentReactions(Collection $videoIds, int $limit): EloquentCollection
    {
        return Reaction::with(['video:id,title', 'user:id,name'])
            ->whereIn('video_id', $videoIds)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }
}
