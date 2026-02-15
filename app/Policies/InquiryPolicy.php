<?php

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InquiryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Inquiry $inquiry): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Inquiry $inquiry): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Inquiry $inquiry): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Inquiry $inquiry): bool
    {
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
     * Determine whether the user can force delete the model.
     */
    public function forceDelete(User $user, Inquiry $inquiry): bool
    {
        return false;
    }
}
