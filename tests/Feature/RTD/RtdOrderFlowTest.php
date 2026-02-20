<?php

namespace Tests\Feature\RTD;

use App\Enums\RTDOrderStatus;
use App\Enums\RTDPayoutStatus;
use App\Models\RtdOrder;
use App\Models\RtdPayout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RtdOrderFlowTest extends TestCase
{
    use RefreshDatabase, RtdTestHelpers;

    /** Golden path: Request → Accept → Pay → In Production → Dispatch → Confirm Delivery */
    public function test_full_happy_flow(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();

        $product = $this->createProductAsConverter($converter);

        // 1) Brand → Request Order (TC-O1)
        $order = $this->requestOrderAsBrand($brand, $product->id, 50);

        $this->assertEquals(RTDOrderStatus::REQUESTED, $order->status);
        $this->assertNotNull($order->confirmation_deadline);
        $this->assertDatabaseHas('rtd_payouts', [
            'order_id'      => $order->id,
            'payout_status' => RTDPayoutStatus::HELD->value,
        ]);

        $order->refresh();
        $subtotal = 50 * $order->unit_price;
        $this->assertEqualsWithDelta($subtotal, (float) $order->subtotal, 0.01);
        $this->assertGreaterThan(0, (float) $order->commission_amount);
        $this->assertGreaterThan(0, (float) $order->gst_amount);
        $this->assertGreaterThan((float) $order->subtotal, (float) $order->total_amount);

        // 2) Converter → Accept (TC-A1)
        $this->acceptOrderAsConverter($converter, $order->id);
        $order->refresh();
        $this->assertEquals(RTDOrderStatus::ACCEPTED, $order->status);

        // 3) Brand → Confirm Payment (TC-PAY1)
        $this->confirmPaymentAsBrand($brand, $order->id);
        $order->refresh();
        $this->assertEquals(RTDOrderStatus::PAID, $order->status);
        $this->assertNotNull($order->paid_at);
        $payout = RtdPayout::where('order_id', $order->id)->first();
        $this->assertEquals(RTDPayoutStatus::HELD, $payout->payout_status);

        // 4) Converter → Mark In Production (TC-D1)
        $this->markInProductionAsConverter($converter, $order->id);
        $order->refresh();
        $this->assertEquals(RTDOrderStatus::IN_PRODUCTION, $order->status);

        // 5) Converter → Dispatch (TC-D3)
        $this->dispatchOrderAsConverter($converter, $order->id);
        $order->refresh();
        $this->assertEquals(RTDOrderStatus::DISPATCHED, $order->status);
        $this->assertNotNull($order->delivery_deadline);
        $this->assertNotNull($order->dispatched_at);
        $this->assertGreaterThan(0, $order->dispatchProofs()->count());

        // 6) Brand → Confirm Delivery (TC-C1)
        $this->confirmDeliveryAsBrand($brand, $order->id);
        $order->refresh();
        $this->assertEquals(RTDOrderStatus::COMPLETED, $order->status);
        $payout->refresh();
        $this->assertEquals(RTDPayoutStatus::RELEASED, $payout->payout_status);
        $this->assertNotNull($payout->released_at);

        $product = $order->product;
        $product->refresh();
        $this->assertGreaterThanOrEqual(0, $product->decline_count);
    }

    /** TC-PAY2: Confirm payment twice is idempotent */
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
        $this->assertEquals(RTDOrderStatus::PAID, $order->status);
        $this->assertEquals(1, RtdPayout::where('order_id', $order->id)->count());
    }

    /** TC-A4: Decline order → status DECLINED, decline_count incremented, product paused */
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

    /** TC-C3: Raise dispute */
    public function test_raise_dispute(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);
        $order    = $this->requestOrderAsBrand($brand, $product->id);
        $this->acceptOrderAsConverter($converter, $order->id);
        $this->confirmPaymentAsBrand($brand, $order->id);
        $this->markInProductionAsConverter($converter, $order->id);
        $this->dispatchOrderAsConverter($converter, $order->id);

        $this->withHeaders($this->authHeaders($brand))
            ->postJson("/api/v1/rtd/orders/{$order->id}/dispute")
            ->assertStatus(200);

        $order->refresh();
        $this->assertEquals(RTDOrderStatus::DISPUTED, $order->status);
        $this->assertEquals(RTDPayoutStatus::HOLD_DISPUTE, $order->payout->payout_status);
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
