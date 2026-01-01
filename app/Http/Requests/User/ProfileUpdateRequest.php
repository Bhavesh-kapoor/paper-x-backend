<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $userId = $this->user()->id ?? null;

        return [
            // Basic info
            'name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],
            // 'mobile' => [
            //     'nullable',
            //     'string',
            //     'max:15',
            //     Rule::unique('users')->ignore($userId),
            // ],
            // 'password' => ['nullable', 'string', 'min:6', 'confirmed'],

            // Roles
            'primary_role' => ['nullable', 'string', 'max:50'],
            'secondary_role' => ['nullable', 'string', 'max:50'],
            'has_secondary_role' => ['boolean'],

            // Operation area
            'operation_area' => ['nullable', 'in:local,pan india,state'],

            // Company info
            'company_name' => ['nullable', 'string', 'max:255'],
            'gst_in' => ['nullable', 'string', 'size:15'],

            // State & city
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],

            // Udyam certificate
            'udyam_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'udyam_verified_at' => ['nullable', 'date'],

            // Avatar (optional)
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already taken.',
            // 'mobile.unique' => 'This mobile number is already taken.',
            // 'password.confirmed' => 'Password confirmation does not match.',
            'operation_area.in' => 'Operation area must be one of: local, pan india, state.',
            'state_id.exists' => 'Selected state is invalid.',
            'city_id.exists' => 'Selected city is invalid.',
            'udyam_certificate.mimes' => 'Udyam certificate must be a PDF or image.',
        ];
    }
}
