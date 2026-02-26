<?php

namespace App\Services;

use App\Domain\MatchEngine\Models\InquiryResponse;
use App\Domain\MatchEngine\Models\MatchHistory;
use App\Enums\SessionStatus;
use App\Models\Brand;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Converter;
use App\Models\Dealer;
use App\Models\Inquiry;
use App\Models\MatchingSession;
use App\Models\Message;
use App\Models\MachineDealer;
use App\Models\SessionParticipant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ChatService
{
    /**
     * Deterministically open or create a chat thread for one inquiry+responder pair.
     *
     * Thread identity is always: (inquiry_id, responder_user_id).
     *
     * @throws \Exception
     */
    public function openOrCreateThread(int $inquiryId, User $responderUser): ChatThread
    {
        if (!$responderUser->exists || !$responderUser->id) {
            throw new \Exception('Responder not found.', 404);
        }

        $inquiry = Inquiry::query()
            ->with(['session', 'poster'])
            ->find($inquiryId);

        if (!$inquiry) {
            throw new \Exception('Inquiry not found.', 404);
        }

        $response = InquiryResponse::query()
            ->where('inquiry_id', $inquiry->id)
            ->where('responder_id', $responderUser->id)
            ->first();

        if (!$response) {
            throw new \Exception('Responder has not responded to this inquiry.', 403);
        }

        $isMatched = MatchHistory::query()
            ->where('inquiry_id', $inquiry->id)
            ->where('matched_user_id', $responderUser->id)
            ->exists();

        if (!$isMatched) {
            throw new \Exception('Responder is not matched for this inquiry.', 403);
        }

        $existing = ChatThread::query()
            ->where('inquiry_id', $inquiry->id)
            ->where('responder_user_id', $responderUser->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $posterUserId = $this->resolvePosterUserId($inquiry);
        $responderRole = (string) ($response->responder_role ?: $responderUser->primary_role ?: 'dealer');
        $sessionId = $inquiry->session?->id;

        // Legacy schema still enforces UNIQUE(session_id), so attach session_id
        // only when free; canonical identity remains inquiry+responder.
        if ($sessionId !== null) {
            $sessionLinkExists = ChatThread::query()
                ->where('session_id', $sessionId)
                ->exists();

            if ($sessionLinkExists) {
                $sessionId = null;
            }
        }

        if ($sessionId === null && !$this->chatThreadSessionIdNullable()) {
            throw new \Exception('Matching session is required before opening thread.', 422);
        }

        try {
            return DB::transaction(function () use ($inquiry, $posterUserId, $responderUser, $responderRole, $sessionId) {
                $insideTxnExisting = ChatThread::query()
                    ->where('inquiry_id', $inquiry->id)
                    ->where('responder_user_id', $responderUser->id)
                    ->first();

                if ($insideTxnExisting) {
                    return $insideTxnExisting;
                }

                return ChatThread::query()->create([
                    'inquiry_id' => $inquiry->id,
                    'poster_user_id' => $posterUserId,
                    'responder_user_id' => $responderUser->id,
                    'responder_role' => $responderRole,
                    'session_id' => $sessionId,
                    'thread_type' => 'one_to_one',
                    'is_active' => true,
                ]);
            });
        } catch (QueryException $e) {
            if (!$this->isDuplicateKeyException($e)) {
                throw $e;
            }

            $thread = ChatThread::query()
                ->where('inquiry_id', $inquiry->id)
                ->where('responder_user_id', $responderUser->id)
                ->first();

            if ($thread) {
                return $thread;
            }

            throw $e;
        }
    }

    /**
     * List structured threads for one inquiry (poster scope only).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getInquiryThreadsForPoster(int $inquiryId, User $posterUser): array
    {
        $threads = ChatThread::query()
            ->where('inquiry_id', $inquiryId)
            ->where('poster_user_id', $posterUser->id)
            ->with([
                'responder:id,name,company_name,primary_role',
                'lastStructuredMessage:id,thread_id,body,attachment,created_at',
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        return $threads->map(function (ChatThread $thread) {
            $last = $thread->lastStructuredMessage;
            $preview = '';

            if ($last) {
                $preview = $last->body ?: ($last->attachment ? '[Attachment]' : '');
            }

            return [
                'id' => $thread->id,
                'thread_id' => $thread->id,
                'inquiry_id' => $thread->inquiry_id,
                'responder_user_id' => $thread->responder_user_id,
                'responder_role' => $thread->responder_role,
                'responder_user' => [
                    'id' => $thread->responder?->id,
                    'name' => $thread->responder?->name,
                    'company_name' => $thread->responder?->company_name,
                    'role' => $thread->responder_role ?: $thread->responder?->primary_role,
                ],
                'last_message_preview' => $preview,
                'last_message_at' => $thread->last_message_at?->toIso8601String(),
                'unread_count' => 0,
            ];
        })->values()->all();
    }

    /**
     * Cursor-paginated structured messages for a thread.
     *
     * @return array{data: array<int, array<string, mixed>>, meta: array<string, mixed>}
     */
    public function getThreadMessages(ChatThread $thread, int $limit = 20, ?int $cursor = null): array
    {
        $limit = max(1, min($limit, 100));

        $query = Message::query()
            ->where('thread_id', $thread->id)
            ->when($cursor, fn ($q) => $q->where('id', '<', $cursor))
            ->orderByDesc('id')
            ->limit($limit + 1);

        $rows = $query->get();
        $hasMore = $rows->count() > $limit;
        $slice = $hasMore ? $rows->take($limit) : $rows;
        $oldestInChunk = $slice->last();

        $messages = $slice
            ->reverse()
            ->values()
            ->map(function (Message $message) {
                return [
                    'id' => $message->id,
                    'thread_id' => $message->thread_id,
                    'sender_user_id' => $message->sender_user_id,
                    'sender_role' => $message->sender_role,
                    'body' => $message->body,
                    'attachment' => $message->attachment,
                    'status' => $message->status,
                    'created_at' => $message->created_at?->toIso8601String(),
                ];
            })
            ->all();

        return [
            'data' => $messages,
            'meta' => [
                'limit' => $limit,
                'next_cursor' => $hasMore && $oldestInChunk ? $oldestInChunk->id : null,
                'has_more' => $hasMore,
            ],
        ];
    }

    public function sendThreadMessage(ChatThread $thread, User $actor, array $payload): Message
    {
        $body = isset($payload['body']) ? trim((string) $payload['body']) : null;
        /** @var UploadedFile|null $attachment */
        $attachment = $payload['attachment'] ?? null;

        if (($body === null || $body === '') && !$attachment) {
            throw new \InvalidArgumentException('Message body or attachment is required.');
        }

        return DB::transaction(function () use ($thread, $actor, $body, $attachment) {
            $attachmentPath = null;
            if ($attachment instanceof UploadedFile) {
                $filename = time() . '_' . $attachment->getClientOriginalName();
                $attachment->move(public_path('chat_attachments'), $filename);
                $attachmentPath = 'chat_attachments/' . $filename;
            }

            $message = Message::query()->create([
                'thread_id' => $thread->id,
                'sender_user_id' => $actor->id,
                'sender_role' => $this->normalizeSenderRole($actor->primary_role),
                'body' => $body !== '' ? $body : null,
                'attachment' => $attachmentPath,
                'status' => 'SENT',
            ]);

            $thread->update([
                'last_message_id' => $message->id,
                'last_message_at' => $message->created_at,
            ]);

            return $message;
        });
    }

    /**
     * Resolve the current actor (dealer, converter, brand, machine_dealer) from the authenticated user.
     *
     * @return array{type: string, model: Dealer|Converter|Brand|MachineDealer}
     */
    private function resolveActor(int $userId): array
    {
        $dealer = Dealer::where('user_id', $userId)->first();
        if ($dealer) {
            return ['type' => 'dealer', 'model' => $dealer];
        }

        $converter = Converter::where('user_id', $userId)->first();
        if ($converter) {
            return ['type' => 'converter', 'model' => $converter];
        }

        $brand = Brand::where('user_id', $userId)->first();
        if ($brand) {
            return ['type' => 'brand', 'model' => $brand];
        }

        $machineDealer = MachineDealer::where('user_id', $userId)->first();
        if ($machineDealer) {
            return ['type' => 'machine_dealer', 'model' => $machineDealer];
        }

        throw new \Exception('No profile found for this user. Please complete registration.', 404);
    }

    /**
     * Get chat list (conversations) for the current user (any role).
     * One chat row per (session, partner):
     * - Poster: one row per responder on each of their posts (B and C both responded to A's post → A sees 2 chats: B, C).
     * - Responder: one row per session they responded to (B responded to A's post and C's post → B sees 2 chats: A, C).
     *
     * @return array<int, array{session_id: int, inquiry_id: int, partner_id: int|null, partner_name: string, partner_company: string, last_message: string, last_message_at: string|null, unread_count: int}>
     */
    public function getChatList(int $userId): array
    {
        $actor = $this->resolveActor($userId);
        $actorType = $actor['type'];
        $actorModel = $actor['model'];
        $actorId = $actorModel->id;

        // 1. Sessions where the actor has an active SessionParticipant (chat-enabled sessions)
        $sessionIdsFromParticipants = MatchingSession::whereHas('participants', function ($q) use ($actorType, $actorId) {
            $q->where('participant_type', $actorType)
                ->where('participant_id', $actorId)
                ->where('status', 'active');
        })->pluck('id')->all();

        // 2. Legacy: for dealers only, sessions where dealer is poster or responder via matchmaking (may not have participants yet)
        $legacySessionIds = [];
        if ($actorType === 'dealer') {
            $legacySessionIds = MatchingSession::with(['inquiry'])
                ->where(function ($q) use ($actorModel) {
                    $q->whereHas('inquiry', function ($inq) use ($actorModel) {
                        $inq->whereRaw('LOWER(poster_type) = ?', ['dealer'])->where('poster_id', $actorModel->id)
                            ->whereHas('matchmakingLogs', fn ($m) => $m->whereNotNull('responded_at'));
                    })
                    ->orWhereHas('inquiry.matchmakingLogs', function ($m) use ($actorModel) {
                        $m->where('dealer_id', $actorModel->id)->whereNotNull('responded_at');
                    });
                })
                ->pluck('id')
                ->all();
        }

        // 3. Any role as poster: sessions where actor is poster and there are responders (matchmaking or participants)
        $posterSessionIds = MatchingSession::whereHas('inquiry', function ($inq) use ($actorType, $actorId) {
            $inq->whereRaw('LOWER(poster_type) = ?', [$actorType])->where('poster_id', $actorId);
        })
            ->where(function ($q) {
                $q->whereHas('inquiry.matchmakingLogs', fn ($m) => $m->whereNotNull('responded_at'))
                    ->orWhereHas('participants', fn ($p) => $p->where('role', 'responder'));
            })
            ->pluck('id')
            ->all();

        $allSessionIds = array_values(array_unique(array_merge($sessionIdsFromParticipants, $legacySessionIds, $posterSessionIds)));
        if (empty($allSessionIds)) {
            return [];
        }

        $sessions = MatchingSession::with([
            'inquiry',
            'inquiry.poster',
            'inquiry.matchmakingLogs' => function ($q) {
                $q->whereNotNull('responded_at')->with(['dealer.user', 'converter.user', 'machineDealer.user']);
            },
            'inquiry.acceptances.dealer.user',
            'participants' => function ($q) {
                $q->where('status', 'active')->with('participant');
            },
        ])
            ->whereIn('id', $allSessionIds)
            ->orderByDesc('updated_at')
            ->get();

        $list = [];
        $seenSessionPartner = []; // avoid duplicate (session_id, partner_user_id) rows

        foreach ($sessions as $session) {
            $inquiry = $session->inquiry;
            if (!$inquiry) {
                continue;
            }

            $posterType = strtolower((string) $inquiry->poster_type);
            $posterId = (int) $inquiry->poster_id;
            $isPoster = $posterType === $actorType && $posterId === (int) $actorId;

            $hasParticipants = $session->participants->isNotEmpty();

            if ($hasParticipants) {
                // Participant-based: one row per partner (poster sees each responder; responder sees poster)
                if ($isPoster) {
                    $responderParticipants = $session->participants->where('role', 'responder');
                    foreach ($responderParticipants as $p) {
                        $partnerUserId = $this->getPartnerUserIdFromParticipant($p);
                        $key = $session->id . '_' . ($partnerUserId ?? 'n' . $p->participant_type . $p->participant_id);
                        if (isset($seenSessionPartner[$key])) {
                            continue;
                        }
                        $seenSessionPartner[$key] = true;
                        $partnerInfo = $this->getPartnerDisplayFromParticipant($p);
                        $list[] = $this->buildChatListItem($session, $partnerInfo['partner_id'], $partnerInfo['partner_name'], $partnerInfo['partner_company']);
                    }
                } else {
                    $poster = $inquiry->poster;
                    $partnerInfo = $this->getPartnerDisplayFromPoster($poster);
                    $key = $session->id . '_' . ($partnerInfo['partner_id'] ?? 'p');
                    if (!isset($seenSessionPartner[$key])) {
                        $seenSessionPartner[$key] = true;
                        $list[] = $this->buildChatListItem($session, $partnerInfo['partner_id'], $partnerInfo['partner_name'], $partnerInfo['partner_company']);
                    }
                }
            } else {
                // No participants yet: use matchmakingLogs + acceptances to build partner list
                if ($isPoster) {
                    // Poster (any role): one row per responder from matchmaking logs or acceptances
                    $responderIdsByType = [];
                    foreach ($inquiry->matchmakingLogs->whereNotNull('responded_at') as $log) {
                        if ($log->dealer_id && $actorType !== 'dealer') {
                            $responderIdsByType['dealer'][$log->dealer_id] = true;
                        }
                        if ($log->converter_id && $actorType !== 'converter') {
                            $responderIdsByType['converter'][$log->converter_id] = true;
                        }
                        if ($log->machine_dealer_id && $actorType !== 'machine_dealer') {
                            $responderIdsByType['machine_dealer'][$log->machine_dealer_id] = true;
                        }
                    }
                    foreach ($inquiry->acceptances ?? [] as $acc) {
                        if ($acc->dealer_id) {
                            $responderIdsByType['dealer'][$acc->dealer_id] = true;
                        }
                    }
                    foreach ($responderIdsByType as $type => $ids) {
                        foreach (array_keys($ids) as $pid) {
                            $key = $session->id . '_' . $type . $pid;
                            if (isset($seenSessionPartner[$key])) {
                                continue;
                            }
                            $seenSessionPartner[$key] = true;
                            $partnerInfo = $this->getPartnerDisplayByTypeAndId($type, $pid);
                            $list[] = $this->buildChatListItem($session, $partnerInfo['partner_id'], $partnerInfo['partner_name'], $partnerInfo['partner_company']);
                        }
                    }
                } else {
                    // Responder (any role): one row, partner = poster
                    $poster = $inquiry->poster;
                    $partnerInfo = $this->getPartnerDisplayFromPoster($poster);
                    $key = $session->id . '_' . ($partnerInfo['partner_id'] ?? 'p');
                    if (!isset($seenSessionPartner[$key])) {
                        $seenSessionPartner[$key] = true;
                        $list[] = $this->buildChatListItem($session, $partnerInfo['partner_id'], $partnerInfo['partner_name'], $partnerInfo['partner_company']);
                    }
                }
            }
        }

        return $list;
    }

    /**
     * Build one chat list item (session_id, inquiry_id, partner_*, last_message, last_message_at, unread_count).
     */
    private function buildChatListItem(MatchingSession $session, ?int $partnerUserId, string $partnerName, string $partnerCompany): array
    {
        $lastMsg = ChatMessage::where('session_id', $session->id)->orderByDesc('created_at')->first();
        $lastMessage = '';
        $lastMessageAt = null;
        if ($lastMsg) {
            $lastMessageAt = $lastMsg->created_at->toIso8601String();
            $lastMessage = $lastMsg->message ? \Illuminate\Support\Str::limit($lastMsg->message, 60) : ($lastMsg->attachment_path ? '[Attachment]' : '');
        }
        return [
            'session_id' => $session->id,
            'inquiry_id' => $session->inquiry_id,
            'partner_id' => $partnerUserId,
            'partner_name' => $partnerName,
            'partner_company' => $partnerCompany ?: $partnerName,
            'last_message' => $lastMessage,
            'last_message_at' => $lastMessageAt,
            'unread_count' => 0,
        ];
    }

    /**
     * Get partner user_id from a SessionParticipant (responder).
     */
    private function getPartnerUserIdFromParticipant(SessionParticipant $p): ?int
    {
        $p->loadMissing('participant');
        $model = $p->participant;
        if (!$model) {
            return null;
        }
        return $model->user_id ?? null;
    }

    /**
     * Get partner display (partner_id = user_id, partner_name, partner_company) from a SessionParticipant.
     */
    private function getPartnerDisplayFromParticipant(SessionParticipant $p): array
    {
        $p->loadMissing('participant');
        $model = $p->participant;
        if (!$model) {
            return ['partner_id' => null, 'partner_name' => 'Unknown', 'partner_company' => ''];
        }
        if ($model instanceof Dealer) {
            $model->loadMissing('user');
            return [
                'partner_id' => $model->user_id,
                'partner_name' => $model->user->name ?? 'Dealer',
                'partner_company' => $model->user->company_name ?? '',
            ];
        }
        if ($model instanceof Converter) {
            $model->loadMissing('user');
            return [
                'partner_id' => $model->user_id,
                'partner_name' => $model->user->name ?? 'Converter',
                'partner_company' => $model->user->company_name ?? '',
            ];
        }
        if ($model instanceof Brand) {
            return [
                'partner_id' => $model->user_id,
                'partner_name' => $model->contact_person_name ?? $model->company_name ?? $model->brand_name ?? 'Brand',
                'partner_company' => $model->company_name ?? $model->brand_name ?? '',
            ];
        }
        if ($model instanceof MachineDealer) {
            return [
                'partner_id' => $model->user_id,
                'partner_name' => $model->contact_person_name ?? $model->company_name ?? 'Machine Dealer',
                'partner_company' => $model->company_name ?? '',
            ];
        }
        return ['partner_id' => $model->user_id ?? null, 'partner_name' => 'Unknown', 'partner_company' => ''];
    }

    /**
     * Get partner display from inquiry poster (morph).
     */
    private function getPartnerDisplayFromPoster($poster): array
    {
        if (!$poster) {
            return ['partner_id' => null, 'partner_name' => 'Unknown', 'partner_company' => ''];
        }
        if ($poster instanceof Dealer) {
            $poster->loadMissing('user');
            return [
                'partner_id' => $poster->user_id,
                'partner_name' => $poster->user->name ?? 'Dealer',
                'partner_company' => $poster->user->company_name ?? '',
            ];
        }
        if ($poster instanceof Converter) {
            $poster->loadMissing('user');
            return [
                'partner_id' => $poster->user_id,
                'partner_name' => $poster->user->name ?? 'Converter',
                'partner_company' => $poster->user->company_name ?? '',
            ];
        }
        if ($poster instanceof Brand) {
            return [
                'partner_id' => $poster->user_id,
                'partner_name' => $poster->contact_person_name ?? $poster->company_name ?? $poster->brand_name ?? 'Partner',
                'partner_company' => $poster->company_name ?? $poster->brand_name ?? '',
            ];
        }
        if ($poster instanceof MachineDealer) {
            return [
                'partner_id' => $poster->user_id,
                'partner_name' => $poster->contact_person_name ?? $poster->company_name ?? 'Partner',
                'partner_company' => $poster->company_name ?? '',
            ];
        }
        return ['partner_id' => $poster->user_id ?? null, 'partner_name' => 'Unknown', 'partner_company' => ''];
    }

    /**
     * Get partner display from participant_type and participant_id (e.g. from matchmaking log).
     */
    private function getPartnerDisplayByTypeAndId(string $type, int $participantId): array
    {
        $type = strtolower($type);
        if ($type === 'dealer') {
            $model = Dealer::with('user')->find($participantId);
            if (!$model) {
                return ['partner_id' => null, 'partner_name' => 'Unknown', 'partner_company' => ''];
            }
            return [
                'partner_id' => $model->user_id,
                'partner_name' => $model->user->name ?? 'Dealer',
                'partner_company' => $model->user->company_name ?? '',
            ];
        }
        if ($type === 'converter') {
            $model = Converter::with('user')->find($participantId);
            if (!$model) {
                return ['partner_id' => null, 'partner_name' => 'Unknown', 'partner_company' => ''];
            }
            return [
                'partner_id' => $model->user_id,
                'partner_name' => $model->user->name ?? 'Converter',
                'partner_company' => $model->user->company_name ?? '',
            ];
        }
        if ($type === 'brand') {
            $model = Brand::find($participantId);
            if (!$model) {
                return ['partner_id' => null, 'partner_name' => 'Unknown', 'partner_company' => ''];
            }
            return [
                'partner_id' => $model->user_id,
                'partner_name' => $model->contact_person_name ?? $model->company_name ?? $model->brand_name ?? 'Brand',
                'partner_company' => $model->company_name ?? $model->brand_name ?? '',
            ];
        }
        if ($type === 'machine_dealer') {
            $model = MachineDealer::find($participantId);
            if (!$model) {
                return ['partner_id' => null, 'partner_name' => 'Unknown', 'partner_company' => ''];
            }
            return [
                'partner_id' => $model->user_id,
                'partner_name' => $model->contact_person_name ?? $model->company_name ?? 'Machine Dealer',
                'partner_company' => $model->company_name ?? '',
            ];
        }
        return ['partner_id' => null, 'partner_name' => 'Unknown', 'partner_company' => ''];
    }

    public function getMessages(int $sessionId, int $userId, array $filters = []): array
    {
        $actor = $this->resolveActor($userId);
        $actorType = $actor['type'];
        $actorModel = $actor['model'];

        $session = MatchingSession::findOrFail($sessionId);
        $inquiry = $session->inquiry;

        // Authorize: actor must be active participant, or (fallback) poster / responder via matchmaking
        $isParticipant = SessionParticipant::where('session_id', $sessionId)
            ->where('participant_type', $actorType)
            ->where('participant_id', $actorModel->id)
            ->where('status', 'active')
            ->exists();

        $isPoster = $inquiry && strtolower((string) $inquiry->poster_type) === $actorType && (int) $inquiry->poster_id === (int) $actorModel->id;
        $isResponder = false;
        if ($inquiry) {
            $isResponder = $inquiry->acceptances()->where('dealer_id', $actorModel->id)->exists()
                || $inquiry->matchmakingLogs()->where('dealer_id', $actorModel->id)->whereNotNull('responded_at')->exists()
                || $inquiry->matchmakingLogs()->where('converter_id', $actorModel->id)->whereNotNull('responded_at')->exists()
                || $inquiry->matchmakingLogs()->where('machine_dealer_id', $actorModel->id)->whereNotNull('responded_at')->exists();
        }

        if (!$isParticipant && !$isPoster && !$isResponder) {
            throw new \Exception('You are not part of this session', 403);
        }

        $perPage = $filters['per_page'] ?? 50; // Default to 50 for chat messages

        $messages = ChatMessage::where('session_id', $sessionId)
            ->with('sender')
            ->orderBy('created_at', 'desc') // Most recent first for pagination
            ->paginate($perPage);

        $messagesData = $messages->map(function ($message) use ($userId) {
            return [
                'id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_type' => $message->sender_type,
                'sender_name' => $message->sender->name ?? 'Unknown',
                'message' => $message->message,
                'attachment_path' => $message->attachment_path,
                'attachment_url' => $message->attachment_path ? url($message->attachment_path) : null,
                'status' => $message->status,
                'created_at' => $message->created_at->toIso8601String(),
                'is_own_message' => $message->sender_id === $userId,
            ];
        });

        // Reverse to get chronological order
        $messagesData = $messagesData->reverse()->values();

        return [
            'messages' => $messagesData->toArray(),
            'pagination' => [
                'current_page' => $messages->currentPage(),
                'total' => $messages->total(),
                'per_page' => $messages->perPage(),
                'last_page' => $messages->lastPage(),
            ],
        ];
    }

    public function sendMessage(int $sessionId, int $userId, array $data): ChatMessage
    {
        $actor = $this->resolveActor($userId);
        $actorType = $actor['type'];
        $actorModel = $actor['model'];

        $session = MatchingSession::findOrFail($sessionId);
        $inquiry = $session->inquiry;

        // Authorize: same as getMessages
        $isParticipant = SessionParticipant::where('session_id', $sessionId)
            ->where('participant_type', $actorType)
            ->where('participant_id', $actorModel->id)
            ->where('status', 'active')
            ->exists();

        $isPoster = $inquiry && strtolower((string) $inquiry->poster_type) === $actorType && (int) $inquiry->poster_id === (int) $actorModel->id;
        $isResponder = false;
        if ($inquiry) {
            $isResponder = $inquiry->acceptances()->where('dealer_id', $actorModel->id)->exists()
                || $inquiry->matchmakingLogs()->where('dealer_id', $actorModel->id)->whereNotNull('responded_at')->exists()
                || $inquiry->matchmakingLogs()->where('converter_id', $actorModel->id)->whereNotNull('responded_at')->exists()
                || $inquiry->matchmakingLogs()->where('machine_dealer_id', $actorModel->id)->whereNotNull('responded_at')->exists();
        }

        if (!$isParticipant && !$isPoster && !$isResponder) {
            throw new \Exception('You are not part of this session', 403);
        }

        $attachmentPath = null;

        if (isset($data['attachment']) && $data['attachment']) {
            $file = $data['attachment'];
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('chat_attachments'), $filename);
            $attachmentPath = 'chat_attachments/' . $filename;
        }

        $senderType = strtoupper($actorType); // dealer -> DEALER, machine_dealer -> MACHINE_DEALER

        return ChatMessage::create([
            'session_id' => $sessionId,
            'sender_id' => $userId,
            'sender_type' => $senderType,
            'message' => $data['message'] ?? null,
            'attachment_path' => $attachmentPath,
            'status' => 'SENT',
        ]);
    }

    private function resolvePosterUserId(Inquiry $inquiry): int
    {
        $poster = $inquiry->poster;

        if (!$poster || !isset($poster->user_id) || !$poster->user_id) {
            throw new \Exception('Unable to resolve inquiry poster user.', 422);
        }

        return (int) $poster->user_id;
    }

    private function isDuplicateKeyException(QueryException $e): bool
    {
        $sqlState = $e->errorInfo[0] ?? null;
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        return $sqlState === '23000' || $driverCode === 1062;
    }

    private function normalizeSenderRole(?string $role): string
    {
        $role = strtolower((string) $role);

        return match ($role) {
            'machine-dealer', 'machinedealer', 'machine_dealer' => 'MACHINE_DEALER',
            'converter' => 'CONVERTER',
            'brand' => 'BRAND',
            default => 'DEALER',
        };
    }

    private function chatThreadSessionIdNullable(): bool
    {
        if (!Schema::hasColumn('chat_threads', 'session_id')) {
            return true;
        }

        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            $row = DB::selectOne("SHOW COLUMNS FROM `chat_threads` LIKE 'session_id'");
            if (!$row) {
                return true;
            }

            return strtoupper((string) ($row->Null ?? 'YES')) === 'YES';
        }

        return true;
    }
}




