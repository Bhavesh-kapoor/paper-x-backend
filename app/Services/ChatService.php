<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\Dealer;
use App\Models\MatchingSession;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ChatService
{
    public function getMessages(int $sessionId, int $userId): array
    {
        $dealer = Dealer::where('user_id', $userId)->firstOrFail();

        $session = MatchingSession::findOrFail($sessionId);

        // Verify dealer is part of session
        $isParticipant = $session->inquiry->acceptances()
            ->where('dealer_id', $dealer->id)
            ->exists();

        if (!$isParticipant) {
            throw new \Exception('You are not part of this session', 403);
        }

        $messages = ChatMessage::where('session_id', $sessionId)
            ->with('sender')
            ->orderBy('created_at', 'asc')
            ->get();

        return $messages->map(function ($message) use ($userId) {
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
        })->toArray();
    }

    public function sendMessage(int $sessionId, int $userId, array $data): ChatMessage
    {
        $dealer = Dealer::where('user_id', $userId)->firstOrFail();

        $session = MatchingSession::findOrFail($sessionId);

        // Verify dealer is part of session
        $isParticipant = $session->inquiry->acceptances()
            ->where('dealer_id', $dealer->id)
            ->exists();

        if (!$isParticipant) {
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

