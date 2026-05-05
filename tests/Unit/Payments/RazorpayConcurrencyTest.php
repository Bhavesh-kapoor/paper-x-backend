<?php

namespace Tests\Unit\Payments;

use App\Models\CreditPack;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletPaymentOrder;
use App\Services\Payments\Contracts\RazorpayClient;
use App\Services\Payments\FakeRazorpayClient;
use App\Services\Payments\RazorpayPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RazorpayConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_concurrent_verifies_for_same_order_only_credit_once(): void
    {
        $fake = new FakeRazorpayClient;
        $this->app->instance(RazorpayClient::class, $fake);
        config([
            'services.razorpay.key_id' => 'rzp_test',
            'services.razorpay.key_secret' => 'test_secret_key',
            'services.razorpay.webhook_secret' => 'whsec_test',
        ]);

        $user = User::factory()->create(['primary_role' => 'dealer']);
        $pack = CreditPack::query()->create([
            'name' => 'P',
            'slug' => 'p-conc-1',
            'credits' => 20,
            'price' => 100,
            'gst_percentage' => 18,
            'validity' => 'Lifetime',
            'is_best_value' => false,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $amountPaise = (int) round((float) (string) $pack->total_price * 100);
        $fake->createOrder([
            'amount' => $amountPaise,
            'currency' => 'INR',
            'receipt' => 'r1',
            'payment_capture' => 1,
            'notes' => [],
        ]);
        $orderId = $fake->createdOrders[0]['id'];

        WalletPaymentOrder::query()->create([
            'user_id' => $user->id,
            'credit_pack_id' => $pack->id,
            'razorpay_order_id' => $orderId,
            'receipt' => 'r1',
            'amount_paise' => $amountPaise,
            'currency' => 'INR',
            'credits' => $pack->credits,
            'status' => WalletPaymentOrder::STATUS_CREATED,
            'metadata' => [
                'pack_id' => $pack->id,
                'pack_name' => $pack->name,
                'price_inr' => (float) (string) $pack->price,
                'gst_percentage' => (float) (string) $pack->gst_percentage,
                'gst_amount_inr' => (float) (string) $pack->gst_amount,
                'total_price_inr' => (float) (string) $pack->total_price,
                'credits' => $pack->credits,
            ],
        ]);

        $paymentId = 'pay_same_order';
        $sig = hash_hmac('sha256', $orderId.'|'.$paymentId, 'test_secret_key');

        $service = app(RazorpayPaymentService::class);
        $service->verifyAndFulfill($user->id, $orderId, $paymentId, $sig);

        try {
            $service->verifyAndFulfill($user->id, $orderId, $paymentId, $sig);
        } catch (\Throwable) {
            $this->fail('Second verify should be idempotent');
        }

        $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(20.0, (float) $wallet->balance);
    }

    public function test_two_concurrent_verifies_for_different_orders_same_user_do_not_lose_balance_update(): void
    {
        $fake = new FakeRazorpayClient;
        $this->app->instance(RazorpayClient::class, $fake);
        config([
            'services.razorpay.key_id' => 'rzp_test',
            'services.razorpay.key_secret' => 'test_secret_key',
            'services.razorpay.webhook_secret' => 'whsec_test',
        ]);

        $user = User::factory()->create(['primary_role' => 'dealer']);

        $packA = CreditPack::query()->create([
            'name' => 'A', 'slug' => 'pa-'.uniqid(), 'credits' => 30, 'price' => 200, 'gst_percentage' => 18,
            'validity' => 'Lifetime', 'is_best_value' => false, 'is_active' => true, 'sort_order' => 0,
        ]);
        $packB = CreditPack::query()->create([
            'name' => 'B', 'slug' => 'pb-'.uniqid(), 'credits' => 40, 'price' => 300, 'gst_percentage' => 18,
            'validity' => 'Lifetime', 'is_best_value' => false, 'is_active' => true, 'sort_order' => 0,
        ]);

        $fake->createOrder([
            'amount' => (int) round((float) (string) $packA->total_price * 100),
            'currency' => 'INR', 'receipt' => 'ra', 'payment_capture' => 1, 'notes' => [],
        ]);
        $orderA = $fake->createdOrders[0]['id'];
        $fake->createOrder([
            'amount' => (int) round((float) (string) $packB->total_price * 100),
            'currency' => 'INR', 'receipt' => 'rb', 'payment_capture' => 1, 'notes' => [],
        ]);
        $orderB = $fake->createdOrders[1]['id'];

        $amountA = (int) round((float) (string) $packA->total_price * 100);
        $amountB = (int) round((float) (string) $packB->total_price * 100);

        WalletPaymentOrder::query()->create([
            'user_id' => $user->id,
            'credit_pack_id' => $packA->id,
            'razorpay_order_id' => $orderA,
            'receipt' => 'ra',
            'amount_paise' => $amountA,
            'currency' => 'INR',
            'credits' => $packA->credits,
            'status' => WalletPaymentOrder::STATUS_CREATED,
            'metadata' => [
                'pack_id' => $packA->id,
                'pack_name' => $packA->name,
                'price_inr' => (float) (string) $packA->price,
                'gst_percentage' => (float) (string) $packA->gst_percentage,
                'gst_amount_inr' => (float) (string) $packA->gst_amount,
                'total_price_inr' => (float) (string) $packA->total_price,
                'credits' => $packA->credits,
            ],
        ]);
        WalletPaymentOrder::query()->create([
            'user_id' => $user->id,
            'credit_pack_id' => $packB->id,
            'razorpay_order_id' => $orderB,
            'receipt' => 'rb',
            'amount_paise' => $amountB,
            'currency' => 'INR',
            'credits' => $packB->credits,
            'status' => WalletPaymentOrder::STATUS_CREATED,
            'metadata' => [
                'pack_id' => $packB->id,
                'pack_name' => $packB->name,
                'price_inr' => (float) (string) $packB->price,
                'gst_percentage' => (float) (string) $packB->gst_percentage,
                'gst_amount_inr' => (float) (string) $packB->gst_amount,
                'total_price_inr' => (float) (string) $packB->total_price,
                'credits' => $packB->credits,
            ],
        ]);

        $payA = 'pay_a';
        $payB = 'pay_b';
        $fake->fetchPaymentOverrides[$payA] = [
            'id' => $payA, 'order_id' => $orderA, 'status' => 'captured', 'amount' => $amountA, 'currency' => 'INR',
        ];
        $fake->fetchPaymentOverrides[$payB] = [
            'id' => $payB, 'order_id' => $orderB, 'status' => 'captured', 'amount' => $amountB, 'currency' => 'INR',
        ];

        $service = app(RazorpayPaymentService::class);
        $sigA = hash_hmac('sha256', $orderA.'|'.$payA, 'test_secret_key');
        $sigB = hash_hmac('sha256', $orderB.'|'.$payB, 'test_secret_key');

        $service->verifyAndFulfill($user->id, $orderA, $payA, $sigA);
        $service->verifyAndFulfill($user->id, $orderB, $payB, $sigB);

        $wallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertEquals(70.0, (float) $wallet->balance);
    }
}
