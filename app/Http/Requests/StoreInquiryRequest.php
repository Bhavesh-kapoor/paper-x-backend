<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return ($user->brand || $user->converter) && !$user->dealer;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'nullable|exists:materials,id',
            'items.*.material_category' => 'required_without:items.*.material_id|string|max:255',
            'items.*.finish_coating' => 'nullable|string|max:255',
            'items.*.thickness_gsm' => 'nullable|numeric|min:0',
            'items.*.thickness_mm' => 'nullable|numeric|min:0',
            'items.*.thickness_unit' => 'required|in:gsm,mm',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.quantity_unit' => 'required|string|max:50',
            'items.*.additional_specs' => 'nullable|array',
            'urgency' => 'nullable|in:normal,urgent',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'timeline' => 'nullable|string|max:255',
            'deadline' => 'nullable|date|after:now',
        ];
    }
}
