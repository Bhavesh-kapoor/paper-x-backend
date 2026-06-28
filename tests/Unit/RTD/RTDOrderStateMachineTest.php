<?php

namespace Tests\Unit\RTD;

use App\Enums\RTDOrderStatus;
use App\StateMachines\RTDOrderStateMachine;
use PHPUnit\Framework\TestCase;

class RTDOrderStateMachineTest extends TestCase
{
    private RTDOrderStateMachine $machine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->machine = new RTDOrderStateMachine();
    }

    public function test_requested_can_transition_to_accepted(): void
    {
        $this->assertTrue($this->machine->canTransition(RTDOrderStatus::REQUESTED, RTDOrderStatus::ACCEPTED));
    }

    public function test_requested_can_transition_to_declined(): void
    {
        $this->assertTrue($this->machine->canTransition(RTDOrderStatus::REQUESTED, RTDOrderStatus::DECLINED));
    }

    public function test_accepted_cannot_transition_to_requested(): void
    {
        $this->assertFalse($this->machine->canTransition(RTDOrderStatus::ACCEPTED, RTDOrderStatus::REQUESTED));
    }

    public function test_accepted_can_transition_to_connected(): void
    {
        $this->assertTrue($this->machine->canTransition(RTDOrderStatus::ACCEPTED, RTDOrderStatus::CONNECTED));
    }

    public function test_accepted_can_transition_to_cancelled(): void
    {
        $this->assertTrue($this->machine->canTransition(RTDOrderStatus::ACCEPTED, RTDOrderStatus::CANCELLED));
    }

    public function test_connected_has_no_outgoing_transitions(): void
    {
        $allowed = $this->machine->getAllowedTransitions(RTDOrderStatus::CONNECTED);
        $this->assertEmpty($allowed);
    }

    public function test_accepted_allowed_transitions(): void
    {
        $allowed = $this->machine->getAllowedTransitions(RTDOrderStatus::ACCEPTED);
        $values = array_map(fn ($s) => $s->value, $allowed);
        $this->assertContains('CONNECTED', $values);
        $this->assertContains('CANCELLED', $values);
        $this->assertNotContains('PAID', $values);
    }
}
