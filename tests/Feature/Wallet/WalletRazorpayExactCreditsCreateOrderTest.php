<?php

namespace Tests\Feature\Wallet;

use App\Models\WalletPaymentOrder;

class WalletRazorpayExactCreditsCreateOrderTest extends WalletRazorpayTestCase
{
    public function test_exact_credits_unauthenticated_returns_401(): void
    {
        $this->postJson('/api/v1/wallet/payments/razorpay/exact-credits-order', [
            'credits' => 60,
        ])->assertStatus(401);
    }

    public function test_exact_credits_requires_credits(): void
    {
        $user = $this->createUserWithToken();

        $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/exact-credits-order', [])
            ->assertStatus(422);
    }

    public function test_exact_credits_rejects_over_max(): void
    {
        config(['wallet_razorpay.exact_credits_max' => 100]);
        $user = $this->createUserWithToken();

        $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/exact-credits-order', [
            'credits' => 101,
        ])->assertStatus(422);
    }

    public function test_exact_credits_amount_paise_matches_inr_per_credit(): void
    {
        config([
            'wallet_razorpay.inr_per_credit' => 1.0,
            'wallet_razorpay.exact_credits_max' => 500,
        ]);
        $user = $this->createUserWithToken();

        $res = $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/exact-credits-order', [
            'credits' => 60,
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.amount', 6000)
            ->assertJsonPath('data.pack.credits', 60)
            ->assertJsonPath('data.pack.total_price', 60);

        $orderId = $res->json('data.razorpay_order_id');
        $this->assertDatabaseHas('wallet_payment_orders', [
            'user_id' => $user->id,
            'razorpay_order_id' => $orderId,
            'amount_paise' => 6000,
            'credits' => 60,
            'status' => WalletPaymentOrder::STATUS_CREATED,
        ]);

        $wpo = WalletPaymentOrder::query()->where('razorpay_order_id', $orderId)->firstOrFail();
        $this->assertNull($wpo->credit_pack_id);
        $this->assertSame('exact_credits', $wpo->metadata['order_kind']);
    }

    public function test_exact_credits_respects_custom_inr_per_credit(): void
    {
        config([
            'wallet_razorpay.inr_per_credit' => 2.5,
            'wallet_razorpay.exact_credits_max' => 500,
        ]);
        $user = $this->createUserWithToken();

        $res = $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/exact-credits-order', [
            'credits' => 40,
        ]);

        $res->assertStatus(201);
        $this->assertSame(10000, $res->json('data.amount'));
        $this->assertSame(100, $res->json('data.pack.total_price'));
    }

    public function test_verify_exact_credits_order_credits_wallet(): void
    {
        config([
            'wallet_razorpay.inr_per_credit' => 1.0,
            'wallet_razorpay.exact_credits_max' => 500,
        ]);
        $user = $this->createUserWithToken();

        $create = $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/exact-credits-order', [
            'credits' => 60,
        ]);
        $orderId = $create->json('data.razorpay_order_id');
        $paymentId = 'pay_exact_1';
        $sig = $this->checkoutSignature($orderId, $paymentId);

        $res = $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/verify', [
            'razorpay_order_id' => $orderId,
            'razorpay_payment_id' => $paymentId,
            'razorpay_signature' => $sig,
        ]);

        $res->assertStatus(200)
            ->assertJsonPath('data.credits_added', 60)
            ->assertJsonPath('data.new_balance', 60);
    }
}
