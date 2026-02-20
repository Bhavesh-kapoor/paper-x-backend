<?php

namespace App\Http\Requests\RTD;

use App\Http\Requests\ApiRequest;

class ConfirmPaymentRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id'       => ['required', 'integer', 'exists:rtd_orders,id'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
