<?php

namespace App\Http\Requests\MachineDealer;

use App\Http\Requests\ApiRequest;

/**
 * Partial per-section update from the Registration Details editor (machine dealer).
 * Phase 1: company overview.
 */
class UpdateSectionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'company_name'        => ['sometimes', 'nullable', 'string', 'max:255'],
            'gst_in'              => ['sometimes', 'nullable', 'string', 'max:20'],
            'city'                => ['sometimes', 'nullable', 'string', 'max:100'],
            'state'               => ['sometimes', 'nullable', 'string', 'max:100'],
            'operation_area'      => ['sometimes', 'nullable', 'string', 'max:50'],
            'contact_person_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mobile'              => ['sometimes', 'nullable', 'string', 'max:20'],
            'email'               => ['sometimes', 'nullable', 'email', 'max:255'],
            'location'            => ['sometimes', 'nullable', 'string', 'max:500'],
            'latitude'            => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude'           => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
