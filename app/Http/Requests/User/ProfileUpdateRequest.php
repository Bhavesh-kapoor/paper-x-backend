<?php

namespace App\Http\Requests\User;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends ApiRequest
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
                'nullable',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],
            'mobile' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users')->ignore($userId),
            ],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],

            // Roles
            'primary_role' => ['nullable', 'string', 'max:50'],
            'has_secondary_role' => ['boolean'],
            'secondary_role' => ['nullable', 'string', 'max:50', Rule::requiredIf(fn() => $this->boolean('has_secondary_role') === true)],

            // Operation area
            'operation_area' => ['nullable', 'in:local,pan india,state'],

            // Company info
            'company_name' => ['nullable', 'string', 'max:255'],
            'gst_in' => ['nullable', 'string', 'max:15'],

            // State & city
            'state' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],

            // Udyam certificate
            'udyam_certificate' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],

            // Avatar
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
            'udyam_certificate.mimes' => 'Udyam certificate must be a PDF or image.',
        ];
    }
}
