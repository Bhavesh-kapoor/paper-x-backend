<?php

namespace App\Services;

use App\Enums\RTDOrderStatus;
use App\Events\RTD\OrderAccepted;
use App\Events\RTD\OrderConnected;
use App\Exceptions\RTDDomainException;
use App\Jobs\HandleOrderAcceptanceTimeout;
use App\Models\RtdOrder;
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
        $product = RtdProduct::with('priceSlabs', 'converter')->find($data['product_id']);

        $this->validateOrderCreation($product, $data['quantity'], $brandUserId);

        $slab = $this->resolveMatchingPriceSlab($product, $data['quantity']);

        $sellerGstRegistered = !empty($product->converter?->gst_in);
        $breakdown = $this->commissionCalculator->calculateTotal(
            $data['quantity'],
            $slab->price_per_unit,
            $sellerGstRegistered
        );

        $this->commissionCalculator->validateOrderCap($breakdown['subtotal']);

        return DB::transaction(function () use ($data, $brandUserId, $product, $slab, $breakdown) {
            $existingActive = RtdOrder::where('brand_id', $brandUserId)
                ->where('product_id', $product->id)
                ->whereIn('status', RTDOrderStatus::activeStatuses())
                ->lockForUpdate()
                ->first();

            if ($existingActive) {
                throw new RTDDomainException(
                    'You already have an active order for this product (Order #' . $existingActive->id . ')',
                    422
                );
            }

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

            HandleOrderAcceptanceTimeout::dispatch($order->id)
                ->delay($deadline);

            return $order->fresh(['product', 'converter']);
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
                ->with('product')
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->brand_id !== $brandUserId) {
                throw new RTDDomainException('You do not own this order');
            }

            if ($order->status === RTDOrderStatus::CONNECTED) {
                return $order;
            }

            $this->stateMachine->transition($order, RTDOrderStatus::CONNECTED);

            $order->update([
                'paid_at'        => now(),
                'payment_status' => 'paid',
            ]);

            $this->productService->rewardCompletion($order->product);

            OrderConnected::dispatch($order);

            return $order->fresh(['brand', 'converter']);
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
            ->with(['product.priceSlabs', 'brand', 'converter'])
            ->firstOrFail();
    }

    // ── Private helpers ──

    private function validateOrderCreation(?RtdProduct $product, int $quantity, int $brandUserId): void
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

        $activeOrder = RtdOrder::where('brand_id', $brandUserId)
            ->where('product_id', $product->id)
            ->whereIn('status', RTDOrderStatus::activeStatuses())
            ->first();

        if ($activeOrder) {
            throw new RTDDomainException(
                'You already have an active order for this product (Order #' . $activeOrder->id . ')',
                422
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
}
