<?php

namespace App\Services;

use App\Models\User;
use App\Services\OtpService;

class AuthService
{
    public function __construct(
        protected OtpService $otpService
    ) {
    }

    public function loginWithOtp(string $mobile, ?string $otp = null)
    {
        try {


            $user = User::firstOrCreate(
                ['mobile' => $mobile]
            );

            if (!$otp) {
                $this->otpService->generateOtp($user);

                return [
                    'type' => 'otp_sent',
                    'message' => 'OTP sent successfully'
                ];
            }

            if (!$this->otpService->verifyOtp($user, $otp)) {
                throw new \Exception('Invalid OTP', 422);
            }
            $user->tokens()->delete();

            $token = $user->createToken('paper-x')->plainTextToken;

            return [
                'type' => 'login_success',
                'token' => $token,
                'user' => $user
            ];
        } catch (\Execption $e) {
            throw $e;
        }
    }

}
