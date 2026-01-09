<?php

namespace App\Http\Requests\Dealer;

use App\Http\Requests\ApiRequest;

class AcceptOpportunityRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            // No additional fields required for acceptance
        ];
    }
}




