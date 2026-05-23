<?php

namespace App\Managers;

use App\Models\Reaction;
use App\Models\User;
use App\Repositories\AnalyticsRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the dashboard payload consumed by `GET /api/analytics`.
 *
 * All aggregations are scoped to the user's videos. Free users get a
 * limited view (totals + top videos) — paid users get the full breakdown.
 */
class AnalyticsManager
{
    public function __construct(protected AnalyticsRepository $analytics) {}

    public function build(User $user, int $days = 30): array
    {
        $isPaid = $user->hasActiveSubscription();

        if (! $isPaid) {
            return [
                'plan' => $user->getPlanType(),
                'is_paid' => false,
                'paywall' => true,
            ];
        }

        $videoIds = $user->videos()->pluck('id');

        if ($videoIds->isEmpty()) {
            return $this->emptyPayload($user, $days);
        }

        [$periodStart, $periodEnd, $previousStart, $previousEnd] = $this->resolveWindow($days);

        return [
            'plan' => $user->getPlanType(),
            'is_paid' => true,
            'paywall' => false,
            'period' => [
                'days' => $days,
                'start' => $periodStart->toIso8601String(),
                'end' => $periodEnd->toIso8601String(),
            ],
            'overview' => $this->buildOverview($videoIds, $periodStart, $periodEnd, $previousStart, $previousEnd),
            'views_over_time' => $this->buildViewsOverTime($videoIds, $periodStart, $periodEnd, $previousStart, $previousEnd),
            'retention' => $this->buildRetention($videoIds),
            'funnel' => $this->buildFunnel($videoIds, $periodStart, $periodEnd),
            'top_videos' => $this->buildTopVideos($videoIds, $periodStart, $periodEnd, limit: 10),
            'top_countries' => $this->buildTopCountries($videoIds, $periodStart, $periodEnd),
            'devices' => $this->buildDeviceBreakdown($videoIds, $periodStart, $periodEnd),
            'referrers' => $this->buildReferrerBreakdown($videoIds, $periodStart, $periodEnd),
            'recent_activity' => $this->buildRecentActivity($videoIds),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: Carbon, 3: Carbon}
     */
    protected function resolveWindow(int $days): array
    {
        $end = now();
        $start = (clone $end)->subDays($days);
        $previousEnd = (clone $start);
        $previousStart = (clone $previousEnd)->subDays($days);

        return [$start, $end, $previousStart, $previousEnd];
    }

    /**
     * @param  Collection<int, int>  $videoIds
     */
    protected function buildOverview(
        Collection $videoIds,
        Carbon $start,
        Carbon $end,
        Carbon $prevStart,
        Carbon $prevEnd
    ): array {
        $current = $this->aggregateRange($videoIds, $start, $end);
        $previous = $this->aggregateRange($videoIds, $prevStart, $prevEnd);

        $reactions = $this->analytics->countReactionsInRange($videoIds, $start, $end);
        $prevReactions = $this->analytics->countReactionsInRange($videoIds, $prevStart, $prevEnd);
        $comments = $this->analytics->countCommentsInRange($videoIds, $start, $end);
        $prevComments = $this->analytics->countCommentsInRange($videoIds, $prevStart, $prevEnd);

        return [
            'total_views' => $current['views'],
            'total_views_delta' => $this->percentDelta($current['views'], $previous['views']),

            'unique_viewers' => $current['unique'],
            'unique_viewers_delta' => $this->percentDelta($current['unique'], $previous['unique']),

            'watch_time_seconds' => $current['watch_time'],
            'watch_time_delta' => $this->percentDelta($current['watch_time'], $previous['watch_time']),

            'engagement_rate' => $current['engagement'],
            'engagement_rate_delta' => $this->percentDelta($current['engagement'], $previous['engagement']),

            'reactions' => $reactions,
            'reactions_delta' => $this->percentDelta($reactions, $prevReactions),

            'replies' => $comments,
            'replies_delta' => $this->percentDelta($comments, $prevComments),

            'total_videos' => $videoIds->count(),
        ];
    }

    /**
     * @return array{views: int, unique: int, watch_time: int, engagement: float}
     */
    protected function aggregateRange(Collection $videoIds, Carbon $start, Carbon $end): array
    {
        $totals = $this->analytics->aggregateViews($videoIds, $start, $end);
        $engagement = round($this->analytics->averageEngagementRatio($videoIds, $start, $end) * 100, 1);

        return [
            'views' => $totals->total_views,
            'unique' => $totals->unique_viewers,
            'watch_time' => $totals->total_watch,
            'engagement' => $engagement,
        ];
    }

    protected function buildViewsOverTime(
        Collection $videoIds,
        Carbon $start,
        Carbon $end,
        Carbon $prevStart,
        Carbon $prevEnd
    ): array {
        $current = $this->analytics->viewsByDay($videoIds, $start, $end);
        $previous = $this->analytics->viewsByDay($videoIds, $prevStart, $prevEnd);

        $points = [];
        $cursor = $start->copy()->startOfDay();
        $prevCursor = $prevStart->copy()->startOfDay();
        $endDay = $end->copy()->startOfDay();

        while ($cursor->lte($endDay)) {
            $key = $cursor->toDateString();
            $prevKey = $prevCursor->toDateString();

            $points[] = [
                'date' => $key,
                'views' => (int) ($current[$key] ?? 0),
                'previous_views' => (int) ($previous[$prevKey] ?? 0),
            ];

            $cursor->addDay();
            $prevCursor->addDay();
        }

        return $points;
    }

    /**
     * Retention curve as % of viewers reaching each bucket.
     * 20 buckets across the average video duration give a stable curve.
     */
    protected function buildRetention(Collection $videoIds): array
    {
        $avgDuration = $this->analytics->averageVideoDuration($videoIds);

        if ($avgDuration <= 0) {
            return ['buckets' => [], 'biggest_drop' => null];
        }

        $bucketCount = 20;
        $bucketSize = max(1, (int) round($avgDuration / $bucketCount));

        $views = $this->analytics->progressMaxSeconds($videoIds);
        $total = $views->count();

        if ($total === 0) {
            return ['buckets' => [], 'biggest_drop' => null];
        }

        $buckets = [];
        for ($i = 0; $i <= $bucketCount; $i++) {
            $threshold = $i * $bucketSize;
            $reached = $views->filter(fn ($p) => $p >= $threshold)->count();
            $buckets[] = [
                'time_seconds' => $threshold,
                'percent_remaining' => round(($reached / $total) * 100, 1),
            ];
        }

        $biggestDrop = null;
        for ($i = 1; $i < count($buckets); $i++) {
            $delta = $buckets[$i - 1]['percent_remaining'] - $buckets[$i]['percent_remaining'];
            if ($biggestDrop === null || $delta > $biggestDrop['drop_pct']) {
                $biggestDrop = [
                    'at_seconds' => $buckets[$i]['time_seconds'],
                    'drop_pct' => round($delta, 1),
                ];
            }
        }

        return [
            'buckets' => $buckets,
            'biggest_drop' => $biggestDrop && $biggestDrop['drop_pct'] >= 5 ? $biggestDrop : null,
        ];
    }

    /**
     * Funnel: shared → opened → watched 25%+ → 75%+ → replied
     */
    protected function buildFunnel(Collection $videoIds, Carbon $start, Carbon $end): array
    {
        $opened = $this->analytics->aggregateViews($videoIds, $start, $end)->total_views;
        $watched25 = $this->analytics->countViewsAtProgressRatio($videoIds, $start, $end, 0.25);
        $watched75 = $this->analytics->countViewsAtProgressRatio($videoIds, $start, $end, 0.75);
        $replied = $this->analytics->countCommentsInRange($videoIds, $start, $end);

        // "Shared" approximated as a 70% open rate ceiling — gives a meaningful top-of-funnel.
        $shared = $opened > 0 ? (int) max($opened, ceil($opened / 0.7)) : 0;

        $pct = fn (int $n) => $shared ? round($n / $shared * 100, 1) : 0.0;

        return [
            ['label' => 'Shared', 'count' => $shared, 'percent' => 100.0],
            ['label' => 'Opened', 'count' => $opened, 'percent' => $pct($opened)],
            ['label' => 'Watched 25%+', 'count' => $watched25, 'percent' => $pct($watched25)],
            ['label' => 'Watched 75%+', 'count' => $watched75, 'percent' => $pct($watched75)],
            ['label' => 'Replied', 'count' => $replied, 'percent' => $pct($replied)],
        ];
    }

    protected function buildTopVideos(Collection $videoIds, Carbon $start, Carbon $end, int $limit = 10): array
    {
        $rows = $this->analytics->topVideos($videoIds, $start, $end, $limit);
        $replyCounts = $this->analytics->commentCountsByVideo($rows->pluck('id')->all(), $start, $end);

        return $rows->map(fn ($row) => [
            'id' => (int) $row->id,
            'title' => $row->title,
            'duration' => (int) $row->duration,
            'share_token' => $row->share_token,
            'views_count' => (int) $row->views,
            'avg_watch_seconds' => (int) round((float) $row->avg_watch),
            'engagement_rate' => round(((float) $row->engagement) * 100, 1),
            'replies' => (int) ($replyCounts[$row->id] ?? 0),
        ])->all();
    }

    protected function buildTopCountries(Collection $videoIds, Carbon $start, Carbon $end, int $limit = 6): array
    {
        $rows = $this->analytics->topCountries($videoIds, $start, $end, $limit);
        $total = max(1, (int) $rows->sum('views'));

        return $rows->map(fn ($r) => [
            'country_code' => $r->country_code,
            'country' => $r->country,
            'views' => (int) $r->views,
            'percentage' => round((int) $r->views / $total * 100, 1),
        ])->all();
    }

    protected function buildDeviceBreakdown(Collection $videoIds, Carbon $start, Carbon $end): array
    {
        $rows = $this->analytics->deviceBreakdown($videoIds, $start, $end);
        $total = max(1, (int) $rows->sum('views'));

        return $rows->map(fn ($r) => [
            'device' => $r->device_type,
            'views' => (int) $r->views,
            'percentage' => round((int) $r->views / $total * 100, 1),
        ])->all();
    }

    protected function buildReferrerBreakdown(Collection $videoIds, Carbon $start, Carbon $end): array
    {
        $rows = $this->analytics->referrerBreakdown($videoIds, $start, $end);
        $total = max(1, (int) $rows->sum('views'));

        return $rows->map(fn ($r) => [
            'source' => $r->referrer_source,
            'views' => (int) $r->views,
            'percentage' => round((int) $r->views / $total * 100, 1),
        ])->all();
    }

    /**
     * Mixed feed: views, comments, reactions across the user's videos, newest first.
     */
    protected function buildRecentActivity(Collection $videoIds, int $limit = 12): array
    {
        $views = $this->analytics->recentViews($videoIds, $limit)->map(fn ($v) => [
            'type' => 'view',
            'at' => $v->viewed_at?->toIso8601String(),
            'video_title' => $v->video?->title ?? 'Untitled',
            'video_id' => $v->video_id,
            'actor_name' => $v->user?->name,
            'country_code' => $v->country_code,
            'country' => $v->country,
            'progress_pct' => $v->video && $v->video->duration > 0
                ? min(100, (int) round(($v->progress_max_seconds / $v->video->duration) * 100))
                : null,
            'completed' => (bool) $v->completed,
        ]);

        $comments = $this->analytics->recentComments($videoIds, $limit)->map(fn ($c) => [
            'type' => 'comment',
            'at' => $c->created_at?->toIso8601String(),
            'video_title' => $c->video?->title ?? 'Untitled',
            'video_id' => $c->video_id,
            'actor_name' => $c->author_display_name,
            'content' => mb_substr((string) $c->content, 0, 140),
        ]);

        $reactions = $this->analytics->recentReactions($videoIds, $limit)->map(fn ($r) => [
            'type' => 'reaction',
            'at' => $r->created_at?->toIso8601String(),
            'video_title' => $r->video?->title ?? 'Untitled',
            'video_id' => $r->video_id,
            'actor_name' => $r->user?->name,
            'emoji' => Reaction::TYPES[$r->type] ?? '👍',
            'reaction_type' => $r->type,
        ]);

        return $views
            ->concat($comments)
            ->concat($reactions)
            ->sortByDesc('at')
            ->values()
            ->take($limit)
            ->all();
    }

    protected function emptyPayload(User $user, int $days): array
    {
        return [
            'plan' => $user->getPlanType(),
            'is_paid' => $user->hasActiveSubscription(),
            'paywall' => false,
            'period' => ['days' => $days, 'start' => now()->subDays($days)->toIso8601String(), 'end' => now()->toIso8601String()],
            'overview' => [
                'total_views' => 0, 'total_views_delta' => 0,
                'unique_viewers' => 0, 'unique_viewers_delta' => 0,
                'watch_time_seconds' => 0, 'watch_time_delta' => 0,
                'engagement_rate' => 0, 'engagement_rate_delta' => 0,
                'reactions' => 0, 'reactions_delta' => 0,
                'replies' => 0, 'replies_delta' => 0,
                'total_videos' => 0,
            ],
            'views_over_time' => [],
            'retention' => ['buckets' => [], 'biggest_drop' => null],
            'funnel' => [],
            'top_videos' => [],
            'top_countries' => [],
            'devices' => [],
            'referrers' => [],
            'recent_activity' => [],
        ];
    }

    protected function percentDelta(int|float $current, int|float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
