<?php

namespace Tests\Unit\RTD;

use App\Exceptions\RTDDomainException;
use App\Services\CommissionCalculator;
use PHPUnit\Framework\TestCase;

class CommissionCalculatorTest extends TestCase
{
    private CommissionCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CommissionCalculator();
    }

    public function test_commission_9_percent_up_to_25000(): void
    {
        $result = $this->calculator->calculateCommission(20000.0);
        $this->assertEquals(9, $result['percent']);
        $this->assertEqualsWithDelta(1800, $result['amount'], 0.02);
    }

    public function test_commission_8_percent_up_to_75000(): void
    {
        $result = $this->calculator->calculateCommission(50000.0);
        $this->assertEquals(8, $result['percent']);
        $this->assertEqualsWithDelta(4000, $result['amount'], 0.02);
    }

    public function test_commission_6_percent_up_to_200000(): void
    {
        $result = $this->calculator->calculateCommission(100000.0);
        $this->assertEquals(6, $result['percent']);
        $this->assertEqualsWithDelta(6000, $result['amount'], 0.02);
    }

    public function test_commission_5_percent_up_to_300000(): void
    {
        $result = $this->calculator->calculateCommission(250000.0);
        $this->assertEquals(5, $result['percent']);
        $this->assertEqualsWithDelta(12500, $result['amount'], 0.02);
    }

    public function test_validate_order_cap_accepts_300000(): void
    {
        $this->calculator->validateOrderCap(300000.0);
        $this->expectNotToPerformAssertions();
    }

    public function test_validate_order_cap_throws_above_300000(): void
    {
        $this->expectException(RTDDomainException::class);
        $this->calculator->validateOrderCap(300001.0);
    }

    public function test_calculate_gst(): void
    {
        $gst = $this->calculator->calculateGST(1000.0, 18.0);
        $this->assertEqualsWithDelta(180.0, $gst, 0.02);
    }

    public function test_calculate_total(): void
    {
        $result = $this->calculator->calculateTotal(100, 100.0);
        $this->assertEqualsWithDelta(10000, $result['subtotal'], 0.02);
        $this->assertEquals(9, $result['commission_percent']);
        $this->assertEqualsWithDelta(900, $result['commission_amount'], 0.02);
        $this->assertEqualsWithDelta(18.0, $result['gst_percent'], 0.01);
        $this->assertGreaterThan(0, $result['gst_amount']);
        $this->assertGreaterThan($result['subtotal'], $result['total_amount']);
    }
}
