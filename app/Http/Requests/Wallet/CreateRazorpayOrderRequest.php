<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class CreateRazorpayOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'credit_pack_id' => 'required|exists:credit_packs,id',
        ];
    }

    public function messages(): array
    {
        return [
            'credit_pack_id.required' => 'Credit pack is required.',
            'credit_pack_id.exists' => 'Selected credit pack is invalid.',
        ];
    }
}
