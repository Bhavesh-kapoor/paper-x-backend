<?php

namespace App\Services\Payments;

use App\Services\Payments\Contracts\RazorpayClient;
use Razorpay\Api\Errors\SignatureVerificationError;

/**
 * Test double: deterministic orders and configurable fetchPayment / signature behaviour.
 */
class FakeRazorpayClient implements RazorpayClient
{
    /** @var list<array<string, mixed>> */
    public array $createdOrders = [];

    /** @var array<string, array<string, mixed>> */
    public array $fetchPaymentOverrides = [];

    public bool $failPaymentSignature = false;

    public bool $failWebhookSignature = false;

    public string $keySecret = 'whsec_test_fake';

    private int $orderSeq = 0;

    public function createOrder(array $payload): array
    {
        $this->orderSeq++;
        $id = 'order_fake_'.$this->orderSeq;
        $row = array_merge($payload, ['id' => $id]);
        $this->createdOrders[] = $row;

        return [
            'id' => $id,
            'amount' => (int) $payload['amount'],
            'currency' => (string) $payload['currency'],
            'receipt' => (string) ($payload['receipt'] ?? ''),
        ];
    }

    public function fetchPayment(string $paymentId): array
    {
        if (isset($this->fetchPaymentOverrides[$paymentId])) {
            return $this->fetchPaymentOverrides[$paymentId];
        }

        $last = end($this->createdOrders) ?: [];
        $orderId = (string) ($last['id'] ?? 'order_fake_unknown');

        return [
            'id' => $paymentId,
            'order_id' => $orderId,
            'status' => 'captured',
            'amount' => (int) ($last['amount'] ?? 0),
            'currency' => (string) ($last['currency'] ?? 'INR'),
        ];
    }

    public function verifyPaymentSignature(array $attributes): void
    {
        if ($this->failPaymentSignature) {
            throw new SignatureVerificationError('Invalid signature passed');
        }
        $secret = config('services.razorpay.key_secret') ?: 'test_secret';
        $payload = $attributes['razorpay_order_id'].'|'.$attributes['razorpay_payment_id'];
        $expected = hash_hmac('sha256', $payload, $secret);
        if (! hash_equals($expected, $attributes['razorpay_signature'])) {
            throw new SignatureVerificationError('Invalid signature passed');
        }
    }

    public function verifyWebhookSignature(string $rawBody, string $signature, string $secret): void
    {
        if ($this->failWebhookSignature) {
            throw new SignatureVerificationError('Invalid signature passed');
        }
        $expected = hash_hmac('sha256', $rawBody, $secret);
        if (! hash_equals($expected, $signature)) {
            throw new SignatureVerificationError('Invalid signature passed');
        }
    }
}
