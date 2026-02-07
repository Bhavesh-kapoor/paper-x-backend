<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\User;
use Exception;

class OtpService
{
    public function generateOtp($user)
    {
        try {
            $otpCode = env('APP_ENV') == 'local' ? 123456 : random_int(100000, 999999);
            return Otp::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'otp' => $otpCode,
                    'type' => 'login',
                    'expires_at' => now()->addMinutes(5),
                    'attempt' => 0,
                ]
            );
        } catch (\Exception $e) {
            throw $e;
        }
    }


    public function verifyOtp(User $user, string $inputOtp): bool
    {
        $otp = Otp::where('user_id', $user->id)
            ->where('type', 'login')
            ->where(function ($q) {
                $q->where('identifier', 'mobile')->orWhereNull('identifier');
            })
            ->first();

        if (!$otp) {
            throw new \Exception('Otp Not Found', 404);

        }

        if ($otp->expires_at->isPast()) {
            throw new \Exception('OTP expired', 410);
        }

        if ($otp->attempt >= 3) {
            throw new \Exception('Too many attempts', 429);
        }

        if (!hash_equals((string) $otp->otp, (string) $inputOtp)) {
            $otp->increment('attempt');
            return false;
        }

        $otp->delete(); // one-time use
        return true;
    }

}
?>