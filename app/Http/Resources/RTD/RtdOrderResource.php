<?php

namespace App\Http\Resources\RTD;

use App\Enums\RTDOrderStatus;
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

        return [
            'id'                  => $this->id,
            'product_id'          => $this->product_id,
            'product'             => new RtdProductResource($this->whenLoaded('product')),
            'quantity'            => $this->quantity,
            'logo_path'           => $this->logo_path,
            'unit_price'          => $this->unit_price,
            'subtotal'            => $this->subtotal,
            'commission_percent'  => $this->commission_percent,
            'commission_amount'   => $this->commission_amount,
            'gst_percent'         => $this->gst_percent,
            'gst_amount'          => $this->gst_amount,
            'total_amount'        => $this->total_amount,
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
                fn () => $this->whenLoaded('brand', fn () => [
                    'id'   => $this->brand->id,
                    'name' => $this->brand->name,
                ])
            ),

            'converter' => $this->whenLoaded('converter', fn () => [
                'id'   => $this->converter->id,
                'name' => $this->converter->name,
            ]),

            'dispatch_proofs' => $this->whenLoaded('dispatchProofs', fn () =>
                $this->dispatchProofs->map(fn ($p) => [
                    'id'         => $p->id,
                    'proof_type' => $p->proof_type,
                    'file_path'  => $p->file_path,
                    'created_at' => $p->created_at?->toISOString(),
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
