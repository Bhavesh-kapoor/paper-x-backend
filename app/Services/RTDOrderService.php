<?php

namespace App\Services;

use App\Enums\NavigationType;
use App\Enums\NotificationType;
use App\Enums\RTDOrderStatus;
use App\Events\RTD\OrderAccepted;
use App\Events\RTD\OrderConnected;
use App\Exceptions\RTDDomainException;
use App\Jobs\HandleOrderAcceptanceTimeout;
use App\Models\RtdOrder;
use App\Models\RtdProduct;
use App\Models\User;
use App\StateMachines\RTDOrderStateMachine;
use App\Support\Notifications\RtdNotificationCopy;
use Illuminate\Support\Facades\DB;

class RTDOrderService
{
    public function __construct(
        protected RTDOrderStateMachine $stateMachine,
        protected RTDProductService $productService,
        protected CommissionCalculator $commissionCalculator,
        protected NotificationService $notificationService,
    ) {
    }

    // ── Order Creation ──

    public function createOrderRequest(array $data, int $brandUserId): RtdOrder
    {
        $product = RtdProduct::with('priceSlabs', 'converter')->find($data['product_id']);

        $this->validateOrderCreation($product, $data['quantity'], $brandUserId);

        $unitPrice = $this->resolveUnitPrice($product, $data['quantity']);

        $sellerGstRegistered = !empty($product->converter?->gst_in);
        $breakdown = $this->commissionCalculator->calculateTotal(
            $data['quantity'],
            $unitPrice,
            $sellerGstRegistered
        );

        $this->commissionCalculator->validateOrderCap($breakdown['subtotal']);

        return DB::transaction(function () use ($data, $brandUserId, $product, $unitPrice, $breakdown) {
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
                'unit_price'           => $unitPrice,
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

            // Notify the converter that a brand wants to order their product.
            $brandName = RtdNotificationCopy::displayName(User::find($brandUserId));
            $productName = RtdNotificationCopy::productName($product);
            $copy = RtdNotificationCopy::orderRequested($order, $brandName, $productName);
            $this->notificationService->create(
                $order->converter_id,
                NotificationType::RTD_ORDER_REQUESTED,
                $copy['title'],
                $copy['body'],
                NavigationType::RTD_ORDER,
                $order->id,
                [
                    'rtd_order_id' => $order->id,
                    'counterparty_name' => $brandName,
                    'product_name' => $productName,
                    'view_target' => 'converter',
                ],
                sprintf('rtd_order_requested_%s', $order->id),
            );

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

            // Notify the brand that the converter accepted their order.
            $order->loadMissing(['product', 'converter']);
            $converterName = RtdNotificationCopy::displayName($order->converter);
            $productName = RtdNotificationCopy::productName($order->product);
            $copy = RtdNotificationCopy::orderAccepted($converterName, $productName);
            $this->notificationService->create(
                $order->brand_id,
                NotificationType::RTD_ORDER_ACCEPTED,
                $copy['title'],
                $copy['body'],
                NavigationType::RTD_ORDER,
                $order->id,
                [
                    'rtd_order_id' => $order->id,
                    'counterparty_name' => $converterName,
                    'product_name' => $productName,
                    'view_target' => 'brand',
                ],
                sprintf('rtd_order_accepted_%s', $order->id),
            );

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

            // Notify the brand that the converter declined their order.
            $order->loadMissing(['product', 'converter']);
            $converterName = RtdNotificationCopy::displayName($order->converter);
            $productName = RtdNotificationCopy::productName($order->product);
            $copy = RtdNotificationCopy::orderDeclined($converterName, $productName);
            $this->notificationService->create(
                $order->brand_id,
                NotificationType::RTD_ORDER_DECLINED,
                $copy['title'],
                $copy['body'],
                NavigationType::RTD_ORDER,
                $order->id,
                [
                    'rtd_order_id' => $order->id,
                    'counterparty_name' => $converterName,
                    'view_target' => 'brand',
                ],
                sprintf('rtd_order_declined_%s', $order->id),
            );

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

            // Notify BOTH parties that payment landed and the order is connected.
            $order->loadMissing(['product', 'brand', 'converter']);
            $brandName = RtdNotificationCopy::displayName($order->brand);
            $converterName = RtdNotificationCopy::displayName($order->converter);
            $productName = RtdNotificationCopy::productName($order->product);

            $converterCopy = RtdNotificationCopy::orderConnectedForConverter($brandName, $productName);
            $this->notificationService->create(
                $order->converter_id,
                NotificationType::RTD_ORDER_CONNECTED,
                $converterCopy['title'],
                $converterCopy['body'],
                NavigationType::RTD_ORDER,
                $order->id,
                [
                    'rtd_order_id' => $order->id,
                    'counterparty_name' => $brandName,
                    'view_target' => 'converter',
                ],
                sprintf('rtd_order_connected_%s_%s', $order->id, $order->converter_id),
            );

            $brandCopy = RtdNotificationCopy::orderConnectedForBrand($converterName, $productName);
            $this->notificationService->create(
                $order->brand_id,
                NotificationType::RTD_ORDER_CONNECTED,
                $brandCopy['title'],
                $brandCopy['body'],
                NavigationType::RTD_ORDER,
                $order->id,
                [
                    'rtd_order_id' => $order->id,
                    'counterparty_name' => $converterName,
                    'view_target' => 'brand',
                ],
                sprintf('rtd_order_connected_%s_%s', $order->id, $order->brand_id),
            );

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

            // Notify the converter that the brand cancelled the order.
            $order->loadMissing(['product', 'brand']);
            $brandName = RtdNotificationCopy::displayName($order->brand);
            $productName = RtdNotificationCopy::productName($order->product);
            $copy = RtdNotificationCopy::orderCancelled($brandName, $productName);
            $this->notificationService->create(
                $order->converter_id,
                NotificationType::RTD_ORDER_CANCELLED,
                $copy['title'],
                $copy['body'],
                NavigationType::RTD_ORDER,
                $order->id,
                [
                    'rtd_order_id' => $order->id,
                    'counterparty_name' => $brandName,
                    'view_target' => 'converter',
                ],
                sprintf('rtd_order_cancelled_%s', $order->id),
            );

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

            // Notify BOTH parties that the request expired unaccepted.
            $order->loadMissing(['product']);
            $productName = RtdNotificationCopy::productName($order->product);

            $converterCopy = RtdNotificationCopy::orderExpiredForConverter($productName);
            $this->notificationService->create(
                $order->converter_id,
                NotificationType::RTD_ORDER_EXPIRED,
                $converterCopy['title'],
                $converterCopy['body'],
                NavigationType::RTD_ORDER,
                $order->id,
                [
                    'rtd_order_id' => $order->id,
                    'view_target' => 'converter',
                ],
                sprintf('rtd_order_expired_%s_%s', $order->id, $order->converter_id),
            );

            $brandCopy = RtdNotificationCopy::orderExpiredForBrand($productName);
            $this->notificationService->create(
                $order->brand_id,
                NotificationType::RTD_ORDER_EXPIRED,
                $brandCopy['title'],
                $brandCopy['body'],
                NavigationType::RTD_ORDER,
                $order->id,
                [
                    'rtd_order_id' => $order->id,
                    'view_target' => 'brand',
                ],
                sprintf('rtd_order_expired_%s_%s', $order->id, $order->brand_id),
            );
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

    /**
     * Per-unit price for an order quantity. Price slabs are optional volume
     * discounts on top of base_price: when a slab covers the quantity, use its
     * price; otherwise (no slabs, or a gap between slabs) fall back to the
     * product's required base_price. Quantity bounds are enforced separately in
     * validateOrderCreation (moq / max_capacity).
     */
    private function resolveUnitPrice(RtdProduct $product, int $quantity): float
    {
        $slab = $product->priceSlabs
            ->first(fn ($s) => $quantity >= $s->min_qty && $quantity <= $s->max_qty);

        if ($slab) {
            return (float) $slab->price_per_unit;
        }

        return (float) $product->base_price;
    }
}
