<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveInquiryStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        // Allow if user has brand or converter relationship (even if they also have dealer)
        return $user->brand || $user->converter;
    }
    
    /**
     * Get the validation error messages for authorization failures.
     */
    protected function failedAuthorization()
    {
        $user = $this->user();
        throw new \Illuminate\Http\Exceptions\HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Only brands or converters can save inquiry steps. Please complete your brand or converter profile first.',
                'errors' => [
                    'error_code' => 'PROFILE_INCOMPLETE',
                    'user_id' => $user->id ?? null,
                    'user_roles' => [
                        'has_brand' => $user->brand ? true : false,
                        'has_converter' => $user->converter ? true : false,
                        'has_dealer' => $user->dealer ? true : false,
                        'has_machine_dealer' => $user->machineDealer ? true : false,
                    ],
                    'message' => 'You need to complete either a brand profile or converter profile to save inquiry steps.',
                ]
            ], 403)
        );
    }

    public function rules(): array
    {
        $step = $this->input('step', 1);
        
        $rules = [
            'step' => 'required|integer|min:1|max:9',
            'inquiry_id' => 'nullable|exists:inquiries,id',
            'title' => 'nullable|string|max:255', // Optional title for any step
            'description' => 'nullable|string|max:5000', // Optional description for any step
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
