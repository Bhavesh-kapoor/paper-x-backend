<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShortlistResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via Gate
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:shortlist,reject',
        ];
    }
}
