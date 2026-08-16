<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;

class OtpLoginRequest extends ApiRequest
{

    public function rules(): array
    {
        return [
            // Indian mobile: 10 digits, must start with 6, 7, 8, or 9
            'mobile' => ['required', 'digits:10', 'regex:/^[6-9][0-9]{9}$/'],
            'otp' => [
                request()->routeIs('auth.otp.verify') ? 'required' : 'nullable',
                'digits:6'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => 'Enter a valid 10-digit Indian mobile number (starting 6-9).',
        ];
    }
}
