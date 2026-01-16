<?php

namespace App\Http\Requests\Dealer;

use App\Http\Requests\ApiRequest;
use App\Enums\InquiryType;
use App\Enums\InquiryIntent;
use Illuminate\Validation\Rule;

class PostRequirementRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'inquiry_type' => ['required', 'string', Rule::in(['material', 'machine', 'job'])],
            'intent' => ['required', 'string', Rule::in(['buy', 'sell'])],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'urgency' => ['required', 'string', Rule::in(['normal', 'urgent'])],
            
            // Material inquiry fields
            'material_ids' => ['required_if:inquiry_type,material', 'array', 'min:1'],
            'material_ids.*' => ['required', 'integer', 'exists:materials,id'],
            'thickness' => ['nullable', 'numeric', 'min:0'],
            'thickness_unit' => ['nullable', 'string', Rule::in(['GSM', 'MM', 'OUNCE', 'BF', 'MICRON'])],
            'size' => ['nullable', 'string', 'max:100'], // e.g., 28x40
            'quantity' => ['required', 'numeric', 'min:0'],
            'quantity_unit' => ['required', 'string', 'max:50'], // kg, tons, sheets, etc
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_unit' => ['nullable', 'string', 'max:50'], // per_sheet, per_kg, etc
            'price_negotiable' => ['nullable', 'boolean'],
            'approx_price_note' => ['nullable', 'string', 'max:500'],
            
            // Machine inquiry fields
            'machine_ids' => ['required_if:inquiry_type,machine', 'array', 'min:1'],
            'machine_ids.*' => ['required', 'integer', 'exists:machines,id'],
            'machine_condition' => ['nullable', 'string', Rule::in(['Brand New', 'Excellent', 'Working Condition', 'Needs Repair'])],
            
            // Job inquiry fields
            'job_type' => ['required_if:inquiry_type,job', 'string', 'max:255'],
            'timeline_days' => ['nullable', 'integer', 'min:1'],
            
            // Location fields
            'location' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            
            // Additional fields
            'specs' => ['nullable', 'array'],
            'attachment_paths' => ['nullable', 'array'],
            'attachment_paths.*' => ['string', 'max:500'],
            'deadline' => ['nullable', 'date'],
        ];
    }
}

