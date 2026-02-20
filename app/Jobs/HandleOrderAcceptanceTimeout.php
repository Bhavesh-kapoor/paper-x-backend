<?php

namespace App\Jobs;

use App\Enums\RTDOrderStatus;
use App\Models\RtdOrder;
use App\Services\RTDOrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class HandleOrderAcceptanceTimeout implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int $orderId,
    ) {
    }

    public function handle(RTDOrderService $orderService): void
    {
        $order = RtdOrder::find($this->orderId);

        if (!$order) {
            Log::warning("RTD acceptance timeout: order {$this->orderId} not found");
            return;
        }

        if ($order->status !== RTDOrderStatus::REQUESTED) {
            return;
        }

        $orderService->expireOrder($order);
    }
}
