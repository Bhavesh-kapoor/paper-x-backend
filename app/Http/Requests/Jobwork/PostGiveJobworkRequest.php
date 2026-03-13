<?php

namespace App\Http\Requests\Jobwork;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class PostGiveJobworkRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'jobwork_type' => [
                'required',
                'string',
                'max:255',
            ],
            'raw_materials' => [
                'nullable',
                'string',
                'max:500',
            ],
            'size' => [
                'nullable',
                'string',
                'max:100',
            ],
            'size_unit' => [
                'nullable',
                'string',
                'max:20',
            ],
            'thickness' => [
                'nullable',
                'string',
                'max:100',
            ],
            'thickness_unit' => [
                'nullable',
                'string',
                'max:20',
            ],
            'grade_finish' => [
                'nullable',
                'string',
                'max:500',
            ],
            'quantity' => [
                'required',
                'numeric',
                'min:1',
            ],
            'quality_requirements' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'timeline' => [
                'required',
                'string',
                Rule::in(['Normal', 'Urgent']),
            ],
            'delivery_location' => [
                'required',
                'string',
                'max:500',
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
            'other_instructions' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}

