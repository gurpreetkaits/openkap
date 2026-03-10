<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Video;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardRepository
{
    public function getTotalUsers(): int
    {
        return User::count();
    }

    public function getActiveUsersLast30Days(): int
    {
        $since = Carbon::now()->subDays(30);

        return User::where(function ($query) use ($since) {
            $query->whereHas('videos', function ($q) use ($since) {
                $q->where('created_at', '>=', $since);
            });
        })->count();
    }

    public function getNewUsersThisMonth(): int
    {
        return User::where('created_at', '>=', Carbon::now()->startOfMonth())->count();
    }

    public function getTotalVideos(): int
    {
        return Video::count();
    }

    public function getVideosThisMonth(): int
    {
        return Video::where('created_at', '>=', Carbon::now()->startOfMonth())->count();
    }

    public function getTotalStorageBytes(): int
    {
        return (int) Video::sum('file_size_bytes');
    }

    public function getSubscriptionBreakdown(): array
    {
        $users = User::all();

        $breakdown = ['free' => 0, 'pro' => 0, 'teams' => 0];

        foreach ($users as $user) {
            $plan = $user->getPlanType();
            if (isset($breakdown[$plan])) {
                $breakdown[$plan]++;
            }
        }

        return $breakdown;
    }

    public function getRecentSignups(int $limit = 10): array
    {
        return User::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar_url ?? $user->avatar,
                    'plan_type' => $user->getPlanType(),
                    'created_at' => $user->created_at->toISOString(),
                ];
            })
            ->toArray();
    }

    public function getVideoProcessingStats(): array
    {
        return [
            'pending_conversion' => Video::where('conversion_status', 'pending')->count(),
            'converting' => Video::where('conversion_status', 'processing')->count(),
            'conversion_failed' => Video::where('conversion_status', 'failed')->count(),
            'pending_hls' => Video::where('hls_status', 'pending')->count(),
            'hls_processing' => Video::where('hls_status', 'processing')->count(),
            'hls_failed' => Video::where('hls_status', 'failed')->count(),
        ];
    }

    public function getUserGrowthByMonth(int $months = 6): array
    {
        $since = Carbon::now()->subMonths($months)->startOfMonth();

        return User::where('created_at', '>=', $since)
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"), DB::raw('COUNT(*) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => ['month' => $row->month, 'count' => $row->count])
            ->toArray();
    }

    public function getVideoGrowthByMonth(int $months = 6): array
    {
        $since = Carbon::now()->subMonths($months)->startOfMonth();

        return Video::where('created_at', '>=', $since)
            ->select(DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"), DB::raw('COUNT(*) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => ['month' => $row->month, 'count' => $row->count])
            ->toArray();
    }
}
