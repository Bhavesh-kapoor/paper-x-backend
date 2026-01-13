<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class SwitchRoleRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'role' => [
                'required',
                'string',
                Rule::in(['DEALER', 'BRAND']), // Add other roles as needed
            ],
        ];
    }
}





