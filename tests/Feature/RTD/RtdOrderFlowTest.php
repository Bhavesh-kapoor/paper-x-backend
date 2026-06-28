<?php

namespace Tests\Feature\RTD;

use App\Enums\RTDOrderStatus;
use App\Models\RtdOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RtdOrderFlowTest extends TestCase
{
    use RefreshDatabase, RtdTestHelpers;

    /** Golden path: Request → Accept → Pay platform fee → Connected */
    public function test_full_happy_flow(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();

        $product = $this->createProductAsConverter($converter);

        $order = $this->requestOrderAsBrand($brand, $product->id, 50);

        $this->assertEquals(RTDOrderStatus::REQUESTED, $order->status);
        $this->assertNotNull($order->confirmation_deadline);
        $this->assertDatabaseMissing('rtd_payouts', ['order_id' => $order->id]);

        $order->refresh();
        $subtotal = 50 * $order->unit_price;
        $this->assertEqualsWithDelta($subtotal, (float) $order->subtotal, 0.01);
        $this->assertGreaterThan(0, (float) $order->commission_amount);
        $this->assertGreaterThan(0, (float) $order->gst_amount);
        $this->assertEqualsWithDelta(
            (float) $order->commission_amount + (float) $order->gst_amount,
            (float) $order->total_amount,
            0.01
        );
        $this->assertLessThan((float) $order->subtotal, (float) $order->total_amount);

        $this->acceptOrderAsConverter($converter, $order->id);
        $order->refresh();
        $this->assertEquals(RTDOrderStatus::ACCEPTED, $order->status);

        $this->confirmPaymentAsBrand($brand, $order->id);
        $order->refresh();
        $this->assertEquals(RTDOrderStatus::CONNECTED, $order->status);
        $this->assertNotNull($order->paid_at);

        $product = $order->product;
        $product->refresh();
        $this->assertGreaterThanOrEqual(0, $product->decline_count);
    }

    /** Confirm payment twice is idempotent */
    public function test_confirm_payment_twice_idempotent(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);
        $order    = $this->requestOrderAsBrand($brand, $product->id);
        $this->acceptOrderAsConverter($converter, $order->id);

        $this->confirmPaymentAsBrand($brand, $order->id);
        $this->confirmPaymentAsBrand($brand, $order->id);

        $order->refresh();
        $this->assertEquals(RTDOrderStatus::CONNECTED, $order->status);
    }

    /** Brand can place a new order after CONNECTED */
    public function test_brand_can_reorder_after_connected(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);
        $order    = $this->requestOrderAsBrand($brand, $product->id);
        $this->acceptOrderAsConverter($converter, $order->id);
        $this->confirmPaymentAsBrand($brand, $order->id);

        $secondOrder = $this->requestOrderAsBrand($brand, $product->id);
        $this->assertNotEquals($order->id, $secondOrder->id);
        $this->assertEquals(RTDOrderStatus::REQUESTED, $secondOrder->status);
    }

    /** Decline order → status DECLINED, decline_count incremented, product paused */
    public function test_decline_order(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);
        $order    = $this->requestOrderAsBrand($brand, $product->id);

        $this->withHeaders($this->authHeaders($converter))
            ->postJson("/api/v1/rtd/orders/{$order->id}/decline")
            ->assertStatus(200);

        $order->refresh();
        $this->assertEquals(RTDOrderStatus::DECLINED, $order->status);

        $product->refresh();
        $this->assertEquals(1, $product->decline_count);
        $this->assertEquals('paused', $product->status);
    }

    /** Cancel order (ACCEPTED → CANCELLED) */
    public function test_cancel_order_before_payment(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);
        $order    = $this->requestOrderAsBrand($brand, $product->id);
        $this->acceptOrderAsConverter($converter, $order->id);

        $this->withHeaders($this->authHeaders($brand))
            ->postJson("/api/v1/rtd/orders/{$order->id}/cancel")
            ->assertStatus(200);

        $order->refresh();
        $this->assertEquals(RTDOrderStatus::CANCELLED, $order->status);
    }
}
