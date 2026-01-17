<?php

namespace App\Http\Requests;

use App\Http\Requests\ApiRequest;

class SubmitResponseRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'quantity_offered' => ['nullable', 'numeric', 'min:0'],
            'quantity_unit' => ['nullable', 'string', 'max:50'],
            'quoted_price' => ['nullable', 'numeric', 'min:0'],
            'price_unit' => ['nullable', 'string', 'max:50'],
            'price_status' => ['nullable', 'string', 'max:100'],
            'additional_details' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
