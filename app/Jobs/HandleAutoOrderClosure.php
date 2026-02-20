<?php

namespace App\Jobs;

use App\Enums\RTDOrderStatus;
use App\Enums\RTDPayoutStatus;
use App\Events\RTD\OrderCompleted;
use App\Models\RtdOrder;
use App\Services\RTDProductService;
use App\StateMachines\RTDOrderStateMachine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HandleAutoOrderClosure implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private readonly int $orderId,
    ) {
    }

    public function handle(RTDOrderStateMachine $stateMachine, RTDProductService $productService): void
    {
        $order = RtdOrder::with(['product', 'payout'])->find($this->orderId);

        if (!$order) {
            Log::warning("RTD auto-closure: order {$this->orderId} not found");
            return;
        }

        if ($order->status !== RTDOrderStatus::DISPATCHED) {
            return;
        }

        DB::transaction(function () use ($order, $stateMachine, $productService) {
            $order = RtdOrder::where('id', $order->id)
                ->lockForUpdate()
                ->first();

            if ($order->status !== RTDOrderStatus::DISPATCHED) {
                return;
            }

            $stateMachine->transition($order, RTDOrderStatus::COMPLETED);

            $order->update(['completed_at' => now()]);

            if ($order->payout) {
                $order->payout->update([
                    'payout_status' => RTDPayoutStatus::RELEASED,
                    'released_at'   => now(),
                ]);
            }

            $productService->rewardCompletion($order->product);

            OrderCompleted::dispatch($order);
        });
    }
}
