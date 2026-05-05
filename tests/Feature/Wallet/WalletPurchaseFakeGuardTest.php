<?php

namespace Tests\Feature\Wallet;

use App\Models\CreditPack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletPurchaseFakeGuardTest extends TestCase
{
    use RefreshDatabase;
    use WalletTestHelpers;

    public function test_wallet_purchase_returns_410_when_fake_payments_disabled(): void
    {
        config(['app.fake_payments' => false]);

        $user = $this->createUserWithToken();
        $pack = CreditPack::query()->create([
            'name' => 'P',
            'slug' => 'p-guard-'.uniqid(),
            'credits' => 10,
            'price' => 100,
            'gst_percentage' => 18,
            'validity' => 'Lifetime',
            'is_best_value' => false,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->postJsonAs($user, '/api/v1/wallet/purchase', [
                'credit_pack_id' => $pack->id,
                'payment_method' => 'UPI',
            ])
            ->assertStatus(410)
            ->assertJsonPath('success', false);
    }

    public function test_wallet_purchase_succeeds_when_fake_payments_enabled(): void
    {
        config(['app.fake_payments' => true]);

        $user = $this->createUserWithToken();
        $pack = CreditPack::query()->create([
            'name' => 'P',
            'slug' => 'p-fake-'.uniqid(),
            'credits' => 10,
            'price' => 100,
            'gst_percentage' => 18,
            'validity' => 'Lifetime',
            'is_best_value' => false,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->postJsonAs($user, '/api/v1/wallet/purchase', [
                'credit_pack_id' => $pack->id,
                'payment_method' => 'UPI',
            ])
            ->assertStatus(200)
            ->assertJsonPath('success', true);
    }
}
