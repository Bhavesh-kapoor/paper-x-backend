<?php

namespace App\Services;

use App\Enums\RTDLeadTime;
use App\Enums\RTDOrderStatus;
use App\Enums\RTDPayoutStatus;
use App\Events\RTD\OrderAccepted;
use App\Events\RTD\OrderCompleted;
use App\Events\RTD\OrderDispatched;
use App\Events\RTD\OrderPaid;
use App\Exceptions\RTDDomainException;
use App\Jobs\HandleAutoOrderClosure;
use App\Jobs\HandleOrderAcceptanceTimeout;
use App\Models\RtdOrder;
use App\Models\RtdPayout;
use App\Models\RtdProduct;
use App\StateMachines\RTDOrderStateMachine;
use Illuminate\Support\Facades\DB;

class RTDOrderService
{
    public function __construct(
        protected RTDOrderStateMachine $stateMachine,
        protected RTDProductService $productService,
        protected CommissionCalculator $commissionCalculator,
    ) {
    }

    // ── Order Creation ──

    public function createOrderRequest(array $data, int $brandUserId): RtdOrder
    {
        $product = RtdProduct::with('priceSlabs')->find($data['product_id']);

        $this->validateOrderCreation($product, $data['quantity']);

        $slab = $this->resolveMatchingPriceSlab($product, $data['quantity']);

        $breakdown = $this->commissionCalculator->calculateTotal($data['quantity'], $slab->price_per_unit);

        $this->commissionCalculator->validateOrderCap($breakdown['subtotal']);

        return DB::transaction(function () use ($data, $brandUserId, $product, $slab, $breakdown) {
            $leadTime = $product->lead_time;
            $deadline = now()->addMinutes($leadTime->acceptanceWindowMinutes());

            $order = RtdOrder::create([
                'product_id'           => $product->id,
                'brand_id'             => $brandUserId,
                'converter_id'         => $product->converter_id,
                'quantity'             => $data['quantity'],
                'logo_path'            => $data['logo_path'] ?? null,
                'unit_price'           => $slab->price_per_unit,
                'subtotal'             => $breakdown['subtotal'],
                'commission_percent'   => $breakdown['commission_percent'],
                'commission_amount'    => $breakdown['commission_amount'],
                'gst_percent'          => $breakdown['gst_percent'],
                'gst_amount'           => $breakdown['gst_amount'],
                'total_amount'         => $breakdown['total_amount'],
                'status'               => RTDOrderStatus::REQUESTED,
                'confirmation_deadline'=> $deadline,
            ]);

            RtdPayout::create([
                'order_id'      => $order->id,
                'converter_id'  => $product->converter_id,
                'amount'        => $breakdown['subtotal'],
                'payout_status' => RTDPayoutStatus::HELD,
            ]);

            HandleOrderAcceptanceTimeout::dispatch($order->id)
                ->delay($deadline);

            return $order->fresh(['product', 'payout']);
        });
    }

    // ── Acceptance (CONCURRENCY-SAFE) ──

    public function acceptOrder(int $orderId, int $converterUserId): RtdOrder
    {
        return DB::transaction(function () use ($orderId, $converterUserId) {
            /** @var RtdOrder $order */
            $order = RtdOrder::where('id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->converter_id !== $converterUserId) {
                throw new RTDDomainException('You do not own this order');
            }

            if ($order->status !== RTDOrderStatus::REQUESTED) {
                throw new RTDDomainException('Order has already been processed');
            }

            if (now()->greaterThan($order->confirmation_deadline)) {
                throw new RTDDomainException('Acceptance window has expired');
            }

            $this->stateMachine->transition($order, RTDOrderStatus::ACCEPTED);

            OrderAccepted::dispatch($order);

            return $order->fresh(['product']);
        });
    }

    // ── Decline (CONCURRENCY-SAFE) ──

    public function declineOrder(int $orderId, int $converterUserId): RtdOrder
    {
        return DB::transaction(function () use ($orderId, $converterUserId) {
            /** @var RtdOrder $order */
            $order = RtdOrder::where('id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->converter_id !== $converterUserId) {
                throw new RTDDomainException('You do not own this order');
            }

            if ($order->status !== RTDOrderStatus::REQUESTED) {
                throw new RTDDomainException('Order has already been processed');
            }

            $this->stateMachine->transition($order, RTDOrderStatus::DECLINED);

            $this->productService->incrementDecline($order->product);

            return $order->fresh();
        });
    }

    // ── Payment ──

    public function confirmPayment(int $orderId, int $brandUserId): RtdOrder
    {
        return DB::transaction(function () use ($orderId, $brandUserId) {
            /** @var RtdOrder $order */
            $order = RtdOrder::where('id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->brand_id !== $brandUserId) {
                throw new RTDDomainException('You do not own this order');
            }

            if ($order->status === RTDOrderStatus::PAID) {
                return $order;
            }

            $this->stateMachine->transition($order, RTDOrderStatus::PAID);

            $order->update([
                'paid_at'        => now(),
                'payment_status' => 'paid',
                'dispatch_deadline' => now()->addHours(
                    $this->resolveDispatchHours($order->product->lead_time)
                ),
            ]);

            OrderPaid::dispatch($order);

            return $order->fresh();
        });
    }

    // ── Production ──

    public function markInProduction(int $orderId, int $converterUserId): RtdOrder
    {
        return DB::transaction(function () use ($orderId, $converterUserId) {
            $order = RtdOrder::where('id', $orderId)
                ->where('converter_id', $converterUserId)
                ->firstOrFail();

            $this->stateMachine->transition($order, RTDOrderStatus::IN_PRODUCTION);

            return $order->fresh();
        });
    }

