<?php

namespace App\Http\Requests;

class UploadSingleRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
        ];
    }
}
