<?php

namespace App\Services;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\DB;

class AuthService
{
    public function __construct(
        protected OtpService $otpService
    ) {
    }

    public function loginWithOtp(string $mobile, ?string $otp = null)
    {
        try {
            // Disable morph map enforcement temporarily to prevent errors
            $user = User::withoutGlobalScopes()->firstOrCreate(
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
            
            // Get user ID before any token operations
            $userId = $user->id;
            
            // Delete old tokens using query builder to avoid model serialization
            \DB::table('personal_access_tokens')
                ->where('tokenable_type', 'App\\Models\\User')
                ->where('tokenable_id', $userId)
                ->delete();

            // Create new token using fresh user instance
            $freshUser = User::find($userId);
            $token = $freshUser->createToken('paper-x')->plainTextToken;

            // Return only user data without relationships to avoid morph map issues
            // Get attributes directly from database to avoid any relationship access
            $userAttributes = $user->getAttributes();
            $userArray = [
                'id' => $userAttributes['id'] ?? null,
                'name' => $userAttributes['name'] ?? null,
                'mobile' => $userAttributes['mobile'] ?? null,
                'email' => $userAttributes['email'] ?? null,
                'primary_role' => $userAttributes['primary_role'] ?? null,
                'has_secondary_role' => $userAttributes['has_secondary_role'] ?? false,
                'secondary_role' => $userAttributes['secondary_role'] ?? null,
            ];
            
            return [
                'type' => 'login_success',
                'token' => $token,
                'user' => $userArray
            ];
        } catch (\Exception $e) {
            throw $e;
        }
    }

}
