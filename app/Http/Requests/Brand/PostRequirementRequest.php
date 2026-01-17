<?php

namespace App\Http\Requests\Brand;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class PostRequirementRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            // Step 1: What do you need?
            'requirement_type' => [
                'required', 
                'string', 
                Rule::in(['Packaging', 'Printing', 'Packaging + Printing', 'Corporate Gifting / Stationery'])
            ],
            
            // Step 2: Packaging Type (conditional - only if requirement_type includes Packaging)
            'packaging_type' => [
                'nullable',
                'required_if:requirement_type,Packaging',
                'required_if:requirement_type,Packaging + Printing',
                'string',
                'max:255'
            ],
            
            // Step 3: Quantity Range (in pieces)
            'quantity_range' => ['required', 'string', 'max:100'], // e.g., "1000-5000", "5000-10000"
            
            // Step 4: Timeline
            'timeline' => [
                'required',
                'string',
                Rule::in(['Emergency (Urgent)', '3–5 Days', 'Flexible'])
            ],
            
            // Step 5: Special Needs (optional text box)
            'special_needs' => ['nullable', 'string', 'max:2000'],
            
            // Step 6: Upload photos/videos/design ideas (all optional)
            'design_attachments' => ['nullable', 'array'],
            'design_attachments.*' => ['string', 'max:500'], // File paths
            
            // Additional fields for inquiry
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'urgency' => ['required', 'string', Rule::in(['normal', 'urgent'])],
            
            // Location (optional)
            'location' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
