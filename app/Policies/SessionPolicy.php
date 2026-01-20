<?php

namespace App\Policies;

use App\Models\MatchingSession;
use App\Models\User;
use App\Enums\SessionStatus;

class SessionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true; // Both brand and dealer can view sessions
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MatchingSession $session): bool
    {
        // Brand/Converter can view their own sessions
        if ($user->brand || $user->converter) {
            $inquiry = $session->inquiry;
            if ($user->brand && $inquiry->poster_type === 'brand' && $inquiry->poster_id === $user->brand->id) {
                return true;
            }
            if ($user->converter && $inquiry->poster_type === 'converter' && $inquiry->poster_id === $user->converter->id) {
                return true;
            }
        }
        
        // Dealer can view if they are a participant
        if ($user->dealer) {
            return $session->participants()
                ->where('participant_type', 'dealer')
                ->where('participant_id', $user->dealer->id)
                ->where('status', 'active')
                ->exists();
        }
        
        return false;
    }

    /**
     * Determine whether the user can lock the session.
     */
    public function lock(User $user, MatchingSession $session): bool
    {
        // Only poster (brand/converter) can lock
        if (!$user->brand && !$user->converter) {
            return false;
        }
        
        // Can only lock if status is RESPONSES_RECEIVED
        if ($session->status !== SessionStatus::RESPONSES_RECEIVED) {
            return false;
        }
        
        $inquiry = $session->inquiry;
        if ($user->brand && $inquiry->poster_type === 'brand' && $inquiry->poster_id === $user->brand->id) {
            return true;
        }
        
        if ($user->converter && $inquiry->poster_type === 'converter' && $inquiry->poster_id === $user->converter->id) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can access chat.
     */
    public function chat(User $user, MatchingSession $session): bool
    {
        // Chat only available after lock
        if (!$session->chat_enabled || $session->status !== SessionStatus::LOCKED && $session->status !== SessionStatus::CHAT_ACTIVE) {
            return false;
        }
        
        // Check if user is a participant with chat permission
        $participant = $session->participants()
            ->where(function ($q) use ($user) {
                if ($user->brand) {
                    $q->where('participant_type', 'brand')
                        ->where('participant_id', $user->brand->id);
                } elseif ($user->converter) {
                    $q->where('participant_type', 'converter')
                        ->where('participant_id', $user->converter->id);
                } elseif ($user->dealer) {
                    $q->where('participant_type', 'dealer')
                        ->where('participant_id', $user->dealer->id);
                }
            })
            ->where('can_chat', true)
            ->where('status', 'active')
            ->first();
        
        return $participant !== null;
    }

    /**
     * Determine whether the user can mark deal as failed.
     */
    public function markDealFailed(User $user, MatchingSession $session): bool
    {
        // Only participants can mark deal as failed
        return $this->view($user, $session) && 
               ($session->status === SessionStatus::CHAT_ACTIVE || $session->status === SessionStatus::LOCKED);
    }

    /**
     * Determine whether the user can republish the session.
     */
    public function republish(User $user, MatchingSession $session): bool
    {
        // Only poster can republish
        if (!$user->brand && !$user->converter) {
            return false;
        }
        
        // Can only republish if cooldown expired
        if (!$session->canRepublish()->where('id', $session->id)->exists()) {
            return false;
        }
        
        $inquiry = $session->inquiry;
        if ($user->brand && $inquiry->poster_type === 'brand' && $inquiry->poster_id === $user->brand->id) {
            return true;
        }
        
        if ($user->converter && $inquiry->poster_type === 'converter' && $inquiry->poster_id === $user->converter->id) {
            return true;
        }
        
        return false;
    }
}
