<?php

namespace Tests\Feature\Wallet;

use App\Models\Wallet;
use App\Models\WalletPaymentOrder;
use App\Models\WalletTransaction;

class WalletRazorpayVerifyTest extends WalletRazorpayTestCase
{
    public function test_verify_unauthenticated_returns_401(): void
    {
        $this->postJson('/api/v1/wallet/payments/razorpay/verify', [
            'razorpay_order_id' => 'order_x',
            'razorpay_payment_id' => 'pay_x',
            'razorpay_signature' => 'sig',
        ])->assertStatus(401);
    }

    public function test_verify_with_invalid_signature_returns_403_and_does_not_credit(): void
    {
        $user = $this->createUserWithToken();
        $pack = $this->seedCreditPack();

        $create = $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ]);
        $orderId = $create->json('data.razorpay_order_id');

        $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/verify', [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => 'pay_test_1',
                'razorpay_signature' => 'invalid',
            ])
            ->assertStatus(403);

        $this->assertSame(0, WalletTransaction::query()->count());
    }

    public function test_verify_with_unknown_order_id_returns_404(): void
    {
        $user = $this->createUserWithToken();

        $sig = $this->checkoutSignature('order_unknown', 'pay_x');
        $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/verify', [
                'razorpay_order_id' => 'order_unknown',
                'razorpay_payment_id' => 'pay_x',
                'razorpay_signature' => $sig,
            ])
            ->assertStatus(404);
    }

    public function test_verify_with_other_users_order_id_returns_404(): void
    {
        $userA = $this->createUserWithToken();
        $userB = $this->createUserWithToken();
        $pack = $this->seedCreditPack();

        $create = $this->postJsonAs($userA, '/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ]);
        $orderId = $create->json('data.razorpay_order_id');
        $paymentId = 'pay_owned_by_a';

        $sig = $this->checkoutSignature($orderId, $paymentId);

        $response = $this->postJsonAs($userB, '/api/v1/wallet/payments/razorpay/verify', [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $sig,
            ]);
        $response->assertStatus(404);
    }

    public function test_verify_success_credits_wallet_once_and_marks_status_paid(): void
    {
        $user = $this->createUserWithToken();
        $pack = $this->seedCreditPack(['credits' => 42]);

        $create = $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ]);
        $orderId = $create->json('data.razorpay_order_id');
        $paymentId = 'pay_ok_1';
        $sig = $this->checkoutSignature($orderId, $paymentId);

        $res = $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/verify', [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $sig,
            ]);

        $res->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.credits_added', 42)
            ->assertJsonPath('data.new_balance', 42);

        $wpo = WalletPaymentOrder::query()->where('razorpay_order_id', $orderId)->firstOrFail();
        $this->assertSame(WalletPaymentOrder::STATUS_PAID, $wpo->status);
        $this->assertNotNull($wpo->wallet_transaction_id);
    }

    public function test_verify_called_twice_is_idempotent_and_does_not_double_credit(): void
    {
        $user = $this->createUserWithToken();
        $pack = $this->seedCreditPack(['credits' => 10]);

        $create = $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ]);
        $orderId = $create->json('data.razorpay_order_id');
        $paymentId = 'pay_idem';
        $sig = $this->checkoutSignature($orderId, $paymentId);

        $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/verify', [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $sig,
            ])
            ->assertStatus(200);

        $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/verify', [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $sig,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.new_balance', 10);

        $this->assertSame(1, Wallet::query()->where('user_id', $user->id)->count());
        $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(10.0, (float) $wallet->balance);
    }

    public function test_verify_after_webhook_already_fulfilled_is_idempotent_success(): void
    {
        $user = $this->createUserWithToken();
        $pack = $this->seedCreditPack(['credits' => 25]);

        $create = $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ]);
        $orderId = $create->json('data.razorpay_order_id');
        $amountPaise = $create->json('data.amount');
        $paymentId = 'pay_web_then_app';

        $payload = $this->razorpayPaymentCapturedPayload($orderId, $paymentId, $amountPaise);
        $raw = json_encode($payload);
        $this->assertIsString($raw);
        $whSig = $this->webhookSignature($raw);

        $this->postRazorpayWebhook($raw, $whSig)->assertStatus(200);

        $sig = $this->checkoutSignature($orderId, $paymentId);
        $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/verify', [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $sig,
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.new_balance', 25);

        $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(25.0, (float) $wallet->balance);
    }

    public function test_verify_returns_422_and_does_not_credit_when_payment_fetch_amount_mismatch(): void
    {
        $user = $this->createUserWithToken();
        $pack = $this->seedCreditPack();

        $create = $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ]);
        $orderId = $create->json('data.razorpay_order_id');
        $correctAmount = $create->json('data.amount');
        $paymentId = 'pay_bad_amount';

        $this->fakeRzp->fetchPaymentOverrides[$paymentId] = [
            'id' => $paymentId,
            'order_id' => $orderId,
            'status' => 'captured',
            'amount' => $correctAmount + 100,
            'currency' => 'INR',
        ];

        $sig = $this->checkoutSignature($orderId, $paymentId);

        $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/verify', [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $sig,
            ])
            ->assertStatus(422);

        $wpo = WalletPaymentOrder::query()->where('razorpay_order_id', $orderId)->firstOrFail();
        $this->assertSame(WalletPaymentOrder::STATUS_FAILED, $wpo->status);
    }

    public function test_verify_returns_422_and_does_not_credit_when_payment_fetch_status_not_captured(): void
    {
        $user = $this->createUserWithToken();
        $pack = $this->seedCreditPack();

        $create = $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ]);
        $orderId = $create->json('data.razorpay_order_id');
        $amountPaise = $create->json('data.amount');
        $paymentId = 'pay_authorized';

        $this->fakeRzp->fetchPaymentOverrides[$paymentId] = [
            'id' => $paymentId,
            'order_id' => $orderId,
            'status' => 'authorized',
            'amount' => $amountPaise,
            'currency' => 'INR',
        ];

        $sig = $this->checkoutSignature($orderId, $paymentId);

        $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/verify', [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $sig,
            ])
            ->assertStatus(422);
    }

    public function test_verify_returns_422_and_does_not_credit_when_payment_fetch_currency_mismatch(): void
    {
        $user = $this->createUserWithToken();
        $pack = $this->seedCreditPack();

        $create = $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ]);
        $orderId = $create->json('data.razorpay_order_id');
        $amountPaise = $create->json('data.amount');
        $paymentId = 'pay_usd';

        $this->fakeRzp->fetchPaymentOverrides[$paymentId] = [
            'id' => $paymentId,
            'order_id' => $orderId,
            'status' => 'captured',
            'amount' => $amountPaise,
            'currency' => 'USD',
        ];

        $sig = $this->checkoutSignature($orderId, $paymentId);

        $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/verify', [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $sig,
            ])
            ->assertStatus(422);
    }

    public function test_verify_returns_422_when_payment_fetch_order_id_does_not_match_local_row(): void
    {
        $user = $this->createUserWithToken();
        $pack = $this->seedCreditPack();

        $create = $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/order', [
                'credit_pack_id' => $pack->id,
            ]);
        $orderId = $create->json('data.razorpay_order_id');
        $amountPaise = $create->json('data.amount');
        $paymentId = 'pay_wrong_order';

        $this->fakeRzp->fetchPaymentOverrides[$paymentId] = [
            'id' => $paymentId,
            'order_id' => 'order_someone_else',
            'status' => 'captured',
            'amount' => $amountPaise,
            'currency' => 'INR',
        ];

        $sig = $this->checkoutSignature($orderId, $paymentId);

        $this->postJsonAs($user, '/api/v1/wallet/payments/razorpay/verify', [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $sig,
            ])
            ->assertStatus(422);
    }
}
