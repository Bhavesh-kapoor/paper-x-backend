<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Models\ChatMessage;
use App\Models\Dealer;
use App\Models\MatchingSession;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ChatService
{
    /**
     * Get chat list (conversations) for the current dealer.
     * One chat row per (session, partner):
     * - Poster: one row per responder on each of their posts (B and C both responded to A's post → A sees 2 chats: B, C).
     * - Responder: one row per session they responded to (B responded to A's post and C's post → B sees 2 chats: A, C).
     *
     * @return array<int, array{session_id: int, inquiry_id: int, partner_id: int|null, partner_name: string, partner_company: string, last_message: string, last_message_at: string|null, unread_count: int}>
     */
    public function getChatList(int $userId): array
    {
        $dealer = Dealer::where('user_id', $userId)->firstOrFail();

        $sessions = MatchingSession::with([
            'inquiry',
            'inquiry.poster',
            'inquiry.matchmakingLogs' => function ($q) {
                $q->whereNotNull('responded_at')->with('dealer.user');
            },
            'inquiry.acceptances.dealer.user',
            'winningDealer.user',
        ])
            ->where(function ($q) use ($dealer) {
                $q->whereHas('inquiry', function ($inq) use ($dealer) {
                    $inq->whereRaw('LOWER(poster_type) = ?', ['dealer'])->where('poster_id', $dealer->id)
                        ->whereHas('matchmakingLogs', fn ($m) => $m->whereNotNull('responded_at'));
                })
                ->orWhereHas('inquiry.matchmakingLogs', function ($m) use ($dealer) {
                    $m->where('dealer_id', $dealer->id)->whereNotNull('responded_at');
                });
            })
            ->orderByDesc('updated_at')
            ->get();

        $list = [];
        foreach ($sessions as $session) {
            $inquiry = $session->inquiry;
            $isPoster = $inquiry && strtolower((string) $inquiry->poster_type) === 'dealer' && (int) $inquiry->poster_id === (int) $dealer->id;

            if ($isPoster) {
                // Poster: one chat per responder (B and C responded → two rows: session+partner B, session+partner C).
                $responderDealerIds = $inquiry->matchmakingLogs->pluck('dealer_id')->merge($inquiry->acceptances->pluck('dealer_id'))->unique()->filter(fn ($id) => (int) $id !== (int) $dealer->id);
                foreach ($responderDealerIds as $partnerDealerId) {
                    $partnerDealer = $inquiry->matchmakingLogs->first(fn ($m) => (int) $m->dealer_id === (int) $partnerDealerId)?->dealer
                        ?? $inquiry->acceptances->first(fn ($a) => (int) $a->dealer_id === (int) $partnerDealerId)?->dealer;
                    if (!$partnerDealer) {
                        $partnerDealer = Dealer::with('user')->find($partnerDealerId);
                    }
                    $partnerName = 'Unknown';
                    $partnerCompany = '';
                    $partnerUserId = null;
                    if ($partnerDealer) {
                        $partnerDealer->loadMissing('user');
                        $partnerName = $partnerDealer->user->name ?? 'Dealer';
                        $partnerCompany = $partnerDealer->user->company_name ?? '';
                        $partnerUserId = $partnerDealer->user_id;
                    }
                    $lastMsg = ChatMessage::where('session_id', $session->id)->orderByDesc('created_at')->first();
                    $lastMessage = '';
                    $lastMessageAt = null;
                    if ($lastMsg) {
                        $lastMessageAt = $lastMsg->created_at->toIso8601String();
                        $lastMessage = $lastMsg->message ? \Illuminate\Support\Str::limit($lastMsg->message, 60) : ($lastMsg->attachment_path ? '[Attachment]' : '');
                    }
                    $list[] = [
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
            } else {
                // Responder: one chat per session (one row; partner = poster).
                $poster = $inquiry?->poster;
                $partnerName = 'Unknown';
                $partnerCompany = '';
                $partnerUserId = null;
                if ($poster) {
                    if ($poster instanceof Dealer) {
                        $poster->loadMissing('user');
                        $partnerName = $poster->user->name ?? 'Dealer';
                        $partnerCompany = $poster->user->company_name ?? '';
                        $partnerUserId = $poster->user_id;
                    } else {
                        $partnerName = $poster->contact_person_name ?? $poster->name ?? $poster->company_name ?? $poster->brand_name ?? 'Partner';
                        $partnerCompany = $poster->company_name ?? $poster->brand_name ?? '';
                    }
                }
                $lastMsg = ChatMessage::where('session_id', $session->id)->orderByDesc('created_at')->first();
                $lastMessage = '';
                $lastMessageAt = null;
                if ($lastMsg) {
                    $lastMessageAt = $lastMsg->created_at->toIso8601String();
                    $lastMessage = $lastMsg->message ? \Illuminate\Support\Str::limit($lastMsg->message, 60) : ($lastMsg->attachment_path ? '[Attachment]' : '');
                }
                $list[] = [
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
        }

        return $list;
    }

    public function getMessages(int $sessionId, int $userId, array $filters = []): array
    {
        $dealer = Dealer::where('user_id', $userId)->firstOrFail();

        $session = MatchingSession::findOrFail($sessionId);
        $inquiry = $session->inquiry;

        // Verify dealer is part of session: poster, in acceptances, or expressed interest (MatchmakingLog.responded_at)
        $isPoster = $inquiry && strtolower((string) $inquiry->poster_type) === 'dealer' && (int) $inquiry->poster_id === (int) $dealer->id;
        $isResponder = $inquiry->acceptances()->where('dealer_id', $dealer->id)->exists()
            || $inquiry->matchmakingLogs()->where('dealer_id', $dealer->id)->whereNotNull('responded_at')->exists();
        if (!$isPoster && !$isResponder) {
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
        $dealer = Dealer::where('user_id', $userId)->firstOrFail();

        $session = MatchingSession::findOrFail($sessionId);
        $inquiry = $session->inquiry;

        // Verify dealer is part of session: poster, in acceptances, or expressed interest (MatchmakingLog.responded_at)
        $isPoster = $inquiry && strtolower((string) $inquiry->poster_type) === 'dealer' && (int) $inquiry->poster_id === (int) $dealer->id;
        $isResponder = $inquiry->acceptances()->where('dealer_id', $dealer->id)->exists()
            || $inquiry->matchmakingLogs()->where('dealer_id', $dealer->id)->whereNotNull('responded_at')->exists();
        if (!$isPoster && !$isResponder) {
            throw new \Exception('You are not part of this session', 403);
        }

        $attachmentPath = null;

        if (isset($data['attachment']) && $data['attachment']) {
            $file = $data['attachment'];
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('chat_attachments'), $filename);
            $attachmentPath = 'chat_attachments/' . $filename;
        }

        return ChatMessage::create([
            'session_id' => $sessionId,
            'sender_id' => $userId,
            'sender_type' => 'DEALER',
            'message' => $data['message'] ?? null,
            'attachment_path' => $attachmentPath,
            'status' => 'SENT',
        ]);
    }
}




