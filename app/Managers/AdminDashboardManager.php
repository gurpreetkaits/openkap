<?php

namespace App\Managers;

use App\Repositories\AdminDashboardRepository;

class AdminDashboardManager
{
    public function __construct(
        private AdminDashboardRepository $repository,
    ) {}

    public function getDashboardStats(): array
    {
        $totalStorage = $this->repository->getTotalStorageBytes();

        return [
            'overview' => [
                'total_users' => $this->repository->getTotalUsers(),
                'active_users_30d' => $this->repository->getActiveUsersLast30Days(),
                'new_users_this_month' => $this->repository->getNewUsersThisMonth(),
                'total_videos' => $this->repository->getTotalVideos(),
                'videos_this_month' => $this->repository->getVideosThisMonth(),
                'total_storage_bytes' => $totalStorage,
                'total_storage_formatted' => $this->formatBytes($totalStorage),
            ],
            'subscriptions' => $this->repository->getSubscriptionBreakdown(),
            'recent_signups' => $this->repository->getRecentSignups(10),
            'growth' => [
                'users' => $this->repository->getUserGrowthByMonth(6),
                'videos' => $this->repository->getVideoGrowthByMonth(6),
            ],
            'processing' => $this->repository->getVideoProcessingStats(),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = floor(log($bytes, 1024));

        return round($bytes / pow(1024, $i), 2).' '.$units[$i];
    }
}
