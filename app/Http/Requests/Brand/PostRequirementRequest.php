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
                Rule::in(['Packaging', 'Printing', 'Labels', 'Other'])
            ],
            
            // Packaging Type (conditional - only if requirement_type is Packaging)
            'packaging_type' => [
                'nullable',
                'required_if:requirement_type,Packaging',
                function ($attribute, $value, $fail) {
                    if ($value !== null && !in_array($value, ['Boxes', 'Bags', 'Pouches', 'Cartons', 'Containers', 'Other'])) {
                        $fail('The ' . $attribute . ' must be one of: Boxes, Bags, Pouches, Cartons, Containers, Other.');
                    }
                },
                'max:255'
            ],
            
            // Quantity Range (in pieces)
            'quantity_range' => [
                'required', 
                'string', 
                'max:100'
            ], // e.g., "100-500", "500-1000", "1000-5000", "5000-10000", "10000-50000", "50000+"
            
            // Timeline
            'timeline' => [
                'required',
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
