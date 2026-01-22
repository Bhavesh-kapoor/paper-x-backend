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
        // Ensure inquiry is loaded
        $inquiry = $session->inquiry;
        if (!$inquiry) {
            return false;
        }
        
        // DEALER CHECK FIRST - Dealers should ALWAYS see their own posted requirements
        if ($user->dealer && $user->dealer->id) {
            $dealerId = $user->dealer->id;
            
            // CRITICAL: If dealer posted this requirement, they MUST be able to view it
            // This is the most important check - dealers need to see responses to their own posts
            if ($inquiry->poster_type === 'dealer') {
                // Use loose comparison to handle any type mismatches (string vs int)
                $posterId = $inquiry->poster_id;
                if ($posterId != null) {
                    // Multiple comparison methods to ensure it works
                    if ($posterId == $dealerId || 
                        (string)$posterId === (string)$dealerId || 
                        (int)$posterId === (int)$dealerId ||
                        $posterId === $dealerId) {
                        return true; // OWN POST - ALWAYS ALLOW
                    }
                }
            }
            
            // Matched dealer inquiries (through participants or matchmaking)
            if ($session->is_visible_to_dealers) {
                // Has participant record
                $hasParticipant = $session->participants()
                    ->where('participant_type', 'dealer')
                    ->where('participant_id', $dealerId)
                    ->where('status', 'active')
                    ->exists();
                
                if ($hasParticipant) {
                    return true;
                }
                
                // OR has matchmaking log (for newly matched dealers)
                if ($inquiry->relationLoaded('matchmakingLogs')) {
                    $hasMatchmakingLog = $inquiry->matchmakingLogs
                        ->where('dealer_id', $dealerId)
                        ->where('is_visible', true)
                        ->isNotEmpty();
                } else {
                    $hasMatchmakingLog = $inquiry->matchmakingLogs()
                        ->where('dealer_id', $dealerId)
                        ->where('is_visible', true)
                        ->exists();
                }
                
                return $hasMatchmakingLog;
            }
            return false;
        }
        
        // Brand can view their own posted requirements (brand-posted inquiries)
        // NEVER see dealer-posted requirements
        if ($user->brand) {
            return $inquiry->poster_type === 'brand' && $inquiry->poster_id === $user->brand->id;
        }
        
        // Converter can view:
        // 1. Their own posted requirements (converter-posted inquiries)
        // 2. Dealer-posted requirements where visibility = 'converters' or 'all'
        if ($user->converter) {
            // Own posted requirements
            if ($inquiry->poster_type === 'converter' && $inquiry->poster_id === $user->converter->id) {
                return true;
            }
            // Dealer-posted requirements visible to converters
            if ($inquiry->poster_type === 'dealer' && 
                ($inquiry->visibility === 'converters' || $inquiry->visibility === 'all')) {
                return true;
            }
            return false;
        }
        
        // Machine dealer can view dealer-posted requirements where visibility = 'all'
        if ($user->machineDealer) {
            return $inquiry->poster_type === 'dealer' && $inquiry->visibility === 'all';
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
