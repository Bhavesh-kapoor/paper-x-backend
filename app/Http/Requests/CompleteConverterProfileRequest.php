<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteConverterProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'converter_type_ids' => ['nullable', 'array'],
            'converter_type_ids.*' => ['exists:converter_types,id'],
            'converter_type_custom' => ['nullable', 'string', 'max:255'],
            'finished_product_ids' => ['nullable', 'array'],
            'finished_product_ids.*' => ['exists:finished_products,id'],
            'machine_ids' => ['nullable', 'array'],
            'machine_ids.*' => ['exists:machines,id'],
            'scrap_type_ids' => ['nullable', 'array'],
            'scrap_type_ids.*' => ['exists:scrap_types,id'],
            'raw_material_ids' => ['nullable', 'array'],
            'raw_material_ids.*' => ['exists:materials,id'],
            'capacity_daily' => ['nullable', 'numeric', 'min:0'],
            'capacity_monthly' => ['nullable', 'numeric', 'min:0'],
            'capacity_unit' => ['nullable', 'string', 'max:50'],
            'factory_address' => ['nullable', 'string'],
            'factory_city' => ['nullable', 'string', 'max:100'],
            'factory_state' => ['nullable', 'string', 'max:100'],
            'factory_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'factory_longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
