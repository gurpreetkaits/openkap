<?php

namespace App\Managers;

use App\Data\AdminUserVideoData;
use App\Data\AdminUserVideosData;
use App\Models\Video;
use App\Repositories\AdminDashboardRepository;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AdminDashboardManager
{
    public function __construct(
        private AdminDashboardRepository $repository,
        private UserRepository $users,
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

    /**
     * Every recording one user has made, newest first, annotated with health so an
     * admin can tell at a glance whether that user's recordings actually work.
     */
    public function getUserVideos(int $userId, int $limit = 100): AdminUserVideosData
    {
        $user = $this->users->findById($userId);

        if (! $user) {
            throw new ModelNotFoundException('User not found');
        }

        $videos = $this->repository->getVideosByUserId($userId, $limit)
            ->map(fn (Video $video) => $this->toVideoData($video))
            ->all();

        $problemCount = count(array_filter(
            $videos,
            fn (AdminUserVideoData $video) => in_array($video->health, ['failed', 'empty'], true)
        ));

        return new AdminUserVideosData(
            user_id: $user->id,
            user_name: $user->name,
            user_email: $user->email,
            total_videos: $this->repository->countVideosByUserId($userId),
            problem_videos: $problemCount,
            videos: $videos,
        );
    }

    private function toVideoData(Video $video): AdminUserVideoData
    {
        $usesBunny = $this->usesBunnyStorage($video);

        $error = $video->conversion_error
            ?: ($video->hls_error ?: ($usesBunny ? $video->bunny_error : null));

        return new AdminUserVideoData(
            id: $video->id,
            title: $video->title,
            duration: $video->duration,
            // Bunny-hosted recordings carry their size on the Bunny column instead.
            file_size_bytes: $video->file_size_bytes ?: $video->bunny_file_size,
            bunny_resolution: $video->bunny_resolution,
            has_audio: (bool) $video->has_audio,
            has_camera: (bool) $video->has_camera,
            storage_type: $video->storage_type,
            conversion_status: $video->conversion_status,
            hls_status: $video->hls_status,
            // Reported only when meaningful — see usesBunnyStorage().
            bunny_status: $usesBunny ? $video->bunny_status : null,
            error: $error ?: null,
            health: $this->resolveVideoHealth($video, $error ?: null),
            created_at: $video->created_at->toISOString(),
        );
    }

    /**
     * `videos.bunny_status` defaults to 'pending' on every row — including recordings
     * that are stored locally and never touch Bunny at all. Reading it unconditionally
     * would mark every healthy local recording as perpetually "processing".
     */
    private function usesBunnyStorage(Video $video): bool
    {
        return $video->storage_type === 'bunny' || ! empty($video->bunny_video_id);
    }

    /**
     * Health follows whichever pipeline actually produces the playable file:
     * Bunny for Bunny-hosted recordings, local conversion otherwise.
     *
     * hls_status deliberately contributes only a FAILURE signal, never an in-flight
     * one. Bunny handles its own delivery, so the local HLS step never runs for
     * Bunny-hosted recordings and their hls_status stays 'pending' forever; locally
     * converted recordings without HLS still play from the MP4. Treating that
     * 'pending' as "processing" mislabels the bulk of the library as in-flight.
     *
     * Order matters: a recording genuinely still converting has no size or duration
     * yet, so 'processing' must win over 'empty' to avoid false alarms.
     */
    private function resolveVideoHealth(Video $video, ?string $error): string
    {
        $usesBunny = $this->usesBunnyStorage($video);

        // The local pipeline reports failure as 'failed'; Bunny reports it as 'error'.
        $hasFailed = $error !== null
            || $video->conversion_status === 'failed'
            || $video->hls_status === 'failed'
            || ($usesBunny && $video->bunny_status === 'error');

        if ($hasFailed) {
            return 'failed';
        }

        $inFlight = $usesBunny
            ? in_array($video->bunny_status, ['pending', 'uploading', 'processing'], true)
            : in_array($video->conversion_status, ['pending', 'processing'], true);

        if ($inFlight) {
            return 'processing';
        }

        if (empty($video->duration) || empty($video->file_size_bytes ?: $video->bunny_file_size)) {
            return 'empty';
        }

        return 'ok';
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
