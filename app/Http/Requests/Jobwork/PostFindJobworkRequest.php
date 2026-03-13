<?php

namespace App\Http\Requests\Jobwork;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class PostFindJobworkRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'jobwork_type' => [
                'required',
                'string',
                'max:255',
            ],
            'machinery_available' => [
                'required',
                'string',
                'max:255',
            ],
            'timeline' => [
                'required',
                'string',
                Rule::in(['Normal', 'Urgent']),
            ],
            'minimum_order_quantity' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'special_instructions' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'sample_available' => [
                'nullable',
                'boolean',
            ],
            'sample_image' => [
                'nullable',
                'string',
                'max:500',
            ],
            'location' => [
                'nullable',
                'string',
                'max:500',
            ],
            'city' => [
                'nullable',
                'string',
                'max:255',
            ],
            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],
        ];
    }
}

