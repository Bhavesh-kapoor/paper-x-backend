<?php

namespace App\Http\Requests\Dealer;

use App\Http\Requests\ApiRequest;

class SubmitQuoteRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'quoted_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'delivery_days' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}





