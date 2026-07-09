<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;

class DeviceTokenService
{
    /**
     * Register (or refresh) a device token for a user.
     *
     * Uses the token as the natural key: if the same token was previously
     * registered — even under a different user (shared device, account switch,
     * reinstall) — it is reassigned to the current user. This keeps exactly one
     * owner per physical device token and prevents cross-account delivery.
     */
    public function register(User $user, string $token, ?string $platform = null): DeviceToken
    {
        return DeviceToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'platform' => $platform,
                'last_used_at' => now(),
            ]
        );
    }

    /**
     * Remove a single token (e.g. on logout / notifications disabled).
     */
    public function unregister(string $token): void
    {
        DeviceToken::where('token', $token)->delete();
    }

    /**
     * Bulk-remove tokens FCM reported as invalid/unregistered so we stop
     * wasting sends on dead devices.
     *
     * @param  array<int, string>  $tokens
     */
    public function prune(array $tokens): void
    {
        if ($tokens === []) {
            return;
        }

        DeviceToken::whereIn('token', $tokens)->delete();
    }
}
