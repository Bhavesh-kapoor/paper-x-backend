<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveInquiryStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return ($user->brand || $user->converter) && !$user->dealer;
    }

    public function rules(): array
    {
        $step = $this->input('step', 1);
        
        $rules = [
            'step' => 'required|integer|min:1|max:9',
            'inquiry_id' => 'nullable|exists:inquiries,id',
        ];
        
        // Step 2: Technical Specifications
        if ($step == 2) {
            $rules['material_category'] = 'required|string|max:255';
            $rules['packaging_type'] = 'nullable|string|max:255';
            $rules['thickness_gsm'] = 'nullable|numeric|min:0';
            $rules['thickness_mm'] = 'nullable|numeric|min:0';
            $rules['thickness_unit'] = 'nullable|in:gsm,mm';
            $rules['size'] = 'nullable|string|max:255';
            $rules['quantity'] = 'required|numeric|min:1';
            $rules['quantity_unit'] = 'required|string|max:50';
            $rules['urgency'] = 'nullable|in:normal,urgent';
            $rules['location'] = 'nullable|string|max:255';
            $rules['latitude'] = 'nullable|numeric|between:-90,90';
            $rules['longitude'] = 'nullable|numeric|between:-180,180';
        }
        
        return $rules;
    }
}
