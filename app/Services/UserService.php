<?php

namespace App\Services;
use Illuminate\Support\Facades\File;

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