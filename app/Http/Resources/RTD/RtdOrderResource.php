<?php

namespace App\Http\Resources\RTD;

use App\Enums\RTDOrderStatus;
use App\Support\RtdPublicUpload;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RtdOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user   = $request->user();
        $isConnected = $this->status === RTDOrderStatus::CONNECTED;

        $sellerGstRegistered = $this->relationLoaded('converter')
            ? !empty($this->converter?->gst_in)
            : false;

        return [
            'id'                     => $this->id,
            'product_id'             => $this->product_id,
            'product'                => new RtdProductResource($this->whenLoaded('product')),
            'quantity'               => $this->quantity,
            'logo_path'              => RtdPublicUpload::publicUrl($this->logo_path),
            'delivery_address'       => $this->delivery_address,
            'order_notes'            => $this->order_notes,
            'unit_price'             => $this->unit_price,
            'subtotal'               => $this->subtotal,
            'commission_percent'     => $this->commission_percent,
            'commission_amount'      => $this->commission_amount,
            'gst_percent'            => $this->gst_percent,
            'gst_amount'             => $this->gst_amount,
            'total_amount'           => $this->total_amount,
            'seller_gst_registered'  => $sellerGstRegistered,
            'status'              => $this->status?->value,
            'status_label'        => $this->status?->label(),
            'confirmation_deadline' => $this->confirmation_deadline?->toISOString(),
            'payment_status'      => $this->payment_status,
            'paid_at'             => $this->paid_at?->toISOString(),

            'brand' => $this->when(
                $isConnected || $user?->id === $this->brand_id,
                fn () => $this->whenLoaded('brand', fn () => array_filter([
                    'id'           => $this->brand->id,
                    'name'         => $this->brand->name,
                    'company_name' => $this->brand->company_name,
                    'email'        => $isConnected ? $this->brand->email : null,
                    'mobile'       => $isConnected ? $this->brand->mobile : null,
                ]))
            ),

            'converter' => $this->whenLoaded('converter', fn () => array_filter([
                'id'           => $this->converter->id,
                'name'         => $this->converter->name,
                'company_name' => $this->converter->company_name,
                'email'        => $isConnected ? $this->converter->email : null,
                'mobile'       => $isConnected ? $this->converter->mobile : null,
            ])),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
