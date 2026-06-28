<?php

namespace Tests\Feature\RTD;

use App\Enums\RTDLeadTime;
use App\Models\RtdOrder;
use App\Models\RtdProduct;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

trait RtdTestHelpers
{
    protected function createConverterUser(): User
    {
        return User::factory()->create([
            'primary_role' => 'converter',
            'email'        => 'converter-rtd@test.com',
        ]);
    }

    protected function createBrandUser(): User
    {
        return User::factory()->create([
            'primary_role' => 'brand',
            'email'       => 'brand-rtd@test.com',
        ]);
    }

    protected function authHeaders(User $user): array
    {
        $token = $user->createToken('rtd-test')->plainTextToken;

        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ];
    }

    protected function createProductAsConverter(User $converter, array $overrides = []): RtdProduct
    {
        Bus::fake([\App\Jobs\HandleOrderAcceptanceTimeout::class, \App\Jobs\HandleAutoOrderClosure::class]);
        $data = array_merge([
            'category'          => 'Box',
            'product_name'      => 'Test Box',
            'lead_time'         => RTDLeadTime::H24->value,
            'moq'               => 10,
            'max_capacity'      => 1000,
            'base_price'        => 100,
            'buy_now_enabled'   => true,
            'delivery_geography'=> 'North India',
            'price_slabs'       => [
                ['min_qty' => 10, 'max_qty' => 99, 'price_per_unit' => 100],
                ['min_qty' => 100, 'max_qty' => 500, 'price_per_unit' => 90],
                ['min_qty' => 501, 'max_qty' => 1000, 'price_per_unit' => 80],
            ],
        ], $overrides);

        $response = $this->withHeaders($this->authHeaders($converter))
            ->postJson('/api/v1/rtd/products', $data);

        $response->assertStatus(201);
        $id = $response->json('data.id');
        return RtdProduct::with('priceSlabs')->findOrFail($id);
    }

    protected function requestOrderAsBrand(User $brand, int $productId, int $quantity = 50): RtdOrder
    {
        Bus::fake([\App\Jobs\HandleOrderAcceptanceTimeout::class, \App\Jobs\HandleAutoOrderClosure::class]);
        $response = $this->withHeaders($this->authHeaders($brand))
            ->postJson('/api/v1/rtd/orders', [
                'product_id' => $productId,
                'quantity'   => $quantity,
            ]);

        $response->assertStatus(201);
        $id = $response->json('data.id');
        return RtdOrder::with(['product'])->findOrFail($id);
    }

    protected function acceptOrderAsConverter(User $converter, int $orderId): void
    {
        $this->withHeaders($this->authHeaders($converter))
            ->postJson("/api/v1/rtd/orders/{$orderId}/accept")
            ->assertStatus(200);
    }

    protected function confirmPaymentAsBrand(User $brand, int $orderId): void
    {
        $this->withHeaders($this->authHeaders($brand))
            ->postJson("/api/v1/rtd/orders/{$orderId}/confirm-payment", [
                'order_id' => $orderId,
            ])
            ->assertStatus(200);
    }

}
