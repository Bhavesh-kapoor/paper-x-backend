<?php

namespace App\Services;

use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\DB;
use App\Models\PreRegistration;

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

            // Seed profile from pending pre-registration lead if applicable
            if (!$user->primary_role || !$user->company_name) {
                $normalizedMobile = preg_replace('/\D+/', '', $user->mobile ?? '');
                if (strlen($normalizedMobile) === 10) {
                    $mobileForLookup = $normalizedMobile;
                } else {
                    $mobileForLookup = $user->mobile;
                }

                $lead = PreRegistration::where('mobile', $mobileForLookup)
                    ->whereNull('consumed_at')
                    ->whereNull('ignored_at')
                    ->latest('created_at')
                    ->first();

                if ($lead) {
                    $dirty = false;
                    if (!$user->primary_role && $lead->primary_role) {
                        $user->primary_role = $lead->primary_role;
                        $dirty = true;
                    }
                    if (!$user->company_name && $lead->company_name) {
                        $user->company_name = $lead->company_name;
                        $dirty = true;
                    }
                    if ($dirty) {
                        $user->save();
                    }

                    $lead->user_id = $user->id;
                    $lead->consumed_at = now();
                    $lead->save();
                }
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
