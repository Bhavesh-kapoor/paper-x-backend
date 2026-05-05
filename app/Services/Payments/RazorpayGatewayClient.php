<?php

namespace App\Services\Payments;

use App\Services\Payments\Contracts\RazorpayClient;
use Razorpay\Api\Api;

class RazorpayGatewayClient implements RazorpayClient
{
    public function __construct(
        protected Api $api,
    ) {}

    public function createOrder(array $payload): array
    {
        $order = $this->api->order->create($payload);

        return $order->toArray();
    }

    public function fetchPayment(string $paymentId): array
    {
        $payment = $this->api->payment->fetch($paymentId);

        return $payment->toArray();
    }

    public function verifyPaymentSignature(array $attributes): void
    {
        $this->api->utility->verifyPaymentSignature($attributes);
    }

    public function verifyWebhookSignature(string $rawBody, string $signature, string $secret): void
    {
        $this->api->utility->verifyWebhookSignature($rawBody, $signature, $secret);
    }
}
