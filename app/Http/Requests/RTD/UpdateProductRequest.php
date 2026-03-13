<?php

namespace App\Http\Requests\RTD;

use App\Enums\RTDLeadTime;
use App\Http\Requests\ApiRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category'          => ['sometimes', 'string', 'max:255'],
            'product_name'      => ['sometimes', 'string', 'max:255'],
            'image_path'        => ['nullable', 'string'],
            'size'              => ['nullable', 'string', 'max:100'],
            'size_unit'         => ['nullable', Rule::in(['inches', 'cm', 'mm'])],
            'material_id'       => ['nullable', 'integer', 'exists:materials,id'],
            'material'          => ['nullable', 'string', 'max:100'],
            'material_custom'   => ['nullable', 'string', 'max:100'],
            'thickness'         => ['nullable', 'string', 'max:50'],
            'thickness_unit'    => ['nullable', Rule::in(['GSM', 'MM', 'OUNCE', 'BF', 'MICRON'])],
            'finish_ids'        => ['nullable', 'array'],
            'finish_ids.*'      => ['integer', 'exists:material_finishes,id'],
            'finish'            => ['nullable', 'string', 'max:100'],
            'branding_methods'  => ['nullable', 'array', 'max:2'],
            'branding_methods.*'=> ['string', 'in:Screen printing,Offset printing,Foil stamping,UV,Emboss,Sticker,Sleeves'],
            'branding_method'   => ['nullable', 'string', 'max:100'],
            'lead_time'         => ['sometimes', Rule::enum(RTDLeadTime::class)],
            'moq'               => ['sometimes', 'integer', 'min:1'],
            'max_capacity'      => ['nullable', 'integer', 'min:1'],
            'base_price'        => ['sometimes', 'numeric', 'min:0.01'],
            'buy_now_enabled'   => ['nullable', 'boolean'],
            'delivery_geography'=> ['nullable', 'string', 'max:255'],
            'location_id'       => ['nullable', 'integer'],
            'location_source'   => ['nullable', Rule::in(['saved', 'manual'])],
            'latitude'          => ['nullable', 'numeric'],
            'longitude'         => ['nullable', 'numeric'],

            'price_slabs'                  => ['sometimes', 'array', 'min:1'],
            'price_slabs.*.min_qty'        => ['required_with:price_slabs', 'integer', 'min:1'],
            'price_slabs.*.max_qty'        => ['required_with:price_slabs', 'integer', 'min:1'],
            'price_slabs.*.price_per_unit' => ['required_with:price_slabs', 'numeric', 'min:0.01'],
        ];
    }
}
