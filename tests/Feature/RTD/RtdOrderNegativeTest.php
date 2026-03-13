<?php

namespace Tests\Feature\RTD;

use App\Enums\RTDOrderStatus;
use App\Models\RtdOrder;
use App\Models\RtdProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RtdOrderNegativeTest extends TestCase
{
    use RefreshDatabase, RtdTestHelpers;

    /** TC-A2: Accept twice → second fails */
    public function test_accept_twice_fails(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);
        $order    = $this->requestOrderAsBrand($brand, $product->id);

        $this->acceptOrderAsConverter($converter, $order->id);

        $response = $this->withHeaders($this->authHeaders($converter))
            ->postJson("/api/v1/rtd/orders/{$order->id}/accept");

        $response->assertStatus(422);
        $order->refresh();
        $this->assertEquals(RTDOrderStatus::ACCEPTED, $order->status);
    }

    /** TC-PAY3: Confirm payment before accept → invalid transition */
    public function test_confirm_payment_before_accept_fails(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);
        $order    = $this->requestOrderAsBrand($brand, $product->id);

        $response = $this->withHeaders($this->authHeaders($brand))
            ->postJson("/api/v1/rtd/orders/{$order->id}/confirm-payment", ['order_id' => $order->id]);

        $this->assertContains($response->status(), [400, 422]);
        $order->refresh();
        $this->assertEquals(RTDOrderStatus::REQUESTED, $order->status);
    }

    /** TC-D2: Mark in production before payment → invalid transition */
    public function test_mark_in_production_before_payment_fails(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);
        $order    = $this->requestOrderAsBrand($brand, $product->id);
        $this->acceptOrderAsConverter($converter, $order->id);
        // Not confirming payment

        $response = $this->withHeaders($this->authHeaders($converter))
            ->postJson("/api/v1/rtd/orders/{$order->id}/in-production");

        $this->assertContains($response->status(), [400, 422]);
        $order->refresh();
        $this->assertEquals(RTDOrderStatus::ACCEPTED, $order->status);
    }

    /** TC-D4: Dispatch before production → invalid transition */
    public function test_dispatch_before_production_fails(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);
        $order    = $this->requestOrderAsBrand($brand, $product->id);
        $this->acceptOrderAsConverter($converter, $order->id);
        $this->confirmPaymentAsBrand($brand, $order->id);
        // Not marking in production

        $response = $this->withHeaders($this->authHeaders($converter))
            ->postJson("/api/v1/rtd/orders/{$order->id}/dispatch", [
                'proof_type'      => 'tracking_number',
                'tracking_number' => 'TRK',
            ]);

        $this->assertContains($response->status(), [400, 422]);
        $order->refresh();
        $this->assertEquals(RTDOrderStatus::PAID, $order->status);
    }

    /** Cancel after payment → invalid (only ACCEPTED can be cancelled) */
    public function test_cancel_after_payment_fails(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);
        $order    = $this->requestOrderAsBrand($brand, $product->id);
        $this->acceptOrderAsConverter($converter, $order->id);
        $this->confirmPaymentAsBrand($brand, $order->id);

        $response = $this->withHeaders($this->authHeaders($brand))
            ->postJson("/api/v1/rtd/orders/{$order->id}/cancel");

        $this->assertContains($response->status(), [400, 422]);
        $order->refresh();
        $this->assertEquals(RTDOrderStatus::PAID, $order->status);
    }

    /** TC-O2: Quantity < MOQ */
    public function test_order_quantity_below_moq_fails(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);

        $response = $this->withHeaders($this->authHeaders($brand))
            ->postJson('/api/v1/rtd/orders', [
                'product_id' => $product->id,
                'quantity'   => 5,
            ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('rtd_orders', ['product_id' => $product->id]);
    }

    /** TC-O3: Quantity not in any slab */
    public function test_order_quantity_not_in_slab_fails(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);
        $product->priceSlabs()->delete();
        $product->priceSlabs()->create([
            'min_qty'        => 10,
            'max_qty'        => 100,
            'price_per_unit' => 50,
        ]);

        $response = $this->withHeaders($this->authHeaders($brand))
            ->postJson('/api/v1/rtd/orders', [
                'product_id' => $product->id,
                'quantity'   => 150,
            ]);

        $response->assertStatus(422);
    }

    /** TC-O5: Product paused → order request fails */
    public function test_order_on_paused_product_fails(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);
        $product->update(['status' => 'paused']);

        $response = $this->withHeaders($this->authHeaders($brand))
            ->postJson('/api/v1/rtd/orders', [
                'product_id' => $product->id,
                'quantity'   => 50,
            ]);

        $response->assertStatus(422);
    }
}
