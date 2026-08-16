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

    /**
     * Map of demo/review mobile numbers => fixed OTPs (config/demo.php).
     */
    protected function demoAccounts(): array
    {
        return (array) config('demo.otp_accounts', []);
    }

    /**
     * The fixed OTP for a demo/review number, or null if it isn't one.
     */
    protected function demoOtpFor(?string $mobile): ?string
    {
        if ($mobile === null) {
            return null;
        }

        return $this->demoAccounts()[$mobile] ?? null;
    }

    public function generateOtp($user)
    {
        try {
            $demoOtp = $this->demoOtpFor($user->mobile);
            $isDemo  = $demoOtp !== null;

            $otpCode = $isDemo
                ? $demoOtp
                : (config('app.env') === 'local' ? '123456' : (string) random_int(100000, 999999));

            $otp = Otp::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'otp' => $otpCode,
                    'type' => 'login',
                    // Demo accounts never expire in practice so reviewers can log in anytime.
                    'expires_at' => $isDemo ? now()->addYear() : now()->addMinutes(5),
                    'attempt' => 0,
                ]
            );

            // Never send a real SMS for demo/review numbers (or local env).
            if (!$isDemo && config('app.env') !== 'local' && $user->mobile) {
                $this->msg91Service->sendOtp($user->mobile, $otpCode);
            } else {
                Log::info('OTP generated without SMS send', [
                    'user_id' => $user->id,
                    'env' => config('app.env'),
                    'is_demo' => $isDemo,
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
        // Demo/review accounts: the mapped OTP always works (no SMS, no expiry).
        $demoOtp = $this->demoOtpFor($user->mobile);
        if ($demoOtp !== null && hash_equals((string) $demoOtp, (string) $inputOtp)) {
            Log::info('Demo OTP accepted', ['user_id' => $user->id]);
            // Clean up any stored OTP row so it doesn't linger.
            Otp::where('user_id', $user->id)->where('type', 'login')->delete();
            return true;
        }

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