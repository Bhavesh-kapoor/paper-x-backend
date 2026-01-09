<?php

namespace App\Http\Requests\Dealer;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class ProfileCompleteRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'materials' => ['required', 'array', 'min:1'],
            'materials.*.material_id' => ['required', 'exists:materials,id'],
            'materials.*.brand_id' => ['nullable', 'exists:brands,id'],
            'materials.*.agent_type' => ['nullable', Rule::in(['AUTHORIZED_AGENT', 'DEALER']), Rule::requiredIf(function () {
                return request()->input('materials.*.brand_id') !== null;
            })],
            'materials.*.finish_ids' => ['nullable', 'array'],
            'materials.*.finish_ids.*' => ['exists:material_finishes,id'],
            'materials.*.thickness_ranges' => ['required', 'array', 'min:1'],
            'materials.*.thickness_ranges.*.unit' => ['required', Rule::in(['GSM', 'MM', 'OUNCE', 'BF', 'MICRON'])],
            'materials.*.thickness_ranges.*.min' => ['required', 'numeric', 'min:0'],
            'materials.*.thickness_ranges.*.max' => ['required', 'numeric', 'gte:materials.*.thickness_ranges.*.min'],
            
            'machines_available' => ['nullable', 'array'],
            'machines_available.*' => ['required', 'exists:machines,id'],
            
            'capacity_daily' => ['required', 'numeric', 'min:0'],
            'capacity_monthly' => ['required', 'numeric', 'min:0'],
            'capacity_unit' => ['required', 'string', 'max:50'],
            
            'has_warehouse' => ['required', 'boolean'],
            'bulk_orders_note' => ['nullable', 'string', 'max:500'],
            'locations' => ['required_if:has_warehouse,true', 'array'],
            'locations.*.type' => ['required', Rule::in(['factory', 'warehouse'])],
            'locations.*.address' => ['nullable', 'string'],
            'locations.*.latitude' => ['required', 'numeric', 'between:-90,90'],
            'locations.*.longitude' => ['required', 'numeric', 'between:-180,180'],
            'locations.*.city' => ['nullable', 'string'],
            'locations.*.state' => ['nullable', 'string'],
        ];
    }
}
