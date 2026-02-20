<?php

namespace App\Http\Resources\RTD;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RtdProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isOwner = $request->user()?->id === $this->converter_id;

        return [
            'id'                => $this->id,
            'category'          => $this->category,
            'product_name'      => $this->product_name,
            'image_path'        => $this->image_path,
            'size'              => $this->size,
            'material'          => $this->material,
            'gsm'               => $this->gsm,
            'finish'            => $this->finish,
            'branding_method'   => $this->branding_method,
            'lead_time'         => $this->lead_time?->value,
            'lead_time_label'   => $this->lead_time?->label(),
            'moq'               => $this->moq,
            'max_capacity'      => $this->when($isOwner, $this->max_capacity),
            'base_price'        => $this->base_price,
            'buy_now_enabled'   => $this->buy_now_enabled,
            'delivery_geography'=> $this->delivery_geography,
            'status'            => $this->status,
            'decline_count'     => $this->when($isOwner, $this->decline_count),
            'visibility_score'  => $this->when($isOwner, $this->visibility_score),
            'price_slabs'       => $this->whenLoaded('priceSlabs', fn () =>
                $this->priceSlabs->map(fn ($s) => [
                    'id'             => $s->id,
                    'min_qty'        => $s->min_qty,
                    'max_qty'        => $s->max_qty,
                    'price_per_unit' => $s->price_per_unit,
                ])
            ),
            'created_at'        => $this->created_at?->toISOString(),
            'updated_at'        => $this->updated_at?->toISOString(),
        ];
    }
}
