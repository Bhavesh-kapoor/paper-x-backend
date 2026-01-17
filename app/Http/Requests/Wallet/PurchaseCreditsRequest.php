<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseCreditsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'credit_pack_id' => 'nullable|exists:credit_packs,id',
            'amount' => 'required_without:credit_pack_id|numeric|min:100',
            'gst_percentage' => 'nullable|numeric|min:0|max:100',
            'payment_method' => 'nullable|string|in:UPI,NET_BANKING,CARDS',
        ];
    }

    public function messages(): array
    {
        return [
            'credit_pack_id.exists' => 'Selected credit pack is invalid.',
            'amount.required_without' => 'Amount is required when credit pack is not selected.',
            'amount.min' => 'Minimum amount is ₹100.',
        ];
    }
}
