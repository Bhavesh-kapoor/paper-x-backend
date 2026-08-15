<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\User;
use App\Services\Msg91Service;
use Illuminate\Support\Facades\Log;

class OtpService
{
    public function __construct(
        protected Msg91Service $msg91Service
    ) {
    }

    public function generateOtp($user)
    {
        try {
            $otpCode = config('app.env') === 'local' ? '123456' : (string) random_int(100000, 999999);

            $otp = Otp::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'otp' => $otpCode,
                    'type' => 'login',
                    'expires_at' => now()->addMinutes(5),
                    'attempt' => 0,
                ]
            );

            if (config('app.env') !== 'local' && $user->mobile) {
                $this->msg91Service->sendOtp($user->mobile, $otpCode);
            } else {
                Log::info('OTP generated without SMS send (local env or missing mobile)', [
                    'user_id' => $user->id,
                    'env' => config('app.env'),
                    'has_mobile' => (bool) $user->mobile,
                ]);
            }

            return $otp;
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
            Log::warning('OTP verify failed: not found', ['user_id' => $user->id]);
            throw new \Exception('Otp Not Found', 404);

        }

        if ($otp->expires_at->isPast()) {
            Log::warning('OTP verify failed: expired', ['user_id' => $user->id]);
            throw new \Exception('OTP expired', 410);
        }

        if ($otp->attempt >= 3) {
            Log::warning('OTP verify failed: too many attempts', ['user_id' => $user->id]);
            throw new \Exception('Too many attempts', 429);
        }

        if (!hash_equals((string) $otp->otp, (string) $inputOtp)) {
            $otp->increment('attempt');
            Log::warning('OTP verify failed: mismatch', ['user_id' => $user->id, 'attempt' => $otp->attempt]);
            return false;
        }

        Log::info('OTP verify succeeded', ['user_id' => $user->id]);
        $otp->delete(); // one-time use
        return true;
    }

}
?>