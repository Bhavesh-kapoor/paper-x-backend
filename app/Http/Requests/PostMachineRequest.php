<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostMachineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'machine_id' => ['required', 'exists:machines,id'],
            'machine_brand_id' => ['nullable', 'exists:machine_brands,id'],
            'machine_type' => ['nullable', 'string', 'max:255'],
            'condition' => ['nullable', Rule::in(['Brand New', 'Excellent', 'Working Condition', 'Needs Repair'])],
            'intent' => ['required', Rule::in(['sell', 'buy'])],
            'urgency' => ['required', Rule::in(['normal', 'urgent'])],
            'description' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'machine_price_range' => ['nullable', 'string', 'max:20'],
            'currency' => ['nullable', 'string', 'max:3'],
            'location' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'title' => ['nullable', 'string', 'max:255'],
            'posting_fee_paid' => ['nullable', 'boolean'],
            'posting_fee_amount' => ['nullable', 'numeric', 'min:0'],
            'visibility' => ['nullable', 'string', Rule::in(['dealers', 'converters', 'machine_dealers', 'all'])],
        ];
    }
}
