<?php

namespace App\Http\Resources\RTD;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RtdPayoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'order_id'      => $this->order_id,
            'amount'        => $this->amount,
            'payout_status' => $this->payout_status?->value,
            'status_label'  => $this->payout_status?->label(),
            'released_at'   => $this->released_at?->toISOString(),
            'created_at'    => $this->created_at?->toISOString(),
        ];
    }
}
