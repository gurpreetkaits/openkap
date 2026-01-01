<?php

namespace App\Repositories;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new Notification);
    }

    public function findByUserId(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Notification::with('actor:id,name,avatar_url')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findUnreadByUserId(int $userId): Collection
    {
        return Notification::with('actor:id,name,avatar_url')
            ->where('user_id', $userId)
            ->where('read', false)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('read', false)
            ->count();
    }

    public function createNotification(array $data): Notification
    {
        return Notification::create($data);
    }

    public function markAsRead(Notification $notification): bool
    {
        return $notification->update([
            'read' => true,
            'read_at' => now(),
        ]);
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('read', false)
            ->update([
                'read' => true,
                'read_at' => now(),
            ]);
    }

    public function deleteNotification(Notification $notification): bool
    {
        return $notification->delete();
    }

    public function deleteAllForUser(int $userId): int
    {
        return Notification::where('user_id', $userId)->delete();
    }

    public function findByNotifiable(string $notifiableType, int $notifiableId): Collection
    {
        return Notification::where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->get();
    }
}
