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
        $isPaid = in_array($this->status, [
            RTDOrderStatus::PAID,
            RTDOrderStatus::IN_PRODUCTION,
            RTDOrderStatus::DISPATCHED,
            RTDOrderStatus::COMPLETED,
        ]);

        $isCompleted = in_array($this->status, [
            RTDOrderStatus::DISPATCHED,
            RTDOrderStatus::COMPLETED,
        ]);

        $sellerGstRegistered = $this->relationLoaded('converter')
            ? !empty($this->converter?->gst_in)
            : false;

        return [
            'id'                     => $this->id,
            'product_id'             => $this->product_id,
            'product'                => new RtdProductResource($this->whenLoaded('product')),
            'quantity'               => $this->quantity,
            'logo_path'              => RtdPublicUpload::publicUrl($this->logo_path),
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
            'dispatch_deadline'   => $this->dispatch_deadline?->toISOString(),
            'delivery_deadline'   => $this->delivery_deadline?->toISOString(),
            'payment_status'      => $this->payment_status,
            'paid_at'             => $this->paid_at?->toISOString(),
            'dispatched_at'       => $this->dispatched_at?->toISOString(),
            'completed_at'        => $this->completed_at?->toISOString(),

            'brand' => $this->when(
                $isPaid || $user?->id === $this->brand_id,
                fn () => $this->whenLoaded('brand', fn () => array_filter([
                    'id'           => $this->brand->id,
                    'name'         => $this->brand->name,
                    'company_name' => $this->brand->company_name,
                    'email'        => $isPaid ? $this->brand->email : null,
                    'mobile'       => $isPaid ? $this->brand->mobile : null,
                ]))
            ),

            'converter' => $this->whenLoaded('converter', fn () => array_filter([
                'id'           => $this->converter->id,
                'name'         => $this->converter->name,
                'company_name' => $this->converter->company_name,
                'email'        => $isPaid ? $this->converter->email : null,
                'mobile'       => $isPaid ? $this->converter->mobile : null,
            ])),

            'tracking_number' => $this->whenLoaded('dispatchProofs', fn () =>
                $this->dispatchProofs->first()?->tracking_number
            ),
            'courier_name' => $this->whenLoaded('dispatchProofs', fn () =>
                $this->dispatchProofs->first()?->courier_name
            ),

            'dispatch_proofs' => $this->whenLoaded('dispatchProofs', fn () =>
                $this->dispatchProofs->map(fn ($p) => [
                    'id'              => $p->id,
                    'proof_type'      => $p->proof_type,
                    'file_path'       => RtdPublicUpload::publicUrl($p->file_path),
                    'courier_name'    => $p->courier_name,
                    'tracking_number' => $p->tracking_number,
                    'dispatch_date'   => $p->dispatch_date?->toDateString(),
                    'created_at'      => $p->created_at?->toISOString(),
                ])
            ),

            'payout' => $this->when(
                $user?->id === $this->converter_id,
                fn () => new RtdPayoutResource($this->whenLoaded('payout'))
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
