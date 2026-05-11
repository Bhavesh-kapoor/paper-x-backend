<?php

namespace App\Http\Requests\Wallet;

use Illuminate\Foundation\Http\FormRequest;

class CreateRazorpayExactCreditsOrderRequest extends FormRequest
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
        $max = (int) config('wallet_razorpay.exact_credits_max', 500);

        return [
            'credits' => 'required|integer|min:1|max:'.$max,
        ];
    }

    public function messages(): array
    {
        return [
            'credits.required' => 'Credits amount is required.',
            'credits.integer' => 'Credits must be a whole number.',
            'credits.min' => 'Credits must be at least 1.',
            'credits.max' => 'Credits exceeds the maximum allowed for a single payment.',
        ];
    }
}