    // ── Dispatch (with delivery buffer) ──

    public function markDispatched(int $orderId, array $proofData, int $converterUserId): RtdOrder
    {
        return DB::transaction(function () use ($orderId, $proofData, $converterUserId) {
            $order = RtdOrder::where('id', $orderId)
                ->where('converter_id', $converterUserId)
                ->with('product')
                ->firstOrFail();

            $this->stateMachine->transition($order, RTDOrderStatus::DISPATCHED);

            $order->dispatchProofs()->create([
                'proof_type' => $proofData['proof_type'],
                'file_path'  => $proofData['file_path'],
            ]);

            $bufferDays = $order->product->lead_time->deliveryBufferDays();

            $order->update([
                'dispatched_at'     => now(),
                'delivery_deadline' => now()->addDays($bufferDays),
            ]);

            HandleAutoOrderClosure::dispatch($order->id)
                ->delay(now()->addDays($bufferDays));

            OrderDispatched::dispatch($order);

            return $order->fresh(['dispatchProofs']);
        });
    }

    // ── Delivery Confirmation (with decline reset) ──

    public function confirmDelivery(int $orderId, int $brandUserId): RtdOrder
    {
        return DB::transaction(function () use ($orderId, $brandUserId) {
            $order = RtdOrder::where('id', $orderId)
                ->where('brand_id', $brandUserId)
                ->with(['product', 'payout'])
                ->firstOrFail();

            $this->stateMachine->transition($order, RTDOrderStatus::COMPLETED);

            $order->update(['completed_at' => now()]);

            if ($order->payout) {
                $order->payout->update([
                    'payout_status' => RTDPayoutStatus::RELEASED,
                    'released_at'   => now(),
                ]);
            }

            $this->productService->rewardCompletion($order->product);

            OrderCompleted::dispatch($order);

            return $order->fresh();
        });
    }

    // ── Dispute ──

    public function raiseDispute(int $orderId, int $brandUserId): RtdOrder
    {
        return DB::transaction(function () use ($orderId, $brandUserId) {
            $order = RtdOrder::where('id', $orderId)
                ->where('brand_id', $brandUserId)
                ->with('payout')
                ->firstOrFail();

            $this->stateMachine->transition($order, RTDOrderStatus::DISPUTED);

            if ($order->payout) {
                $order->payout->update([
                    'payout_status' => RTDPayoutStatus::HOLD_DISPUTE,
                ]);
            }

            return $order->fresh();
        });
    }

    // ── Cancellation ──

    public function cancelOrder(int $orderId, int $brandUserId): RtdOrder
    {
        return DB::transaction(function () use ($orderId, $brandUserId) {
            $order = RtdOrder::where('id', $orderId)
                ->where('brand_id', $brandUserId)
                ->firstOrFail();

            $this->stateMachine->transition($order, RTDOrderStatus::CANCELLED);

            return $order->fresh();
        });
    }

    // ── Expiry (called by timeout job) ──

    public function expireOrder(RtdOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $order = RtdOrder::where('id', $order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== RTDOrderStatus::REQUESTED) {
                return;
            }

            $this->stateMachine->transition($order, RTDOrderStatus::EXPIRED);

            $this->productService->incrementDecline($order->product);
        });
    }

    // ── Listing ──

    public function getMyOrders(int $userId, string $role, array $filters): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = RtdOrder::with(['product', 'brand', 'converter']);

        if ($role === 'brand') {
            $query->where('brand_id', $userId);
        } else {
            $query->where('converter_id', $userId);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function getOrderDetail(int $orderId, int $userId): RtdOrder
    {
        return RtdOrder::where('id', $orderId)
            ->where(function ($q) use ($userId) {
                $q->where('brand_id', $userId)
                  ->orWhere('converter_id', $userId);
            })
            ->with(['product.priceSlabs', 'brand', 'converter', 'dispatchProofs', 'payout'])
            ->firstOrFail();
    }

    // ── Private helpers ──

    private function validateOrderCreation(?RtdProduct $product, int $quantity): void
    {
        if (!$product) {
            throw new RTDDomainException('Product not found', 404);
        }

        if ($product->status !== 'active') {
            throw new RTDDomainException('Product is not currently active');
        }

        if (!$product->buy_now_enabled) {
            throw new RTDDomainException('Buy Now is not enabled for this product');
        }

        if ($quantity < $product->moq) {
            throw new RTDDomainException(
                "Quantity {$quantity} is below the minimum order quantity of {$product->moq}"
            );
        }

        if ($product->max_capacity !== null && $quantity > $product->max_capacity) {
            throw new RTDDomainException(
                "Quantity {$quantity} exceeds maximum capacity of {$product->max_capacity}"
            );
        }
    }

    private function resolveMatchingPriceSlab(RtdProduct $product, int $quantity): \App\Models\RtdPriceSlab
    {
        $slab = $product->priceSlabs
            ->first(fn ($s) => $quantity >= $s->min_qty && $quantity <= $s->max_qty);

        if (!$slab) {
            throw new RTDDomainException(
                "No matching price slab found for quantity {$quantity}"
            );
        }

        return $slab;
    }

    private function resolveDispatchHours(RTDLeadTime $leadTime): int
    {
        return match ($leadTime) {
            RTDLeadTime::SAME_DAY => 12,
            RTDLeadTime::H24      => 24,
            RTDLeadTime::H48      => 48,
            RTDLeadTime::DAYS_3_5 => 120,
        };
    }
}
