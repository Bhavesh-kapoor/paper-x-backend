<?php

namespace Tests\Feature\Wallet;

use App\Models\WalletPaymentOrder;

class WalletRazorpayCreateOrderTest extends WalletRazorpayTestCase
{
    public function test_unauthenticated_create_order_returns_401(): void
    {
        $pack = $this->seedCreditPack();

        $this->postJson('/api/v1/wallet/payments/razorpay/order', [
            'credit_pack_id' => $pack->id,
        ])->assertStatus(401);
    }

    public function test_create_order_requires_valid_credit_pack(): void
    {
        $user = $this->createUserWithToken();

        $this->postJsonAs($user,'/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => 999999,
            ])
            ->assertStatus(422);
    }

    public function test_create_order_with_inactive_pack_returns_400(): void
    {
        $user = $this->createUserWithToken();
        $pack = $this->seedCreditPack(['is_active' => false]);

        $this->postJsonAs($user,'/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ])
            ->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    public function test_create_order_returns_key_id_order_id_amount_paise_currency_inr(): void
    {
        $user = $this->createUserWithToken();
        $pack = $this->seedCreditPack();

        $amountPaise = (int) round((float) (string) $pack->total_price * 100);

        $res = $this->postJsonAs($user,'/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.key_id', 'rzp_test_xxxxx')
            ->assertJsonPath('data.amount', $amountPaise)
            ->assertJsonPath('data.currency', 'INR')
            ->assertJsonStructure(['data' => ['razorpay_order_id', 'receipt', 'pack']]);
    }

    public function test_create_order_persists_wallet_payment_order_row_with_status_created(): void
    {
        $user = $this->createUserWithToken();
        $pack = $this->seedCreditPack();

        $res = $this->postJsonAs($user,'/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ]);

        $orderId = $res->json('data.razorpay_order_id');
        $this->assertNotEmpty($orderId);

        $this->assertDatabaseHas('wallet_payment_orders', [
            'user_id' => $user->id,
            'credit_pack_id' => $pack->id,
            'razorpay_order_id' => $orderId,
            'status' => WalletPaymentOrder::STATUS_CREATED,
        ]);
    }

    public function test_create_order_uses_pack_total_price_authoritatively_even_if_request_has_other_fields(): void
    {
        $user = $this->createUserWithToken();
        $pack = $this->seedCreditPack(['price' => 500, 'gst_percentage' => 18]);

        $expectedPaise = (int) round((float) (string) $pack->total_price * 100);

        $res = $this->postJsonAs($user,'/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
                'extra' => 'ignored',
            ]);

        $res->assertStatus(201);
        $this->assertSame($expectedPaise, $res->json('data.amount'));

        $wpo = WalletPaymentOrder::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame($expectedPaise, $wpo->amount_paise);
    }

    public function test_create_order_records_user_and_pack_in_metadata(): void
    {
        $user = $this->createUserWithToken();
        $pack = $this->seedCreditPack(['name' => 'Meta Pack']);

        $this->postJsonAs($user,'/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ])
            ->assertStatus(201);

        $wpo = WalletPaymentOrder::query()->where('user_id', $user->id)->firstOrFail();
        $meta = $wpo->metadata;
        $this->assertSame($pack->id, $meta['pack_id']);
        $this->assertSame('Meta Pack', $meta['pack_name']);
        $this->assertArrayHasKey('total_price_inr', $meta);
    }

    public function test_create_order_is_rate_limited_after_10_requests_in_a_minute_returns_429(): void
    {
        $user = $this->createUserWithToken();
        $pack = $this->seedCreditPack();

        for ($i = 0; $i < 10; $i++) {
            $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ])
                ->assertStatus(201);
        }

        $this->postJsonAs($user,'/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ])
            ->assertStatus(429);
    }
}
