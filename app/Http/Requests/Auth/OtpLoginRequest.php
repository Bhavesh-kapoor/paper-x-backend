<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiRequest;

class OtpLoginRequest extends ApiRequest
{

    public function rules(): array
    {
        return [
            'mobile' => ['required', 'digits:10'],
            'otp' => [
                request()->routeIs('auth.otp.verify') ? 'required' : 'nullable',
                'digits:6'
            ],
        ];
    }
}
