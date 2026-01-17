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
        // Map class names to morph map keys
        $notifiableType = null;
        if ($notifiable) {
            $class = get_class($notifiable);
            $morphMap = [
                \App\Models\Inquiry::class => 'inquiry',
                \App\Models\MatchingSession::class => 'session',
                \App\Models\Brand::class => 'brand',
                \App\Models\Dealer::class => 'dealer',
                \App\Models\Converter::class => 'converter',
                \App\Models\MachineDealer::class => 'machine_dealer',
            ];
            $notifiableType = $morphMap[$class] ?? null;
        }
        
        return Notification::create([
            'user_id' => $userId,
            'type' => NotificationType::from($type),
            'title' => $title,
            'message' => $message,
            'notifiable_type' => $notifiableType,
            'notifiable_id' => $notifiable?->id,
            'read' => false,
        ]);
    }

    public function getNotifications(int $userId, bool $unreadOnly = false, array $filters = []): array
    {
        $perPage = $filters['per_page'] ?? 15;

        $query = Notification::where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($unreadOnly) {
            $query->where('read', false);
        }

        $notifications = $query->paginate($perPage);

        return [
            'notifications' => $notifications->items(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'last_page' => $notifications->lastPage(),
            ],
        ];
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




