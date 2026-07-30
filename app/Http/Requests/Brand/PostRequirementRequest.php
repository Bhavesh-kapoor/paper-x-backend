<?php

namespace App\Http\Requests\Brand;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class PostRequirementRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            // Requirement Type
            'requirement_type' => [
                'required',
                'string',
                Rule::in([
                    'Packaging',
                    'Printing',
                    'Packaging + Printing',
                    'Corporate Gifting / Stationery',
                ]),
            ],
            
            // Packaging Type (required for every requirement type)
            'packaging_type' => [
                'required',
                'string',
                Rule::in(['Boxes', 'Bags', 'Pouches', 'Cartons', 'Containers', 'Other']),
            ],
            
            // Quantity Range (in pieces)
            'quantity_range' => [
                'required', 
                'string', 
                'max:100'
            ], // e.g., "100-500", "500-1000", "1000-5000", "5000-10000", "10000-50000", "50000+"
            
            // Timeline (optional — defaults to Normal in service layer)
            'timeline' => [
                'nullable',
                'string',
                Rule::in(['Urgent 1-2 Days', 'Normal 3-5 Days'])
            ],
            
            // Description (required)
            'description' => [
                'required', 
                'string', 
                'max:2000'
            ],
            
            // Location (required)
            'location' => [
                'required', 
                'string', 
                'max:500'
            ],
            'city' => [
                'nullable', 
                'string', 
                'max:100'
            ],
            'latitude' => [
                'required', 
                'numeric', 
                'between:-90,90'
            ],
            'longitude' => [
                'required', 
                'numeric', 
                'between:-180,180'
            ],
        ];
    }
}
