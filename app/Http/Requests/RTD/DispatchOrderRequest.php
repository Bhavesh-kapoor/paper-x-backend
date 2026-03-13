<?php

namespace App\Http\Requests\RTD;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class DispatchOrderRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowedCouriers = config('rtd.allowed_couriers', []);
        $proofTypes = [
            'courier_receipt',
            'lr_copy',
            'eway_bill',
            'transport_challan',
            'invoice_copy',
        ];

        return [
            'courier_name'    => ['required', 'string', 'max:100', Rule::in($allowedCouriers)],
            'tracking_number' => ['required', 'string', 'max:100', 'regex:' . config('rtd.tracking_pattern', '/^[A-Za-z0-9\s\-]{5,100}$/')],
            'dispatch_date'   => ['required', 'date', 'before_or_equal:today'],
            'proof_type'      => ['required', 'string', Rule::in($proofTypes)],
            'file_path'       => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'tracking_number.regex' => 'Tracking/LR number must be 5–100 characters (letters, numbers, spaces, hyphens only).',
            'courier_name.in'       => 'Please select a valid courier/transporter from the list.',
        ];
    }
}
