<?php

namespace App\Http\Requests\Dealer;

use App\Http\Requests\ApiRequest;

class DeclineOpportunityRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}




