<?php

namespace App\Services\Payments;

use App\Exceptions\RazorpayDomainException;
use App\Models\CreditPack;
use App\Models\Wallet;
use App\Models\WalletPaymentOrder;
use App\Services\Payments\Contracts\RazorpayClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Razorpay\Api\Errors\SignatureVerificationError;

class RazorpayPaymentService
{
    public function __construct(
        protected RazorpayClient $razorpayClient,
    ) {}

    /**
     * @return array{key_id: string, razorpay_order_id: string, amount: int, currency: string, receipt: string, pack: array{id: int, name: string, credits: int, total_price: float}}
     */
    public function createOrderForPack(int $userId, int $creditPackId): array
    {
        $keyId = config('services.razorpay.key_id');
        $keySecret = config('services.razorpay.key_secret');
        if (empty($keyId) || empty($keySecret)) {
            throw new RazorpayDomainException('Razorpay is not configured', 503);
        }

        $pack = CreditPack::query()->find($creditPackId);
        if (! $pack) {
            throw new RazorpayDomainException('Invalid credit pack', 400);
        }
        if (! $pack->is_active) {
            throw new RazorpayDomainException('Credit pack is not available', 400);
        }

        $totalInr = (float) (string) $pack->total_price;
        $gstAmount = (float) (string) $pack->gst_amount;
        $amountPaise = (int) round($totalInr * 100);
        if ($amountPaise < 100) {
            throw new RazorpayDomainException('Order amount too small', 400);
        }

        $currency = strtoupper((string) config('services.razorpay.currency', 'INR'));
        // Razorpay: receipt must be ≤40 characters.
        $receipt = 'WPO-'.Str::ulid()->toString();

        $metadata = [
            'pack_id' => $pack->id,
            'pack_name' => $pack->name,
            'pack_slug' => $pack->slug,
            'price_inr' => (float) (string) $pack->price,
            'gst_percentage' => (float) (string) $pack->gst_percentage,
            'gst_amount_inr' => $gstAmount,
            'total_price_inr' => $totalInr,
            'credits' => $pack->credits,
        ];

        $rzOrder = $this->razorpayClient->createOrder([
            'amount' => $amountPaise,
            'currency' => $currency,
            'receipt' => $receipt,
            'payment_capture' => 1,
            'notes' => [
                'user_id' => (string) $userId,
                'credit_pack_id' => (string) $pack->id,
            ],
        ]);

        $razorpayOrderId = (string) ($rzOrder['id'] ?? '');
        if ($razorpayOrderId === '') {
            throw new RazorpayDomainException('Failed to create Razorpay order', 502);
        }

        WalletPaymentOrder::query()->create([
            'user_id' => $userId,
            'credit_pack_id' => $pack->id,
            'razorpay_order_id' => $razorpayOrderId,
            'razorpay_payment_id' => null,
            'receipt' => $receipt,
            'amount_paise' => $amountPaise,
            'currency' => $currency,
            'credits' => $pack->credits,
            'status' => WalletPaymentOrder::STATUS_CREATED,
            'wallet_transaction_id' => null,
            'metadata' => $metadata,
            'paid_at' => null,
        ]);

        return [
            'key_id' => $keyId,
            'razorpay_order_id' => $razorpayOrderId,
            'amount' => $amountPaise,
            'currency' => $currency,
            'receipt' => $receipt,
            'pack' => [
                'id' => $pack->id,
                'name' => $pack->name,
                'credits' => $pack->credits,
                'total_price' => $totalInr,
            ],
        ];
    }

