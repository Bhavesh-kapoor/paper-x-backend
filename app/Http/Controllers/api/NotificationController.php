<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {
    }

    public function getNotifications()
    {
        try {
            $user = request()->user();
            $unreadOnly = request()->boolean('unread_only', false);
            $filters = request()->only(['cursor', 'limit']);
            $notifications = $this->notificationService->getNotifications($user->id, $unreadOnly, $filters);

            return Response::success('notification.list', $notifications['notifications'], $notifications['pagination'] ?? null);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    public function markAsRead(int $id)
    {
        try {
            $user = request()->user();
            $this->notificationService->markAsRead($id, $user->id);

            return Response::success('notification.marked_read', null);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    public function markAllAsRead()
    {
        try {
            $user = request()->user();
            $count = $this->notificationService->markAllAsRead($user->id);

            return Response::success('notification.all_marked_read', ['count' => $count]);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    public function getUnreadCount()
    {
        try {
            $user = request()->user();
            $unreadCount = $this->notificationService->getUnreadCount($user->id);

            return Response::success('notification.unread_count', ['unread_count' => $unreadCount]);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }
}




