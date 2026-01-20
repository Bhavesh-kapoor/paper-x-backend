<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LockSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via Gate
    }

    public function rules(): array
    {
        return [
            'selected_dealer_ids' => 'required|array|min:1',
            'selected_dealer_ids.*' => 'required|exists:dealers,id',
        ];
    }
}
