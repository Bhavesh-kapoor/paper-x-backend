<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Models\Brand;
use App\Models\ChatMessage;
use App\Models\Converter;
use App\Models\Dealer;
use App\Models\MatchingSession;
use App\Models\MachineDealer;
use App\Models\SessionParticipant;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ChatService
{
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
                $q->whereNotNull('responded_at')->with(['dealer.user', 'converter.user']);
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
                        if ($log->dealer_id && (int) $log->dealer_id !== (int) $actorId) {
                            $responderIdsByType['dealer'][$log->dealer_id] = true;
                        }
                        if ($log->converter_id && (int) $log->converter_id !== (int) $actorId) {
                            $responderIdsByType['converter'][$log->converter_id] = true;
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
                || $inquiry->matchmakingLogs()->where('converter_id', $actorModel->id)->whereNotNull('responded_at')->exists();
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
                || $inquiry->matchmakingLogs()->where('converter_id', $actorModel->id)->whereNotNull('responded_at')->exists();
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
}




