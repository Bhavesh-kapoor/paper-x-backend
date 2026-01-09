<?php

namespace App\Http\Requests\Chat;

use App\Http\Requests\ApiRequest;

class SendMessageRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'message' => ['required_without:attachment', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'max:10240'], // 10MB max
        ];
    }
}




