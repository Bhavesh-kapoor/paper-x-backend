<?php

namespace App\Services;

use App\Enums\InquiryStatus;
use App\Enums\SessionStatus;
use App\Models\Inquiry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class UserService
{
    private const SYNTHETIC_LOCATION_IDS = [
        'converter_factory' => -2001,
        'brand_location' => -3001,
        'machine_dealer_location' => -4001,
    ];

    public function updateProfile(array $data)
    {
        try {
            $user = request()->user();

            if (isset($data['udyam_certificate']) && $data['udyam_certificate'] instanceof \Illuminate\Http\UploadedFile) {
                // Delete old certificate if exists
                if ($user->udyam_certificate && File::exists(public_path($user->udyam_certificate))) {
                    File::delete(public_path($user->udyam_certificate));
                }
                
                $file = $data['udyam_certificate'];
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('certificates'), $filename);
                
                $data['udyam_certificate'] = 'certificates/' . $filename;
                $data['udyam_verified_at'] = now();
            }

            if (isset($data['avatar']) && $data['avatar'] instanceof \Illuminate\Http\UploadedFile) {
                // Delete old avatar if exists
                if ($user->avatar && File::exists(public_path($user->avatar))) {
                    File::delete(public_path($user->avatar));
                }
                
                $file = $data['avatar'];
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('avatars'), $filename);
                
                $data['avatar'] = 'avatars/' . $filename;
            }

            $user->update($data);

            return $user->refresh();
        } catch (\Exception $e) {
            throw $e;
        }

    }

    /**
     * Permanently delete the authenticated user's account (Apple 5.1.1(v)).
     *
     * Strategy: anonymize + delete. We scrub all personal data, null the unique
     * mobile/email (which frees the number for future re-registration and makes
     * the account unreachable via OTP login), revoke all access, and mark the row
     * deleted. Role-profile rows are kept (PII scrubbed) so historical inquiries
     * that reference them polymorphically don't break.
     */
    public function deleteAccount(): void
    {
        $user = request()->user();

        if (! $user) {
            throw new \RuntimeException('Not authenticated');
        }

        DB::transaction(function () use ($user) {
            $user->loadMissing(['dealer.locations', 'converter', 'brand', 'machineDealer']);

            // 1) Best-effort: expire this user's active listings so they stop
            //    matching others. Never let a listing-expiry hiccup (e.g. a DB
            //    enum quirk) block the account deletion itself.
            try {
                $this->expireUserListings($user);
            } catch (\Throwable $e) {
                Log::warning('Account deletion: expiring listings failed', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);
            }

            // 2) Scrub role-profile PII (keep the rows for referential integrity).
            if ($user->dealer) {
                // Dealer has no direct PII columns; remove saved warehouse addresses.
                $user->dealer->locations()->delete();
            }
            if ($user->converter) {
                $user->converter->update([
                    'factory_address'   => null,
                    'factory_city'      => null,
                    'factory_state'     => null,
                    'factory_latitude'  => null,
                    'factory_longitude' => null,
                ]);
            }
            if ($user->brand) {
                $user->brand->update([
                    'company_name'        => null,
                    'brand_name'          => null,
                    'contact_person_name' => null,
                    'mobile'              => null,
                    'email'               => null,
                    'gst'                 => null,
                    'state'               => null,
                    'city'                => null,
                    'address'             => null,
                    'location'            => null,
                    'latitude'            => null,
                    'longitude'           => null,
                ]);
            }
            if ($user->machineDealer) {
                $user->machineDealer->update([
                    'company_name'         => null,
                    'gst'                  => null,
                    'contact_person_name'  => null,
                    'mobile'               => null,
                    'email'                => null,
                    'city'                 => null,
                    'location'             => null,
                    'latitude'             => null,
                    'longitude'            => null,
                    'preferred_brand_names' => null,
                    'machine_preferences'  => null,
                ]);
            }

            // 3) Remove uploaded personal files (best-effort).
            foreach (['udyam_certificate', 'avatar'] as $fileCol) {
                if ($user->{$fileCol} && File::exists(public_path($user->{$fileCol}))) {
                    File::delete(public_path($user->{$fileCol}));
                }
            }

            // 4) Stop push notifications for this account.
            $user->deviceTokens()->delete();

            // 5) Revoke all API access (Sanctum personal access tokens).
            $user->tokens()->delete();

            // 6) Scrub the base user row + mark it deleted. forceFill bypasses
            //    $fillable so we can set deleted_at directly.
            $user->forceFill([
                'name'               => 'Deleted User',
                'mobile'             => null,
                'email'              => null,
                'company_name'       => null,
                'gst_in'             => null,
                'avatar'             => null,
                'udyam_certificate'  => null,
                'udyam_verified_at'  => null,
                'operation_area'     => null,
                'state'              => null,
                'city'               => null,
                'deleted_at'         => now(),
            ])->save();
        });
    }

    /**
     * Set every non-terminal inquiry (and its session) posted by this user to
     * EXPIRED, so a deleted user no longer appears as a live match/opportunity.
     */
    private function expireUserListings($user): void
    {
        $posters = [];
        if ($user->dealer)        { $posters[] = ['dealer', $user->dealer->id]; }
        if ($user->converter)     { $posters[] = ['converter', $user->converter->id]; }
        if ($user->brand)         { $posters[] = ['brand', $user->brand->id]; }
        if ($user->machineDealer) { $posters[] = ['machine_dealer', $user->machineDealer->id]; }

        if (empty($posters)) {
            return;
        }

        $terminal = [
            InquiryStatus::DEAL_SUCCESS,
            InquiryStatus::DEAL_FAILED,
            InquiryStatus::DEAL_WON,
            InquiryStatus::DEAL_LOST,
            InquiryStatus::EXPIRED,
            InquiryStatus::SESSION_EXPIRED,
            InquiryStatus::BRAND_CANCELLED,
        ];

        $inquiries = Inquiry::query()
            ->where(function ($q) use ($posters) {
                foreach ($posters as [$type, $id]) {
                    $q->orWhere(function ($sub) use ($type, $id) {
                        $sub->where('poster_type', $type)->where('poster_id', $id);
                    });
                }
            })
            ->whereNotIn('status', $terminal)
            ->with('session')
            ->get();

        foreach ($inquiries as $inquiry) {
            // NOTE: the inquiries DB enum uses SESSION_EXPIRED (it has no EXPIRED
            // value); matching_sessions does have EXPIRED.
            $inquiry->update(['status' => InquiryStatus::SESSION_EXPIRED]);
            if ($inquiry->session) {
                $inquiry->session->update(['status' => SessionStatus::EXPIRED]);
            }
        }
    }

    public function getProfile()
    {
        $user = request()->user();

        // Determine whether the user has completed their registration.
        //
        // IMPORTANT:
        // - "Registration complete" should mean the role-specific profile is done
        //   (dealer/converter/brand/machineDealer profile_complete = true),
        //   NOT just that a company_name exists on the base user record.
        // - This flag is used by the mobile app to decide whether to send
        //   the user back into the registration flow or to the dashboard.

        // Eager-load role relations to avoid N+1 and ensure we have the latest data.
        $user->loadMissing(['dealer.locations', 'converter', 'brand', 'machineDealer']);

        $primaryRole = $this->normalizePrimaryRole($user->primary_role);
        $hasCompletedRegistration = false;

        switch ($primaryRole) {
            case 'dealer':
                // Dealer registration is complete when dealer.profile_complete is true
                $hasCompletedRegistration = (bool) optional($user->dealer)->profile_complete;
                break;

            case 'converter':
                // Converter registration is complete when converter.profile_complete is true
                $hasCompletedRegistration = (bool) optional($user->converter)->profile_complete;
                break;

            case 'brand':
                // Brand registration is complete when brand.profile_complete is true
                $hasCompletedRegistration = (bool) optional($user->brand)->profile_complete;
                break;

            case 'machine_dealer':
                // Machine dealer registration is complete when machine_dealers.profile_complete is true
                $hasCompletedRegistration = (bool) optional($user->machineDealer)->profile_complete;
                break;

            default:
                // Fallback for roles that don't yet have a separate profile model:
                // treat a non-empty company_name as "basic registration complete".
                $hasCompletedRegistration = !empty($user->company_name);
                break;
        }

        // Return the full user payload plus the backend-driven completion flag.
        // This is what the mobile app reads as data.has_completed_registration.
        $data = $user->toArray();
        $data['has_completed_registration'] = $hasCompletedRegistration;
        $data['posting_locations'] = $this->buildPostingLocations($user);

        return $data;
    }

    private function normalizePrimaryRole(?string $role): ?string
    {
        if (!$role) {
            return null;
        }

        $normalized = strtolower(trim($role));
        $normalized = str_replace('-', '_', $normalized);

        if ($normalized === 'machinedealer') {
            return 'machine_dealer';
        }

        return $normalized;
    }

    private function buildPostingLocations($user): array
    {
        $locations = [];

        $dealer = $user->dealer;
        if ($dealer && $dealer->relationLoaded('locations')) {
            foreach ($dealer->locations as $location) {
                $locations[] = [
                    'id' => (int) $location->id,
                    'source' => 'dealer_saved',
                    'label' => $location->address ?: trim(($location->city ?? '') . ($location->state ? ', ' . $location->state : '')),
                    'address' => $location->address,
                    'city' => $location->city,
                    'state' => $location->state,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                ];
            }
        }

        $converter = $user->converter;
        if ($converter && !is_null($converter->factory_latitude) && !is_null($converter->factory_longitude)) {
            $locations[] = [
                'id' => self::SYNTHETIC_LOCATION_IDS['converter_factory'],
                'source' => 'converter_factory',
                'label' => $converter->factory_address ?: trim(($converter->factory_city ?? '') . ($converter->factory_state ? ', ' . $converter->factory_state : '')),
                'address' => $converter->factory_address,
                'city' => $converter->factory_city,
                'state' => $converter->factory_state,
                'latitude' => $converter->factory_latitude,
                'longitude' => $converter->factory_longitude,
            ];
        }

        $brand = $user->brand;
        if ($brand && !is_null($brand->latitude) && !is_null($brand->longitude)) {
            $locations[] = [
                'id' => self::SYNTHETIC_LOCATION_IDS['brand_location'],
                'source' => 'brand_location',
                'label' => $brand->location ?: trim(($brand->city ?? '') . ($brand->state ? ', ' . $brand->state : '')),
                'address' => $brand->location,
                'city' => $brand->city,
                'state' => $brand->state,
                'latitude' => $brand->latitude,
                'longitude' => $brand->longitude,
            ];
        }

        $machineDealer = $user->machineDealer;
        if ($machineDealer && !is_null($machineDealer->latitude) && !is_null($machineDealer->longitude)) {
            $locations[] = [
                'id' => self::SYNTHETIC_LOCATION_IDS['machine_dealer_location'],
                'source' => 'machine_dealer_location',
                'label' => $machineDealer->location ?: $machineDealer->city,
                'address' => $machineDealer->location,
                'city' => $machineDealer->city,
                'state' => null,
                'latitude' => $machineDealer->latitude,
                'longitude' => $machineDealer->longitude,
            ];
        }

        return array_values(array_filter($locations, function ($location) {
            return !is_null($location['latitude']) && !is_null($location['longitude']);
        }));
    }
}



?>