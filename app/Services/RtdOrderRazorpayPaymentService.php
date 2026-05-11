<?php

namespace App\Services;

use App\Enums\RTDOrderStatus;
use App\Exceptions\RazorpayDomainException;
use App\Exceptions\RTDDomainException;
use App\Models\RtdOrder;
use App\Models\RtdOrderPaymentOrder;
use App\Services\Payments\Contracts\RazorpayClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Razorpay\Api\Errors\SignatureVerificationError;

class RtdOrderRazorpayPaymentService
{
    public function __construct(
        protected RazorpayClient $razorpayClient,
        protected RTDOrderService $rtdOrderService,
    ) {}

    /**
     * @return array{key_id: string, razorpay_order_id: string, amount: int, currency: string, receipt: string, order: array{id: int, total_amount: string}}
     */
    public function createOrderForRtdOrder(int $brandUserId, int $rtdOrderId): array
    {
        $keyId = config('services.razorpay.key_id');
        $keySecret = config('services.razorpay.key_secret');
        if (empty($keyId) || empty($keySecret)) {
            throw new RazorpayDomainException('Razorpay is not configured', 503);
        }

        return DB::transaction(function () use ($brandUserId, $rtdOrderId, $keyId) {
            /** @var RtdOrder $order */
            $order = RtdOrder::query()
                ->whereKey($rtdOrderId)
                ->where('brand_id', $brandUserId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status !== RTDOrderStatus::ACCEPTED) {
                throw new RTDDomainException('Order is not awaiting payment', 422);
            }

            RtdOrderPaymentOrder::query()
                ->where('rtd_order_id', $order->id)
                ->where('status', RtdOrderPaymentOrder::STATUS_CREATED)
                ->update(['status' => RtdOrderPaymentOrder::STATUS_EXPIRED]);

            $totalInr = (float) (string) $order->total_amount;
            $amountPaise = (int) round($totalInr * 100);
            if ($amountPaise < 100) {
                throw new RazorpayDomainException('Order amount too small', 400);
            }

            $currency = strtoupper((string) config('services.razorpay.currency', 'INR'));
            $receipt = 'RTD-'.Str::ulid()->toString();

            $metadata = [
                'rtd_order_id' => $order->id,
                'total_amount_inr' => $totalInr,
            ];

            $rzOrder = $this->razorpayClient->createOrder([
                'amount' => $amountPaise,
                'currency' => $currency,
                'receipt' => $receipt,
                'payment_capture' => 1,
                'notes' => [
                    'user_id' => (string) $brandUserId,
                    'rtd_order_id' => (string) $order->id,
                ],
            ]);

            $razorpayOrderId = (string) ($rzOrder['id'] ?? '');
            if ($razorpayOrderId === '') {
                throw new RazorpayDomainException('Failed to create Razorpay order', 502);
            }

            RtdOrderPaymentOrder::query()->create([
                'rtd_order_id' => $order->id,
                'user_id' => $brandUserId,
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => null,
                'receipt' => $receipt,
                'amount_paise' => $amountPaise,
                'currency' => $currency,
                'status' => RtdOrderPaymentOrder::STATUS_CREATED,
                'metadata' => $metadata,
                'paid_at' => null,
            ]);

            return [
                'key_id' => $keyId,
                'razorpay_order_id' => $razorpayOrderId,
                'amount' => $amountPaise,
                'currency' => $currency,
                'receipt' => $receipt,
                'order' => [
                    'id' => $order->id,
                    'total_amount' => (string) $order->total_amount,
                ],
            ];
        });
    }

    public function verifyAndFulfill(
        int $brandUserId,
        int $rtdOrderId,
        string $razorpayOrderId,
        string $paymentId,
        string $signature,
    ): RtdOrder {
        try {
            $this->razorpayClient->verifyPaymentSignature([
                'razorpay_order_id' => $razorpayOrderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ]);
        } catch (SignatureVerificationError $e) {
            throw new RazorpayDomainException('Invalid payment signature', 403, $e);
        }

        try {
            return DB::transaction(function () use ($brandUserId, $rtdOrderId, $razorpayOrderId, $paymentId) {
                /** @var RtdOrderPaymentOrder|null $po */
                $po = RtdOrderPaymentOrder::query()
                    ->where('razorpay_order_id', $razorpayOrderId)
                    ->where('rtd_order_id', $rtdOrderId)
                    ->where('user_id', $brandUserId)
                    ->lockForUpdate()
                    ->first();

                if (! $po) {
                    throw new RazorpayDomainException('Payment order not found', 404);
                }

                if ($po->status === RtdOrderPaymentOrder::STATUS_PAID) {
                    return RtdOrder::query()
                        ->whereKey($rtdOrderId)
                        ->with(['product', 'payout', 'brand', 'converter'])
                        ->firstOrFail();
                }

                if ($po->status !== RtdOrderPaymentOrder::STATUS_CREATED) {
                    throw new RazorpayDomainException('Order cannot be fulfilled', 409);
                }

                $payment = $this->razorpayClient->fetchPayment($paymentId);
                if (! $this->paymentMatchesOrder($payment, $po)) {
                    RtdOrderPaymentOrder::query()
                        ->whereKey($po->id)
                        ->update(['status' => RtdOrderPaymentOrder::STATUS_FAILED]);

                    throw new RazorpayDomainException('Payment mismatch', 422);
                }

                $paidOrder = $this->rtdOrderService->confirmPayment($rtdOrderId, $brandUserId);

                $po->razorpay_payment_id = $paymentId;
                $po->status = RtdOrderPaymentOrder::STATUS_PAID;
                $po->paid_at = now();
                $po->save();

                return $paidOrder->fresh(['product', 'payout', 'brand', 'converter']);
            });
        } catch (RazorpayDomainException $e) {
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $payment
     */
    protected function paymentMatchesOrder(array $payment, RtdOrderPaymentOrder $po): bool
    {
        $apiOrderId = (string) ($payment['order_id'] ?? '');
        $status = (string) ($payment['status'] ?? '');
        $amount = (int) ($payment['amount'] ?? 0);
        $currency = strtoupper((string) ($payment['currency'] ?? ''));

        return $apiOrderId === $po->razorpay_order_id
            && $status === 'captured'
            && $amount === $po->amount_paise
            && $currency === strtoupper($po->currency);
    }
}
