<?php

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;
use App\Enums\InquiryStatus;

class InquiryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only brand/converter can view their own inquiries
        return $user->brand || $user->converter;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Inquiry $inquiry): bool
    {
        // Brand/Converter can view their own inquiries
        if ($user->brand && $inquiry->poster_type === 'brand' && $inquiry->poster_id === $user->brand->id) {
            return true;
        }
        
        if ($user->converter && $inquiry->poster_type === 'converter' && $inquiry->poster_id === $user->converter->id) {
            return true;
        }
        
        // Dealer can view if matched and visible
        if ($user->dealer) {
            return $inquiry->is_visible_to_dealers 
                && $inquiry->matchmakingLogs()
                    ->where('dealer_id', $user->dealer->id)
                    ->where('is_visible', true)
                    ->exists();
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only brand/converter can create inquiries
        // Allow if user has brand or converter relationship (even if they also have dealer)
        return $user->brand || $user->converter;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Inquiry $inquiry): bool
    {
        // Only poster can update, and only if in DRAFT or POSTED status
        if ($inquiry->status !== InquiryStatus::DRAFT && $inquiry->status !== InquiryStatus::POSTED) {
            return false;
        }
        
        if ($user->brand && $inquiry->poster_type === 'brand' && $inquiry->poster_id === $user->brand->id) {
            return true;
        }
        
        if ($user->converter && $inquiry->poster_type === 'converter' && $inquiry->poster_id === $user->converter->id) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Inquiry $inquiry): bool
    {
        // Only poster can delete, and only if in DRAFT status
        if ($inquiry->status !== InquiryStatus::DRAFT) {
            return false;
        }
        
        if ($user->brand && $inquiry->poster_type === 'brand' && $inquiry->poster_id === $user->brand->id) {
            return true;
        }
        
        if ($user->converter && $inquiry->poster_type === 'converter' && $inquiry->poster_id === $user->converter->id) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can post the inquiry.
     */
    public function post(User $user, Inquiry $inquiry): bool
    {
        // Only poster can post, and only if in DRAFT status
        if ($inquiry->status !== InquiryStatus::DRAFT) {
            return false;
        }
        
        if ($user->brand && $inquiry->poster_type === 'brand' && $inquiry->poster_id === $user->brand->id) {
            return true;
        }
        
        if ($user->converter && $inquiry->poster_type === 'converter' && $inquiry->poster_id === $user->converter->id) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can view matchmaking responses.
     * Allow: (1) the poster, or (2) a matched responder (has MatchmakingLog for this inquiry) so they can see countdown / submit quote.
     */
    public function viewResponses(User $user, Inquiry $inquiry): bool
    {
        // Poster can view
        if ($user->brand && $inquiry->poster_type === 'brand' && (int) $inquiry->poster_id === (int) $user->brand->id) {
            return true;
        }
        if ($user->converter && $inquiry->poster_type === 'converter' && (int) $inquiry->poster_id === (int) $user->converter->id) {
            return true;
        }
        if ($user->dealer && $inquiry->poster_type === 'dealer' && (int) $inquiry->poster_id === (int) $user->dealer->id) {
            return true;
        }
        if ($user->machineDealer && $inquiry->poster_type === 'machine_dealer' && (int) $inquiry->poster_id === (int) $user->machineDealer->id) {
            return true;
        }

        // Matched responder can view (e.g. for countdown and to know they can submit quote)
        if ($user->dealer && $inquiry->matchmakingLogs()->where('dealer_id', $user->dealer->id)->exists()) {
            return true;
        }
        if ($user->converter && $inquiry->matchmakingLogs()->where('converter_id', $user->converter->id)->exists()) {
            return true;
        }
        if ($user->machineDealer && $inquiry->matchmakingLogs()->where('machine_dealer_id', $user->machineDealer->id)->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can republish the inquiry.
     */
    public function republish(User $user, Inquiry $inquiry): bool
    {
        // First check if user owns the inquiry
        $isOwner = false;
        if ($user->brand && $inquiry->poster_type === 'brand' && $inquiry->poster_id === $user->brand->id) {
            $isOwner = true;
        } elseif ($user->converter && $inquiry->poster_type === 'converter' && $inquiry->poster_id === $user->converter->id) {
            $isOwner = true;
        }
        
        if (!$isOwner) {
            return false;
        }
        
        // Check if inquiry can be republished
        // 1. DRAFT inquiries should be posted first, not republished
        // But allow republishing DRAFT if user wants to create a copy
        // (This allows creating a duplicate of a DRAFT inquiry)
        if ($inquiry->status === InquiryStatus::DRAFT) {
            // Allow republishing DRAFT inquiries (creates a copy/duplicate)
            // This is useful if user wants to start fresh with same data
            return true;
        }
        
        // 2. Check cooldown period (if set, must be expired)
        if ($inquiry->cooldown_until && $inquiry->cooldown_until > now()) {
            return false;
        }
        
        // 3. Check republish count (allow up to 1 republish)
        if (($inquiry->republish_count ?? 0) >= 1) {
            return false;
        }
        
        // Allow republishing for inquiries that are:
        // - MATCHING (no responses yet)
        // - EXPIRED
        // - DEAL_FAILED
        // - DEAL_SUCCESS (if they want to post similar)
        // - Other completed states
        return true;
    }
}
