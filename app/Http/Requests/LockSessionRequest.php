<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LockSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via Gate
    }

    public function rules(): array
    {
        return [
            'selected_dealer_ids' => 'sometimes|array',
            'selected_dealer_ids.*' => 'integer|exists:dealers,id',
            'selected_converter_ids' => 'sometimes|array',
            'selected_converter_ids.*' => 'integer|exists:converters,id',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $data = $this->all();
            $dealerIds = $data['selected_dealer_ids'] ?? [];
            $converterIds = $data['selected_converter_ids'] ?? [];

            $hasDealers = is_array($dealerIds) && count($dealerIds) > 0;
            $hasConverters = is_array($converterIds) && count($converterIds) > 0;

            if (!$hasDealers && !$hasConverters) {
                $v->errors()->add('selected_dealer_ids', 'Please select at least one responder.');
            }
        });
    }
}
