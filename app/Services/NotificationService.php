<?php

namespace App\Services;

use App\Enums\NotificationType;
use App\Models\Notification;

class NotificationService
{
    public function create(
        int $userId,
        string $type,
        string $title,
        string $message,
        $notifiable = null
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'type' => NotificationType::from($type),
            'title' => $title,
            'message' => $message,
            'notifiable_type' => $notifiable ? get_class($notifiable) : null,
            'notifiable_id' => $notifiable?->id,
            'read' => false,
        ]);
    }

    public function getNotifications(int $userId, bool $unreadOnly = false): \Illuminate\Database\Eloquent\Collection
    {
        $query = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($unreadOnly) {
            $query->where('read', false);
        }

        return $query->get();
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $notification->update([
            'read' => true,
            'read_at' => now(),
        ]);

        return true;
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
}

