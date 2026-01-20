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
        return ($user->brand || $user->converter) && !$user->dealer;
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
     * Determine whether the user can view responses.
     */
    public function viewResponses(User $user, Inquiry $inquiry): bool
    {
        // Only poster (brand/converter) can view responses
        if ($user->brand && $inquiry->poster_type === 'brand' && $inquiry->poster_id === $user->brand->id) {
            return true;
        }
        
        if ($user->converter && $inquiry->poster_type === 'converter' && $inquiry->poster_id === $user->converter->id) {
            return true;
        }
        
        return false;
    }

    /**
     * Determine whether the user can republish the inquiry.
     */
    public function republish(User $user, Inquiry $inquiry): bool
    {
        // Only poster can republish, and only if cooldown expired
        if (!$inquiry->canRepublish()->where('id', $inquiry->id)->exists()) {
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
}
