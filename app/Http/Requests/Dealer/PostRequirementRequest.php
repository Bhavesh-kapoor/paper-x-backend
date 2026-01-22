<?php

namespace App\Http\Requests\Dealer;

use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class PostRequirementRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            // Required: Inquiry Type & Intent (material with buy or sell intent for dealer requirements)
            'inquiry_type' => ['required', 'string', Rule::in(['material'])],
            'intent' => ['required', 'string', Rule::in(['buy', 'sell'])],
            
            // Required: Material Selection
            'material_id' => [
                'required',
                'integer',
                'exists:materials,id'
            ],
            
            // Required: Thickness
            'thickness' => [
                'required',
                'numeric',
                'min:0.01' // Must be greater than 0
            ],
            'thickness_unit' => [
                'required',
                'string',
                Rule::in(['GSM', 'MM', 'OUNCE', 'BF', 'MICRON'])
            ],
            
            // Required: Size
            'size' => [
                'required',
                'string',
                'max:100',
                'regex:/^\d+(\.\d+)?x\d+(\.\d+)?$/' // Format: WidthxHeight (e.g., "28x40" or "20.5x30")
            ],
            'size_unit' => [
                'required',
                'string',
                Rule::in(['inches', 'cm', 'mm'])
            ],
            
            // Optional: Grade/Finish/Variant
            'finish_ids' => [
                'nullable',
                'array',
                'min:1'
            ],
            'finish_ids.*' => [
                'required',
                'integer',
                'exists:material_finishes,id'
            ],
            
            // Required: Quantity
            'quantity' => [
                'required',
                'numeric',
                'min:0.01' // Must be greater than 0
            ],
            'quantity_unit' => [
                'required',
                'string',
                Rule::in(["kg's", 'tonnes', 'sheets', 'reels', 'reams', 'rolls', 'bundles'])
            ],
            
            // Required: Urgency/Timeline
            'urgency' => [
                'required',
                'string',
                Rule::in(['normal', 'urgent'])
            ],
            
            // Required: Visibility (UPDATED - removed 'manufacturers')
            'visibility' => [
                'required',
                'string',
                Rule::in(['dealers', 'converters', 'all']) // Removed 'manufacturers'
            ],
            
            // Required: Location
            'location_source' => [
                'required',
                'string',
                Rule::in(['saved', 'manual'])
            ],
            'location' => [
                'required',
                'string',
                'max:500'
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
            
            // Optional: Location ID (if saved location)
            'location_id' => [
                'nullable',
                'integer',
                'exists:dealer_locations,id'
            ],
        ];
    }
    
    public function messages(): array
    {
        return [
            'material_id.required' => 'Please select a material',
            'material_id.exists' => 'Selected material does not exist',
            'thickness.required' => 'Please enter thickness',
            'thickness.min' => 'Thickness must be greater than 0',
            'thickness_unit.required' => 'Please select thickness unit',
            'size.required' => 'Please enter size',
            'size.regex' => 'Size must be in format WidthxHeight (e.g., 28x40)',
            'size_unit.required' => 'Please select size unit',
            'quantity.required' => 'Please enter quantity',
            'quantity.min' => 'Quantity must be greater than 0',
            'quantity_unit.required' => 'Please select quantity unit',
            'urgency.required' => 'Please select timeline',
            'urgency.in' => 'Urgency must be "normal" or "urgent" (not "Normal 3-5 Days" or "Urgent 1-2 Days")',
            'visibility.required' => 'Please select visibility',
            'visibility.in' => 'Visibility must be one of: dealers, converters, all',
            'location.required' => 'Please select a delivery location',
            'latitude.required' => 'Location coordinates are required',
            'longitude.required' => 'Location coordinates are required',
            'finish_ids.*.exists' => 'One or more selected finishes do not exist',
        ];
    }
    
    /**
     * Custom validation: Ensure location_id belongs to authenticated user if provided
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = auth()->user();
            
            if (!$user) {
                return;
            }
            
            if ($this->location_source === 'saved' && $this->location_id) {
                // Load dealer relationship if not already loaded
                $dealer = $user->dealer;
                
                if (!$dealer) {
                    $validator->errors()->add(
                        'location_id',
                        'Dealer profile not found. Please complete your dealer profile first.'
                    );
                    return;
                }
                
                // Check if location belongs to this dealer
                $locationExists = $dealer->locations()
                    ->where('id', $this->location_id)
                    ->exists();
                
                if (!$locationExists) {
                    // Get dealer's location IDs for better error message
                    $dealerLocationIds = $dealer->locations()->pluck('id')->toArray();
                    
                    // Log for debugging
                    \Log::warning('Location validation failed', [
                        'user_id' => $user->id,
                        'dealer_id' => $dealer->id,
                        'requested_location_id' => $this->location_id,
                        'available_location_ids' => $dealerLocationIds,
                    ]);
                    
                    $validator->errors()->add(
                        'location_id',
                        "Selected location (ID: {$this->location_id}) does not belong to your dealer account (Dealer ID: {$dealer->id}). Your available location IDs are: " . implode(', ', $dealerLocationIds)
                    );
                }
            }
            
            // If location_source is 'manual', location_id should be null
            if ($this->location_source === 'manual' && $this->location_id) {
                $validator->errors()->add(
                    'location_id',
                    'Location ID should be null when location source is manual'
                );
            }
        });
    }
}

