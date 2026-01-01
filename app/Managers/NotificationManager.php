<?php

namespace App\Managers;

use App\Models\Notification;
use App\Models\User;
use App\Models\Video;
use App\Repositories\NotificationRepository;

class NotificationManager
{
    public function __construct(
        protected NotificationRepository $notifications
    ) {}

    public function getUserNotifications(int $userId, int $perPage = 20): array
    {
        $paginated = $this->notifications->findByUserId($userId, $perPage);

        return [
            'notifications' => $paginated->map(function ($notification) {
                return $this->formatNotification($notification);
            }),
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'has_more' => $paginated->hasMorePages(),
            ],
        ];
    }

    public function getUnreadCount(int $userId): int
    {
        return $this->notifications->getUnreadCount($userId);
    }

    public function markAsRead(Notification $notification): bool
    {
        return $this->notifications->markAsRead($notification);
    }

    public function markAllAsRead(int $userId): int
    {
        return $this->notifications->markAllAsRead($userId);
    }

    public function deleteNotification(Notification $notification): bool
    {
        return $this->notifications->deleteNotification($notification);
    }

    public function createViewerNotification(Video $video, User $viewer): ?Notification
    {
        if ($video->user_id === $viewer->id) {
            return null;
        }

        $message = "<span class=\"font-medium text-gray-900\">{$viewer->name}</span> viewed your video \"{$video->title}\"";

        return $this->notifications->createNotification([
            'user_id' => $video->user_id,
            'type' => Notification::TYPE_VIEWER,
            'message' => $message,
            'notifiable_type' => Video::class,
            'notifiable_id' => $video->id,
            'actor_id' => $viewer->id,
        ]);
    }

    public function createCommentNotification(Video $video, User $commenter): ?Notification
    {
        if ($video->user_id === $commenter->id) {
            return null;
        }

        $message = "<span class=\"font-medium text-gray-900\">{$commenter->name}</span> commented on \"{$video->title}\"";

        return $this->notifications->createNotification([
            'user_id' => $video->user_id,
            'type' => Notification::TYPE_COMMENT,
            'message' => $message,
            'notifiable_type' => Video::class,
            'notifiable_id' => $video->id,
            'actor_id' => $commenter->id,
        ]);
    }

    public function createQuotaWarningNotification(User $user, int $percentUsed): Notification
    {
        $message = "You've used <span class=\"font-medium text-gray-900\">{$percentUsed}%</span> of your monthly recording quota";

        return $this->notifications->createNotification([
            'user_id' => $user->id,
            'type' => Notification::TYPE_WARNING,
            'message' => $message,
            'notifiable_type' => null,
            'notifiable_id' => null,
            'actor_id' => null,
        ]);
    }

    public function createVideoProcessedNotification(Video $video): Notification
    {
        $message = "Your video \"{$video->title}\" has been <span class=\"font-medium text-gray-900\">processed</span> and is ready to share";

        return $this->notifications->createNotification([
            'user_id' => $video->user_id,
            'type' => Notification::TYPE_SUCCESS,
            'message' => $message,
            'notifiable_type' => Video::class,
            'notifiable_id' => $video->id,
            'actor_id' => null,
        ]);
    }

    public function createInfoNotification(User $user, string $message): Notification
    {
        return $this->notifications->createNotification([
            'user_id' => $user->id,
            'type' => Notification::TYPE_INFO,
            'message' => $message,
            'notifiable_type' => null,
            'notifiable_id' => null,
            'actor_id' => null,
        ]);
    }

    public function findNotification(int $id): ?Notification
    {
        return $this->notifications->find($id);
    }

    protected function formatNotification(Notification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'message' => $notification->message,
            'read' => $notification->read,
            'created_at' => $notification->created_at->toISOString(),
            'read_at' => $notification->read_at?->toISOString(),
            'actor' => $notification->actor ? [
                'id' => $notification->actor->id,
                'name' => $notification->actor->name,
                'avatar_url' => $notification->actor->avatar_url,
            ] : null,
        ];
    }
}
