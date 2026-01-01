<?php

namespace App\Http\Controllers;

use App\Managers\NotificationManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationManager $notificationManager
    ) {}

    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 20);
        $result = $this->notificationManager->getUserNotifications(Auth::id(), $perPage);

        return response()->json($result);
    }

    public function unreadCount()
    {
        $count = $this->notificationManager->getUnreadCount(Auth::id());

        return response()->json([
            'unread_count' => $count,
        ]);
    }

    public function markAsRead($id)
    {
        $notification = $this->notificationManager->findNotification($id);

        if (! $notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        if ($notification->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->notificationManager->markAsRead($notification);

        return response()->json([
            'message' => 'Notification marked as read',
        ]);
    }

    public function markAllAsRead()
    {
        $count = $this->notificationManager->markAllAsRead(Auth::id());

        return response()->json([
            'message' => 'All notifications marked as read',
            'count' => $count,
        ]);
    }

    public function destroy($id)
    {
        $notification = $this->notificationManager->findNotification($id);

        if (! $notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        if ($notification->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->notificationManager->deleteNotification($notification);

        return response()->json([
            'message' => 'Notification deleted',
        ]);
    }
}
