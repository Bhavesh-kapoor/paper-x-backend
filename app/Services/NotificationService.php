<?php

namespace App\Services;

use App\Enums\NavigationType;
use App\Enums\NotificationType;
use App\Models\Notification;
use App\Support\Notifications\NotificationMetaSchema;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

class NotificationService
{
    public function create(
        int $userId,
        string|NotificationType $type,
        string $title,
        string $body,
        string|NavigationType $navigationType,
        string|int $navigationId,
        array $meta = [],
        ?string $dedupeKey = null
    ): ?Notification {
        $notificationType = $type instanceof NotificationType ? $type : NotificationType::from($type);
        $resolvedNavigationType = $navigationType instanceof NavigationType
            ? $navigationType
            : NavigationType::from($navigationType);

        NotificationMetaSchema::validate($notificationType, $meta);

        try {
            return Notification::create([
                'user_id' => $userId,
                'type' => $notificationType,
                'title' => $title,
                'body' => $body,
                'meta' => $meta,
                'navigation_type' => $resolvedNavigationType,
                'navigation_id' => (string) $navigationId,
                'dedupe_key' => $dedupeKey,
                'read_at' => null,
            ]);
        } catch (QueryException $e) {
            // Duplicate dedupe keys are expected under retries/races.
            if ($dedupeKey !== null && $this->isDuplicateKeyException($e)) {
                return Notification::where('user_id', $userId)
                    ->where('dedupe_key', $dedupeKey)
                    ->first();
            }

            throw $e;
        }
    }

    public function getNotifications(int $userId, bool $unreadOnly = false, array $filters = []): array
    {
        $limit = (int) ($filters['limit'] ?? 20);
        $limit = min(max($limit, 1), 100);
        $cursor = $filters['cursor'] ?? null;

        $query = Notification::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        if (is_string($cursor) && $cursor !== '') {
            $decodedCursor = $this->decodeCursor($cursor);
            if ($decodedCursor !== null) {
                [$cursorCreatedAt, $cursorId] = $decodedCursor;
                $query->where(function ($nestedQuery) use ($cursorCreatedAt, $cursorId) {
                    $nestedQuery->where('created_at', '<', $cursorCreatedAt)
                        ->orWhere(function ($tieBreakerQuery) use ($cursorCreatedAt, $cursorId) {
                            $tieBreakerQuery->where('created_at', '=', $cursorCreatedAt)
                                ->where('id', '<', $cursorId);
                        });
                });
            }
        }

        $rows = $query->limit($limit + 1)->get();
        $hasMore = $rows->count() > $limit;
        $notifications = $rows->take($limit)->values();

        $nextCursor = null;
        if ($hasMore && $notifications->isNotEmpty()) {
            $lastNotification = $notifications->last();
            $nextCursor = $this->encodeCursor($lastNotification->created_at, (int) $lastNotification->id);
        }

        return [
            'notifications' => $notifications->toArray(),
            'pagination' => [
                'limit' => $limit,
                'has_more' => $hasMore,
                'next_cursor' => $nextCursor,
            ],
        ];
    }

    public function getUnreadCount(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->firstOrFail();

        if ($notification->read_at === null) {
            $notification->update([
                'read_at' => now(),
            ]);
        }

        return true;
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update([
                'read_at' => now(),
            ]);
    }

    private function isDuplicateKeyException(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = $exception->errorInfo[1] ?? null;

        return $sqlState === '23000' || $driverCode === 1062;
    }

    private function encodeCursor(Carbon|string $createdAt, int $id): string
    {
        $normalizedCreatedAt = $createdAt instanceof Carbon
            ? $createdAt->toIso8601String()
            : Carbon::parse($createdAt)->toIso8601String();

        return base64_encode($normalizedCreatedAt.'|'.$id);
    }

    /**
     * @return array{0: string, 1: int}|null
     */
    private function decodeCursor(string $cursor): ?array
    {
        $decoded = base64_decode($cursor, true);
        if ($decoded === false || !str_contains($decoded, '|')) {
            return null;
        }

        [$createdAt, $id] = explode('|', $decoded, 2);
        if (!is_numeric($id)) {
            return null;
        }

        try {
            $normalized = Carbon::parse($createdAt)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }

        return [$normalized, (int) $id];
    }
}




