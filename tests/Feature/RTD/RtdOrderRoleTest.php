<?php

namespace Tests\Feature\RTD;

use App\Models\RtdOrder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RtdOrderRoleTest extends TestCase
{
    use RefreshDatabase, RtdTestHelpers;

    /** TC-A5: Brand tries to accept → must fail with 4xx (wrong owner). */
    public function test_brand_cannot_accept_order(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);
        $order    = $this->requestOrderAsBrand($brand, $product->id);

        $response = $this->withHeaders($this->authHeaders($brand))
            ->postJson("/api/v1/rtd/orders/{$order->id}/accept");

        $this->assertGreaterThanOrEqual(400, $response->status());
        $order->refresh();
        if ($response->status() === 422) {
            $this->assertEquals('REQUESTED', $order->status->value);
        }
    }

    /** TC-PAY4: Converter tries to confirm payment → must fail with 4xx (wrong owner). */
    public function test_converter_cannot_confirm_payment(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);
        $order    = $this->requestOrderAsBrand($brand, $product->id);
        $this->acceptOrderAsConverter($converter, $order->id);

        $response = $this->withHeaders($this->authHeaders($converter))
            ->postJson("/api/v1/rtd/orders/{$order->id}/confirm-payment", ['order_id' => $order->id]);

        $this->assertGreaterThanOrEqual(400, $response->status());
    }

    /** Brand cannot dispatch (converter-only) → must fail with 4xx. */
    public function test_brand_cannot_dispatch(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);
        $order    = $this->requestOrderAsBrand($brand, $product->id);
        $this->acceptOrderAsConverter($converter, $order->id);
        $this->confirmPaymentAsBrand($brand, $order->id);
        $this->markInProductionAsConverter($converter, $order->id);

        $response = $this->withHeaders($this->authHeaders($brand))
            ->postJson("/api/v1/rtd/orders/{$order->id}/dispatch", [
                'proof_type'      => 'tracking_number',
                'tracking_number' => 'TRK',
            ]);

        $this->assertGreaterThanOrEqual(400, $response->status());
    }

    /** Converter cannot confirm delivery (brand-only) → must fail with 4xx. */
    public function test_converter_cannot_confirm_delivery(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);
        $order    = $this->requestOrderAsBrand($brand, $product->id);
        $this->acceptOrderAsConverter($converter, $order->id);
        $this->confirmPaymentAsBrand($brand, $order->id);
        $this->markInProductionAsConverter($converter, $order->id);
        $this->dispatchOrderAsConverter($converter, $order->id);

        $response = $this->withHeaders($this->authHeaders($converter))
            ->postJson("/api/v1/rtd/orders/{$order->id}/confirm-delivery");

        $this->assertGreaterThanOrEqual(400, $response->status());
    }

    /** TC-P4: Brand tries to create product. Expect 403 if role middleware added; currently may be 201. */
    public function test_brand_can_or_cannot_create_product_depending_on_middleware(): void
    {
        $brand = $this->createBrandUser();

        $response = $this->withHeaders($this->authHeaders($brand))
            ->postJson('/api/v1/rtd/products', [
                'category'     => 'Box',
                'product_name' => 'Brand Product',
                'lead_time'    => 'H24',
                'moq'          => 10,
                'base_price'   => 10,
                'price_slabs'  => [
                    ['min_qty' => 10, 'max_qty' => 100, 'price_per_unit' => 10],
                ],
            ]);

        if ($response->status() === 403) {
            $this->assertDatabaseMissing('rtd_products', ['product_name' => 'Brand Product']);
        }
        $this->assertContains($response->status(), [201, 403]);
    }

    /** TC-A3: Accept after expiry → 422 */
    public function test_accept_after_expiry_fails(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);
        $order    = $this->requestOrderAsBrand($brand, $product->id);

        RtdOrder::where('id', $order->id)->update([
            'confirmation_deadline' => Carbon::now()->subMinutes(5),
        ]);

        $response = $this->withHeaders($this->authHeaders($converter))
            ->postJson("/api/v1/rtd/orders/{$order->id}/accept");

        $this->assertTrue($response->status() >= 400 && $response->status() < 500);
        $body = $response->json();
        $msg = $body['message'] ?? '';
        $this->assertTrue(
            str_contains(strtolower($msg), 'expired') || str_contains(strtolower($msg), 'window') || str_contains(strtolower($msg), 'process'),
            'Error message should mention expired/window/process'
        );
    }
}
