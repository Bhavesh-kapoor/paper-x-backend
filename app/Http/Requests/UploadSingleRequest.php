<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UploadSingleRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file'    => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
            'purpose' => ['sometimes', 'string', Rule::in(['product', 'dispatch'])],
        ];
    }
}
