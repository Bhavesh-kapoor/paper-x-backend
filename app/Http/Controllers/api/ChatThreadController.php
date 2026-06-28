<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\ChatThread;
use App\Models\Inquiry;
use App\Policies\ChatThreadPolicy;
use App\Services\ChatService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Response;

class ChatThreadController extends Controller
{
    public function __construct(
        protected ChatService $chatService,
        protected ChatThreadPolicy $chatThreadPolicy
    ) {
    }

    public function listAll()
    {
        try {
            $user = request()->user();
            $threads = $this->chatService->getAllThreadsForUser($user);
            return Response::success('chat_threads.all', $threads);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                $this->resolveStatusCode($e, HttpResponse::HTTP_BAD_REQUEST)
            );
        }
    }

    public function listByInquiry(int $inquiryId)
    {
        try {
            $user = request()->user();
            $inquiry = Inquiry::query()->with('poster')->findOrFail($inquiryId);
            $posterUserId = $inquiry->poster?->user_id ?? null;
            if ((int) $posterUserId !== (int) $user->id || !$this->chatThreadPolicy->listThreads($user, $inquiry)) {
                throw new AuthorizationException('This action is unauthorized.');
            }

            $threads = $this->chatService->getInquiryThreadsForPoster($inquiryId, $user);

            return Response::success('chat_threads.list', $threads);
        } catch (AuthorizationException $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                $this->resolveStatusCode($e, HttpResponse::HTTP_BAD_REQUEST)
            );
        }
    }

    public function open(int $inquiryId)
    {
        try {
            $user = request()->user();
            $thread = $this->chatService->openOrCreateThread($inquiryId, $user);
            if (!$this->isThreadParticipant($thread, $user) || !$this->chatThreadPolicy->view($user, $thread)) {
                throw new AuthorizationException('This action is unauthorized.');
            }

            return Response::success('chat_threads.open', [
                'thread_id' => $thread->id,
                'inquiry_id' => $thread->inquiry_id,
            ]);
        } catch (AuthorizationException $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                $this->resolveStatusCode($e, HttpResponse::HTTP_BAD_REQUEST)
            );
        }
    }

    public function getMessages(int $threadId, Request $request)
    {
        try {
            $user = $request->user();
            $thread = ChatThread::query()->findOrFail($threadId);
            if (!$this->isThreadParticipant($thread, $user) || !$this->chatThreadPolicy->view($user, $thread)) {
                throw new AuthorizationException('This action is unauthorized.');
            }

            $validated = $request->validate([
                'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
                'cursor' => ['nullable', 'integer', 'min:1'],
            ]);

            $limit = (int) ($validated['limit'] ?? 20);
            $cursor = isset($validated['cursor']) ? (int) $validated['cursor'] : null;

            $result = $this->chatService->getThreadMessages($thread, $limit, $cursor);

            return Response::success('chat_threads.messages', $result['data'], $result['meta']);
        } catch (AuthorizationException $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                $this->resolveStatusCode($e, HttpResponse::HTTP_BAD_REQUEST)
            );
        }
    }

    public function sendMessage(int $threadId, Request $request)
    {
        try {
            $user = $request->user();
            $thread = ChatThread::query()->findOrFail($threadId);
            if (!$this->isThreadParticipant($thread, $user) || !$this->chatThreadPolicy->send($user, $thread)) {
                throw new AuthorizationException('This action is unauthorized.');
            }

            $validated = $request->validate([
                'body' => ['nullable', 'string', 'max:5000'],
                'attachment' => ['nullable', 'file', 'max:10240'],
            ]);

            $hasBody = isset($validated['body']) && trim((string) $validated['body']) !== '';
            $hasAttachment = $request->hasFile('attachment');
            if (!$hasBody && !$hasAttachment) {
                return Response::error('Message body or attachment is required.', null, HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($hasAttachment) {
                $validated['attachment'] = $request->file('attachment');
            }

            $message = $this->chatService->sendThreadMessage($thread, $user, $validated);

            return Response::success('chat_threads.message_sent', $message, null, HttpResponse::HTTP_CREATED);
        } catch (AuthorizationException $e) {
            return Response::error($e->getMessage(), null, HttpResponse::HTTP_FORBIDDEN);
        } catch (\Exception $e) {
            return Response::error(
                $e->getMessage(),
                null,
                $this->resolveStatusCode($e, HttpResponse::HTTP_BAD_REQUEST)
            );
        }
    }

    private function resolveStatusCode(\Exception $e, int $fallback): int
    {
        $code = (int) $e->getCode();

        if ($code >= 400 && $code < 600) {
            return $code;
        }

        if (method_exists($e, 'getStatusCode')) {
            $status = (int) $e->getStatusCode();
            if ($status >= 400 && $status < 600) {
                return $status;
            }
        }

        return $fallback;
    }

    private function isThreadParticipant(ChatThread $thread, $user): bool
    {
        return (int) $thread->poster_user_id === (int) $user->id
            || (int) $thread->responder_user_id === (int) $user->id;
    }
}
