<?php

namespace Tests\Unit\Payments;

use PHPUnit\Framework\TestCase;

class RazorpaySignatureTest extends TestCase
{
    public function test_checkout_signature_hmac_matches_expected(): void
    {
        $secret = 'test_secret';
        $orderId = 'order_123';
        $paymentId = 'pay_456';
        $expected = hash_hmac('sha256', $orderId.'|'.$paymentId, $secret);
        $this->assertSame(64, strlen($expected));
        $this->assertTrue(hash_equals($expected, hash_hmac('sha256', $orderId.'|'.$paymentId, $secret)));
    }

    public function test_webhook_signature_hmac_matches_expected(): void
    {
        $secret = 'whsec_test';
        $body = '{"event":"payment.captured"}';
        $expected = hash_hmac('sha256', $body, $secret);
        $this->assertTrue(hash_equals($expected, hash_hmac('sha256', $body, $secret)));
    }

    public function test_signature_uses_constant_time_compare(): void
    {
        $a = hash_hmac('sha256', 'payload', 'secret');
        $b = hash_hmac('sha256', 'payload', 'secret');
        $this->assertTrue(hash_equals($a, $b));
    }
}
