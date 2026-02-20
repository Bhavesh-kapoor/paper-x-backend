<?php

namespace App\Http\Requests\RTD;

use App\Http\Requests\ApiRequest;

class DispatchOrderRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'proof_type'      => ['required', 'string', 'in:tracking_number,lr_photo,delivery_challan'],
            'file'            => ['required_unless:proof_type,tracking_number', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'tracking_number' => ['required_if:proof_type,tracking_number', 'string', 'max:255'],
        ];
    }
}
