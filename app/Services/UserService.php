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
        return request()->user();
    }
}



?>