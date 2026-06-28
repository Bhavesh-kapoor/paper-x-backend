<?php

namespace App\Http\Requests\RTD;

use App\Http\Requests\ApiRequest;

class CreateOrderRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id'       => ['required', 'integer', 'exists:rtd_products,id'],
            'quantity'         => ['required', 'integer', 'min:1'],
            'delivery_address' => ['nullable', 'string', 'max:1000'],
            'order_notes'      => ['nullable', 'string', 'max:2000'],
            'logo'             => ['nullable', 'file', 'mimes:png,jpg,jpeg,pdf,ai,svg', 'max:10240'],
        ];
    }
}
