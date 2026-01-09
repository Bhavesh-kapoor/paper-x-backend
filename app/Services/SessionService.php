<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\MatchingSession;

class SessionService
{
    public function getSession(int $sessionId, int $userId): array
    {
        $dealer = Dealer::where('user_id', $userId)->firstOrFail();

        $session = MatchingSession::with(['inquiry.brand', 'inquiry.acceptances' => function ($query) use ($dealer) {
            $query->where('dealer_id', $dealer->id);
        }])
            ->findOrFail($sessionId);

        // Check if dealer is part of this session
        $isParticipant = $session->inquiry->acceptances()
            ->where('dealer_id', $dealer->id)
            ->exists();

        if (!$isParticipant) {
            throw new \Exception('You are not part of this session', 403);
        }

        $expiresIn = max(0, now()->diffInSeconds($session->expires_at));

        return [
            'session_id' => $session->id,
            'expires_in' => $expiresIn,
            'brand_details' => [
                'name' => $session->inquiry->brand->name,
                'company_name' => $session->inquiry->brand->name,
            ],
            'inquiry' => [
                'id' => $session->inquiry->id,
                'title' => $session->inquiry->title,
                'description' => $session->inquiry->description,
                'quantity' => $session->inquiry->quantity,
                'quantity_unit' => $session->inquiry->quantity_unit,
            ],
            'chat_enabled' => true,
            'status' => $session->status->value,
        ];
    }

    public function getHistory(int $userId, array $filters = []): array
    {
        $dealer = Dealer::where('user_id', $userId)->firstOrFail();

        $perPage = $filters['per_page'] ?? 15;

        $sessions = MatchingSession::whereHas('inquiry.acceptances', function ($query) use ($dealer) {
            $query->where('dealer_id', $dealer->id);
        })
            ->with(['inquiry.brand', 'winningDealer'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        $history = $sessions->map(function ($session) use ($dealer) {
            $isWon = $session->winning_dealer_id === $dealer->id;
            $status = $session->status->value;

            return [
                'session_id' => $session->id,
                'inquiry_id' => $session->inquiry->id,
                'inquiry_title' => $session->inquiry->title,
                'brand_name' => $session->inquiry->brand->name,
                'status' => $status,
                'result' => $this->determineResult($status, $isWon),
                'locked_at' => $session->locked_at->toIso8601String(),
                'expires_at' => $session->expires_at->toIso8601String(),
            ];
        });

        return [
            'history' => $history->toArray(),
            'pagination' => [
                'current_page' => $sessions->currentPage(),
                'total' => $sessions->total(),
                'per_page' => $sessions->perPage(),
                'last_page' => $sessions->lastPage(),
            ],
        ];
    }

    private function determineResult(string $status, bool $isWon): string
    {
        if ($status === 'DEAL_WON' && $isWon) {
            return 'DEAL_WON';
        } elseif ($status === 'DEAL_LOST' || ($status === 'DEAL_WON' && !$isWon)) {
            return 'DEAL_LOST';
        } elseif ($status === 'EXPIRED') {
            return 'SESSION_EXPIRED';
        } elseif ($status === 'CANCELLED') {
            return 'BRAND_CANCELLED';
        }

        return 'PENDING';
    }
}




