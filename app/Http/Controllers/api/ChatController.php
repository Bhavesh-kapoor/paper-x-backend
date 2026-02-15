<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\SendMessageRequest;
use App\Services\ChatService;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class ChatController extends Controller
{
    public function __construct(
        protected ChatService $chatService
    ) {
    }

    public function getChatList()
    {
        try {
            $user = request()->user();
            $list = $this->chatService->getChatList($user->id);
            return Response::success('chat.list', $list);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    public function getMessages(int $sessionId)
    {
        try {
            $user = request()->user();
            $filters = request()->only(['page', 'per_page']);
            $messages = $this->chatService->getMessages($sessionId, $user->id, $filters);

            return Response::success('chat.messages', $messages['messages'], $messages['pagination'] ?? null);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }

    public function sendMessage(int $sessionId, SendMessageRequest $request)
    {
        try {
            $user = $request->user();
            $message = $this->chatService->sendMessage($sessionId, $user->id, $request->validated());

            return Response::success('chat.message_sent', $message, null, HttpResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                method_exists($e, 'getStatusCode') ? $e->getStatusCode() : HttpResponse::HTTP_BAD_REQUEST
            );
        }
    }
}




