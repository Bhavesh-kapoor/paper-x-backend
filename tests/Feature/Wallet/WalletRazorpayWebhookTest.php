<?php

namespace Tests\Feature\Wallet;

use App\Models\Wallet;
use App\Models\WalletPaymentOrder;

class WalletRazorpayWebhookTest extends WalletRazorpayTestCase
{
    public function test_webhook_with_invalid_signature_returns_400_and_does_not_credit(): void
    {
        $user = $this->createUserWithToken();
        $pack = $this->seedCreditPack();

        $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ])
            ->assertStatus(201);

        $wpo = WalletPaymentOrder::query()->where('user_id', $user->id)->firstOrFail();
        $payload = $this->razorpayPaymentCapturedPayload($wpo->razorpay_order_id, 'pay_wh', $wpo->amount_paise);
        $raw = json_encode($payload);
        $this->assertIsString($raw);

        $this->postRazorpayWebhook($raw, 'bad_sig')->assertStatus(400);

        $this->assertNull(Wallet::query()->where('user_id', $user->id)->first());
    }

    public function test_webhook_payment_captured_credits_wallet_when_app_never_called_verify(): void
    {
        $user = $this->createUserWithToken();
        $pack = $this->seedCreditPack(['credits' => 77]);

        $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ])
            ->assertStatus(201);

        $wpo = WalletPaymentOrder::query()->where('user_id', $user->id)->firstOrFail();
        $paymentId = 'pay_webhook_only';
        $payload = $this->razorpayPaymentCapturedPayload($wpo->razorpay_order_id, $paymentId, $wpo->amount_paise);
        $raw = json_encode($payload);
        $this->assertIsString($raw);
        $sig = $this->webhookSignature($raw);

        $this->postRazorpayWebhook($raw, $sig)->assertStatus(200);

        $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(77.0, (float) $wallet->balance);
        $wpo->refresh();
        $this->assertSame(WalletPaymentOrder::STATUS_PAID, $wpo->status);
    }

    public function test_webhook_payment_captured_is_idempotent_when_already_paid(): void
    {
        $user = $this->createUserWithToken();
        $pack = $this->seedCreditPack(['credits' => 5]);

        $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ]);

        $wpo = WalletPaymentOrder::query()->where('user_id', $user->id)->firstOrFail();
        $paymentId = 'pay_double_webhook';
        $payload = $this->razorpayPaymentCapturedPayload($wpo->razorpay_order_id, $paymentId, $wpo->amount_paise);
        $raw = json_encode($payload);
        $this->assertIsString($raw);
        $sig = $this->webhookSignature($raw);

        $this->postRazorpayWebhook($raw, $sig)->assertStatus(200);

        $this->postRazorpayWebhook($raw, $sig)->assertStatus(200);

        $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(5.0, (float) $wallet->balance);
    }

    public function test_webhook_payment_failed_marks_order_failed_and_does_not_credit(): void
    {
        $user = $this->createUserWithToken();
        $pack = $this->seedCreditPack();

        $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ]);

        $wpo = WalletPaymentOrder::query()->where('user_id', $user->id)->firstOrFail();
        $payload = $this->razorpayPaymentFailedPayload($wpo->razorpay_order_id, 'pay_fail');
        $raw = json_encode($payload);
        $this->assertIsString($raw);
        $sig = $this->webhookSignature($raw);

        $this->postRazorpayWebhook($raw, $sig)->assertStatus(200);

        $wpo->refresh();
        $this->assertSame(WalletPaymentOrder::STATUS_FAILED, $wpo->status);
        $this->assertNull(Wallet::query()->where('user_id', $user->id)->first());
    }

    public function test_webhook_unknown_order_id_returns_400_without_side_effects(): void
    {
        $payload = $this->razorpayPaymentCapturedPayload('order_nonexistent', 'pay_x', 11800);
        $raw = json_encode($payload);
        $this->assertIsString($raw);
        $sig = $this->webhookSignature($raw);

        $this->postRazorpayWebhook($raw, $sig)->assertStatus(400);

        $this->assertSame(0, WalletPaymentOrder::query()->count());
    }
}
