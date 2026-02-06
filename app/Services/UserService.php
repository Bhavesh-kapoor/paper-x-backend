<?php

namespace App\Services;
use Illuminate\Support\Facades\File;

class UserService
{

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
        $user->loadMissing(['dealer', 'converter', 'brand', 'machineDealer']);

        $primaryRole = $user->primary_role;
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

            case 'machineDealer':
            case 'machine-dealer':
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

        return $data;
    }
}



?>