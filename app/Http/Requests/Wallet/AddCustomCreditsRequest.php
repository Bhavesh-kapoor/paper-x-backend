<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class AddCustomCreditsRequest extends FormRequest
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
            'credits' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'transaction_type' => 'nullable|string|in:PURCHASE,REFERRAL_BONUS,REFUND,ADMIN_ADJUSTMENT,OTHER',
            'reference_id' => 'nullable|string',
            'reference_type' => 'nullable|string',
            'metadata' => 'nullable|array',
        ];
    }
}
