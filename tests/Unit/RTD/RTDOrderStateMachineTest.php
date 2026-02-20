<?php

namespace Tests\Unit\RTD;

use App\Enums\RTDOrderStatus;
use App\Models\RtdOrder;
use App\Models\RtdProduct;
use App\Models\RtdPayout;
use App\Models\User;
use App\StateMachines\RTDOrderStateMachine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class RTDOrderStateMachineTest extends TestCase
{
    use RefreshDatabase;

    private RTDOrderStateMachine $machine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->machine = new RTDOrderStateMachine();
    }

    private function createOrderWithStatus(RTDOrderStatus $status): RtdOrder
    {
        $user = User::factory()->create();
        $product = RtdProduct::create([
            'converter_id'    => $user->id,
            'category'       => 'Box',
            'product_name'   => 'Test',
            'lead_time'      => 'H24',
            'moq'            => 10,
            'base_price'     => 10,
            'buy_now_enabled'=> true,
            'status'         => 'active',
        ]);
        $product->priceSlabs()->create(['min_qty' => 10, 'max_qty' => 100, 'price_per_unit' => 10]);

        $order = RtdOrder::create([
            'product_id'   => $product->id,
            'brand_id'     => $user->id,
            'converter_id' => $user->id,
            'quantity'     => 10,
            'unit_price'   => 10,
            'subtotal'     => 100,
            'commission_percent' => 9,
            'commission_amount'  => 9,
            'gst_percent'  => 18,
            'gst_amount'   => 19.62,
            'total_amount' => 128.62,
            'status'       => $status,
        ]);

        return $order;
    }

    public function test_requested_can_transition_to_accepted(): void
    {
        $order = $this->createOrderWithStatus(RTDOrderStatus::REQUESTED);
        $this->assertTrue($this->machine->canTransition(RTDOrderStatus::REQUESTED, RTDOrderStatus::ACCEPTED));
        $this->machine->transition($order, RTDOrderStatus::ACCEPTED);
        $order->refresh();
        $this->assertEquals(RTDOrderStatus::ACCEPTED, $order->status);
    }

    public function test_requested_can_transition_to_declined(): void
    {
        $order = $this->createOrderWithStatus(RTDOrderStatus::REQUESTED);
        $this->machine->transition($order, RTDOrderStatus::DECLINED);
        $order->refresh();
        $this->assertEquals(RTDOrderStatus::DECLINED, $order->status);
    }

    public function test_accepted_cannot_transition_to_requested(): void
    {
        $order = $this->createOrderWithStatus(RTDOrderStatus::ACCEPTED);
        $this->assertFalse($this->machine->canTransition(RTDOrderStatus::ACCEPTED, RTDOrderStatus::REQUESTED));

        $this->expectException(InvalidArgumentException::class);
        $this->machine->transition($order, RTDOrderStatus::REQUESTED);
    }

    public function test_accepted_can_transition_to_paid(): void
    {
        $order = $this->createOrderWithStatus(RTDOrderStatus::ACCEPTED);
        $this->machine->transition($order, RTDOrderStatus::PAID);
        $order->refresh();
        $this->assertEquals(RTDOrderStatus::PAID, $order->status);
    }

    public function test_paid_can_transition_to_in_production(): void
    {
        $order = $this->createOrderWithStatus(RTDOrderStatus::PAID);
        $this->machine->transition($order, RTDOrderStatus::IN_PRODUCTION);
        $order->refresh();
        $this->assertEquals(RTDOrderStatus::IN_PRODUCTION, $order->status);
    }

    public function test_dispatched_can_transition_to_completed(): void
    {
        $order = $this->createOrderWithStatus(RTDOrderStatus::DISPATCHED);
        $this->machine->transition($order, RTDOrderStatus::COMPLETED);
        $order->refresh();
        $this->assertEquals(RTDOrderStatus::COMPLETED, $order->status);
    }

    public function test_no_payment_pending_in_transitions(): void
    {
        $allowed = $this->machine->getAllowedTransitions(RTDOrderStatus::ACCEPTED);
        $values = array_map(fn ($s) => $s->value, $allowed);
        $this->assertNotContains('PAYMENT_PENDING', $values);
        $this->assertContains('PAID', $values);
    }
}