    /**
     * Razorpay order for a variable credit amount (e.g. pay posting fee in INR at inr_per_credit).
     *
     * @return array{key_id: string, razorpay_order_id: string, amount: int, currency: string, receipt: string, pack: array{id: int, name: string, credits: int, total_price: float}}
     */
    public function createOrderForExactCredits(int $userId, int $credits): array
    {
        $keyId = config('services.razorpay.key_id');
        $keySecret = config('services.razorpay.key_secret');
        if (empty($keyId) || empty($keySecret)) {
            throw new RazorpayDomainException('Razorpay is not configured', 503);
        }

        $max = (int) config('wallet_razorpay.exact_credits_max', 500);
        if ($credits < 1 || $credits > $max) {
            throw new RazorpayDomainException('Invalid credits amount', 400);
        }

        $inrPerCredit = (float) config('wallet_razorpay.inr_per_credit', 1.0);
        if ($inrPerCredit <= 0) {
            throw new RazorpayDomainException('Invalid pricing configuration', 500);
        }

        $totalInr = round($credits * $inrPerCredit, 2);
        $amountPaise = (int) round($totalInr * 100);
        if ($amountPaise < 100) {
            $amountPaise = 100;
        }

        $currency = strtoupper((string) config('services.razorpay.currency', 'INR'));
        $receipt = 'WEC-'.Str::ulid()->toString();

        $metadata = [
            'order_kind' => 'exact_credits',
            'inr_per_credit' => $inrPerCredit,
            'total_price_inr' => $totalInr,
            'price_inr' => $totalInr,
            'gst_amount_inr' => 0.0,
            'credits' => $credits,
        ];

        try {
            $rzOrder = $this->razorpayClient->createOrder([
                'amount' => $amountPaise,
                'currency' => $currency,
                'receipt' => $receipt,
                'payment_capture' => 1,
                'notes' => [
                    'user_id' => (string) $userId,
                    'order_kind' => 'exact_credits',
                    'credits' => (string) $credits,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::warning('rzp.exact_credits_create_order_api_failed', ['err' => $e->getMessage()]);

            throw new RazorpayDomainException(
                'Razorpay could not create the order: '.$e->getMessage(),
                502,
                $e
            );
        }

        $razorpayOrderId = (string) ($rzOrder['id'] ?? '');
        if ($razorpayOrderId === '') {
            throw new RazorpayDomainException('Failed to create Razorpay order', 502);
        }

        WalletPaymentOrder::query()->create([
            'user_id' => $userId,
            'credit_pack_id' => null,
            'razorpay_order_id' => $razorpayOrderId,
            'razorpay_payment_id' => null,
            'receipt' => $receipt,
            'amount_paise' => $amountPaise,
            'currency' => $currency,
            'credits' => $credits,
            'status' => WalletPaymentOrder::STATUS_CREATED,
            'wallet_transaction_id' => null,
            'metadata' => $metadata,
            'paid_at' => null,
        ]);

        return [
            'key_id' => $keyId,
            'razorpay_order_id' => $razorpayOrderId,
            'amount' => $amountPaise,
            'currency' => $currency,
            'receipt' => $receipt,
            'pack' => [
                'id' => 0,
                'name' => 'Posting payment',
                'credits' => $credits,
                'total_price' => $totalInr,
            ],
        ];
    }

    public function verifyAndFulfill(int $userId, string $orderId, string $paymentId, string $signature): WalletPaymentOrder
    {
        try {
            $this->razorpayClient->verifyPaymentSignature([
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ]);
        } catch (SignatureVerificationError $e) {
            throw new RazorpayDomainException('Invalid payment signature', 403, $e);
        }

        try {
            return DB::transaction(function () use ($userId, $orderId, $paymentId) {
                /** @var WalletPaymentOrder|null $wpo */
                $wpo = WalletPaymentOrder::query()
                    ->where('razorpay_order_id', $orderId)
                    ->where('user_id', $userId)
                    ->lockForUpdate()
                    ->first();

                if (! $wpo) {
                    throw new RazorpayDomainException('Payment order not found', 404);
                }

                if ($wpo->status === WalletPaymentOrder::STATUS_PAID) {
                    $wpo->load(['walletTransaction', 'user.wallet']);

                    return $wpo;
                }

                if ($wpo->status !== WalletPaymentOrder::STATUS_CREATED) {
                    throw new RazorpayDomainException('Order cannot be fulfilled', 409);
                }

                $this->fulfillCreatedOrder($wpo, $paymentId);

                $wpo->refresh();
                $wpo->load(['walletTransaction', 'user.wallet']);

                return $wpo;
            });
        } catch (RazorpayDomainException $e) {
            if ($e->getStatusCode() === 422 && $e->getMessage() === 'Payment mismatch') {
                WalletPaymentOrder::query()
                    ->where('razorpay_order_id', $orderId)
                    ->where('user_id', $userId)
                    ->where('status', WalletPaymentOrder::STATUS_CREATED)
                    ->update(['status' => WalletPaymentOrder::STATUS_FAILED]);
            }
            throw $e;
        }
    }

    public function handleWebhookEvent(string $rawBody, string $signature): void
    {
        $secret = (string) config('services.razorpay.webhook_secret');
        if ($secret === '') {
            Log::warning('rzp.webhook_missing_secret');

            throw new RazorpayDomainException('Webhook not configured', 400);
        }

        try {
            $this->razorpayClient->verifyWebhookSignature($rawBody, $signature, $secret);
        } catch (SignatureVerificationError $e) {
            throw new RazorpayDomainException('Invalid webhook signature', 400, $e);
        }

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            throw new RazorpayDomainException('Invalid webhook payload', 400);
        }

        $event = (string) ($payload['event'] ?? '');

        if ($event === 'payment.captured') {
            $this->handlePaymentCapturedWebhook($payload);

            return;
        }

        if ($event === 'payment.failed') {
            $this->handlePaymentFailedWebhook($payload);

            return;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handlePaymentCapturedWebhook(array $payload): void
    {
        $entity = data_get($payload, 'payload.payment.entity');
        if (! is_array($entity)) {
            throw new RazorpayDomainException('Invalid webhook payload', 400);
        }

        $orderId = (string) ($entity['order_id'] ?? '');
        $paymentId = (string) ($entity['id'] ?? '');
        if ($orderId === '' || $paymentId === '') {
            throw new RazorpayDomainException('Invalid webhook payload', 400);
        }

        try {
            DB::transaction(function () use ($orderId, $paymentId) {
                /** @var WalletPaymentOrder|null $wpo */
                $wpo = WalletPaymentOrder::query()
                    ->where('razorpay_order_id', $orderId)
                    ->lockForUpdate()
                    ->first();

                if (! $wpo) {
                    throw new RazorpayDomainException('Unknown payment order', 400);
                }

                if ($wpo->status === WalletPaymentOrder::STATUS_PAID) {
                    return;
                }

                if ($wpo->status !== WalletPaymentOrder::STATUS_CREATED) {
                    return;
                }

                $this->fulfillCreatedOrder($wpo, $paymentId);
            });
        } catch (RazorpayDomainException $e) {
            if ($e->getStatusCode() === 422 && $e->getMessage() === 'Payment mismatch') {
                WalletPaymentOrder::query()
                    ->where('razorpay_order_id', $orderId)
                    ->where('status', WalletPaymentOrder::STATUS_CREATED)
                    ->update(['status' => WalletPaymentOrder::STATUS_FAILED]);
            }
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function handlePaymentFailedWebhook(array $payload): void
    {
        $entity = data_get($payload, 'payload.payment.entity');
        if (! is_array($entity)) {
            return;
        }

        $orderId = (string) ($entity['order_id'] ?? '');
        if ($orderId === '') {
            return;
        }

        DB::transaction(function () use ($orderId) {
            $wpo = WalletPaymentOrder::query()
                ->where('razorpay_order_id', $orderId)
                ->lockForUpdate()
                ->first();

            if (! $wpo || $wpo->status !== WalletPaymentOrder::STATUS_CREATED) {
                return;
            }

            $wpo->status = WalletPaymentOrder::STATUS_FAILED;
            $wpo->save();
        });
    }

    protected function fulfillCreatedOrder(WalletPaymentOrder $wpo, string $paymentId): void
    {
        $payment = $this->razorpayClient->fetchPayment($paymentId);

        if (! $this->paymentMatchesOrder($payment, $wpo)) {
            throw new RazorpayDomainException('Payment mismatch', 422);
        }

        $wallet = Wallet::query()->firstOrCreate(
            ['user_id' => $wpo->user_id],
            ['balance' => 0, 'status' => 'ACTIVE'],
        );

        $wallet = Wallet::query()
            ->whereKey($wallet->id)
            ->lockForUpdate()
            ->firstOrFail();

        $pack = $wpo->creditPack;
        $meta = $wpo->metadata ?? [];
        $amountInr = (float) ($meta['price_inr'] ?? 0);
        $gstAmount = (float) ($meta['gst_amount_inr'] ?? 0);
        $totalInr = (float) ($meta['total_price_inr'] ?? ($wpo->amount_paise / 100));

        $description = $wpo->credit_pack_id !== null
            ? (($pack?->name ?? 'Pack').' - '.$wpo->credits.' Credits')
            : ('Posting payment - '.$wpo->credits.' credits');

        $transaction = $wallet->addCredits(
            (float) $wpo->credits,
            $description,
            'PURCHASE',
            $wpo->razorpay_order_id,
            'razorpay_order',
            array_merge($meta, [
                'pack_id' => $wpo->credit_pack_id,
                'amount' => $amountInr,
                'gst_amount' => $gstAmount,
                'total_amount' => $totalInr,
                'payment_method' => 'RAZORPAY',
                'payment_status' => 'PAID',
                'razorpay_order_id' => $wpo->razorpay_order_id,
                'razorpay_payment_id' => $paymentId,
            ])
        );

        $wpo->razorpay_payment_id = $paymentId;
        $wpo->wallet_transaction_id = $transaction->id;
        $wpo->status = WalletPaymentOrder::STATUS_PAID;
        $wpo->paid_at = now();
        $wpo->save();
    }

    /**
     * @param  array<string, mixed>  $payment
     */
    protected function paymentMatchesOrder(array $payment, WalletPaymentOrder $wpo): bool
    {
        $apiOrderId = (string) ($payment['order_id'] ?? '');
        $status = (string) ($payment['status'] ?? '');
        $amount = (int) ($payment['amount'] ?? 0);
        $currency = strtoupper((string) ($payment['currency'] ?? ''));

        return $apiOrderId === $wpo->razorpay_order_id
            && $status === 'captured'
            && $amount === $wpo->amount_paise
            && $currency === strtoupper($wpo->currency);
    }
}
