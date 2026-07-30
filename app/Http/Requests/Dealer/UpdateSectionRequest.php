<?php

namespace App\Http\Requests\Dealer;

use App\Http\Requests\ApiRequest;

/**
 * Partial per-section update from the Registration Details editor (dealer).
 * Phase 1: company (common), capacity, machines.
 */
class UpdateSectionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'company_name'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'gst_in'         => ['sometimes', 'nullable', 'string', 'max:20'],
            'city'           => ['sometimes', 'nullable', 'string', 'max:100'],
            'state'          => ['sometimes', 'nullable', 'string', 'max:100'],
            'operation_area' => ['sometimes', 'nullable', 'string', 'max:50'],

            'capacity_daily'   => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'capacity_monthly' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'capacity_unit'    => ['sometimes', 'nullable', 'string', 'max:20'],

            'machine_ids'   => ['sometimes', 'array'],
            'machine_ids.*' => ['integer'],
        ];
    }
}
