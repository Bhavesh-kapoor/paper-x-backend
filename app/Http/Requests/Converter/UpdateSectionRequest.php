<?php

namespace App\Http\Requests\Converter;

use App\Http\Requests\ApiRequest;

/**
 * Partial per-section update from the Registration Details editor.
 * Every field is `sometimes` so any subset (one section) validates.
 */
class UpdateSectionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            // Company overview (common users-table fields)
            'company_name'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'gst_in'         => ['sometimes', 'nullable', 'string', 'max:20'],
            'city'           => ['sometimes', 'nullable', 'string', 'max:100'],
            'state'          => ['sometimes', 'nullable', 'string', 'max:100'],
            'operation_area' => ['sometimes', 'nullable', 'string', 'max:50'],

            // Multi-select lists
            'converter_type_ids'    => ['sometimes', 'array'],
            'converter_type_ids.*'  => ['integer'],
            'converter_type_custom' => ['sometimes', 'nullable', 'string', 'max:255'],
            'finished_product_ids'    => ['sometimes', 'array'],
            'finished_product_ids.*'  => ['integer'],
            'machine_ids'    => ['sometimes', 'array'],
            'machine_ids.*'  => ['integer'],
            'scrap_type_ids'    => ['sometimes', 'array'],
            'scrap_type_ids.*'  => ['integer'],
            'raw_material_ids'    => ['sometimes', 'array'],
            'raw_material_ids.*'  => ['integer'],

            // Capacity
            'capacity_daily'   => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'capacity_monthly' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'capacity_unit'    => ['sometimes', 'nullable', 'string', 'max:20'],

            // Factory location
            'factory_address'   => ['sometimes', 'nullable', 'string', 'max:500'],
            'factory_city'      => ['sometimes', 'nullable', 'string', 'max:100'],
            'factory_state'     => ['sometimes', 'nullable', 'string', 'max:100'],
            'factory_latitude'  => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'factory_longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
