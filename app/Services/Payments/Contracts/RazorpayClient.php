<?php

namespace App\Services\Payments\Contracts;

interface RazorpayClient
{
    /**
     * @return array{id: string, amount: int, currency: string, receipt?: string, ...}
     */
    public function createOrder(array $payload): array;

    /**
     * @return array{id: string, order_id: string|null, status: string, amount: int, currency: string, ...}
     */
    public function fetchPayment(string $paymentId): array;

    /**
     * @param  array{razorpay_order_id: string, razorpay_payment_id: string, razorpay_signature: string}  $attributes
     *
     * @throws \Razorpay\Api\Errors\SignatureVerificationError
     */
    public function verifyPaymentSignature(array $attributes): void;

    /**
     * @throws \Razorpay\Api\Errors\SignatureVerificationError
     */
    public function verifyWebhookSignature(string $rawBody, string $signature, string $secret): void;
}
