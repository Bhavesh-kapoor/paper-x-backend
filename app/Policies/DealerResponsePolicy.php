<?php

namespace App\Policies;

use App\Models\Response;
use App\Models\User;
use App\Enums\ResponseStatus;

class DealerResponsePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Brand/converter can view responses to their inquiries
        // Dealer can view their own responses
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Response $response): bool
    {
        // Brand/Converter can view responses to their inquiries
        $inquiry = $response->inquiry;
        if ($user->brand && $inquiry->poster_type === 'brand' && $inquiry->poster_id === $user->brand->id) {
            return true;
        }
        
        if ($user->converter && $inquiry->poster_type === 'converter' && $inquiry->poster_id === $user->converter->id) {
            return true;
        }
        
        // Dealer can view their own responses
        if ($user->dealer && $response->responder_id === $user->id) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only dealers can create responses
        return $user->dealer !== null;
    }

    /**
     * Determine whether the user can respond to an inquiry.
     */
    public function respond(User $user, $inquiry): bool
    {
        // Only dealers can respond
        if (!$user->dealer) {
            return false;
        }
        
        // Inquiry must be visible to dealers
        if (!$inquiry->is_visible_to_dealers) {
            return false;
        }
        
        // Dealer must be matched
        $matchmakingLog = $inquiry->matchmakingLogs()
            ->where('dealer_id', $user->dealer->id)
            ->where('is_visible', true)
            ->first();
        
        if (!$matchmakingLog) {
            return false;
        }
        
        // Check if already responded
        $existingResponse = $inquiry->responses()
            ->where('responder_id', $user->id)
            ->exists();
        
        return !$existingResponse;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Response $response): bool
    {
        // Only dealer can update their own response, and only if not selected yet
        if (!$user->dealer || $response->responder_id !== $user->id) {
            return false;
        }
        
        // Cannot update if session is locked
        if ($response->session && $response->session->status === \App\Enums\SessionStatus::LOCKED) {
            return false;
        }
        
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Response $response): bool
    {
        // Only dealer can delete their own response, and only if not selected
        if (!$user->dealer || $response->responder_id !== $user->id) {
            return false;
        }
        
        // Cannot delete if session is locked
        if ($response->session && $response->session->status === \App\Enums\SessionStatus::LOCKED) {
            return false;
        }
        
        return true;
    }
}
