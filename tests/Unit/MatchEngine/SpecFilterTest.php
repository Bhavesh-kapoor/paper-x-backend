<?php

namespace Tests\Unit\MatchEngine;

use App\Domain\MatchEngine\SpecFilter;
use PHPUnit\Framework\TestCase;

class SpecFilterTest extends TestCase
{
    private SpecFilter $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new SpecFilter;
    }

    public function test_gsm_within_normal_tolerance_returns_pass_or_variance(): void
    {
        $buyer = ['thickness_gsm' => 200.0, 'quantity' => null, 'thickness_mm' => null, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $seller = ['thickness_gsm' => 210.0, 'quantity' => null, 'thickness_mm' => null, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $result = $this->filter->evaluate($buyer, $seller, 'normal');
        $this->assertContains($result['status'], [SpecFilter::STATUS_PASS, SpecFilter::STATUS_PASS_WITH_VARIANCE]);
    }

    public function test_gsm_over_normal_tolerance_returns_hard_fail(): void
    {
        $buyer = ['thickness_gsm' => 200.0, 'quantity' => null, 'thickness_mm' => null, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $seller = ['thickness_gsm' => 240.0, 'quantity' => null, 'thickness_mm' => null, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $result = $this->filter->evaluate($buyer, $seller, 'normal');
        $this->assertSame(SpecFilter::STATUS_HARD_FAIL, $result['status']);
    }

    public function test_gsm_urgent_within_tolerance_returns_pass_with_variance(): void
    {
        $buyer = ['thickness_gsm' => 200.0, 'quantity' => null, 'thickness_mm' => null, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $seller = ['thickness_gsm' => 230.0, 'quantity' => null, 'thickness_mm' => null, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $result = $this->filter->evaluate($buyer, $seller, 'urgent');
        $this->assertSame(SpecFilter::STATUS_PASS_WITH_VARIANCE, $result['status']);
    }

    public function test_thickness_mm_normal_boundary_within_tolerance(): void
    {
        $buyer = ['thickness_gsm' => null, 'thickness_mm' => 1.0, 'quantity' => null, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $seller = ['thickness_gsm' => null, 'thickness_mm' => 1.2, 'quantity' => null, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $result = $this->filter->evaluate($buyer, $seller, 'normal');
        $this->assertContains($result['status'], [SpecFilter::STATUS_PASS, SpecFilter::STATUS_PASS_WITH_VARIANCE]);
    }

    public function test_thickness_mm_urgent_within_tolerance(): void
    {
        $buyer = ['thickness_gsm' => null, 'thickness_mm' => 1.0, 'quantity' => null, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $seller = ['thickness_gsm' => null, 'thickness_mm' => 1.29, 'quantity' => null, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $result = $this->filter->evaluate($buyer, $seller, 'urgent');
        $this->assertContains($result['status'], [SpecFilter::STATUS_PASS, SpecFilter::STATUS_PASS_WITH_VARIANCE]);
    }

    public function test_thickness_mm_over_normal_returns_hard_fail(): void
    {
        $buyer = ['thickness_gsm' => null, 'thickness_mm' => 1.0, 'quantity' => null, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $seller = ['thickness_gsm' => null, 'thickness_mm' => 1.25, 'quantity' => null, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $result = $this->filter->evaluate($buyer, $seller, 'normal');
        $this->assertSame(SpecFilter::STATUS_HARD_FAIL, $result['status']);
    }

    public function test_quantity_normal_seller_70_percent_passes(): void
    {
        $buyer = ['thickness_gsm' => null, 'thickness_mm' => null, 'quantity' => 100.0, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $seller = ['thickness_gsm' => null, 'thickness_mm' => null, 'quantity' => 70.0, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $result = $this->filter->evaluate($buyer, $seller, 'normal');
        $this->assertContains($result['status'], [SpecFilter::STATUS_PASS, SpecFilter::STATUS_PASS_WITH_VARIANCE]);
    }

    public function test_quantity_normal_seller_50_percent_returns_hard_fail(): void
    {
        $buyer = ['thickness_gsm' => null, 'thickness_mm' => null, 'quantity' => 100.0, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $seller = ['thickness_gsm' => null, 'thickness_mm' => null, 'quantity' => 50.0, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $result = $this->filter->evaluate($buyer, $seller, 'normal');
        $this->assertSame(SpecFilter::STATUS_HARD_FAIL, $result['status']);
    }

    public function test_quantity_urgent_seller_25_percent_returns_pass_with_variance(): void
    {
        $buyer = ['thickness_gsm' => null, 'thickness_mm' => null, 'quantity' => 100.0, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $seller = ['thickness_gsm' => null, 'thickness_mm' => null, 'quantity' => 25.0, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $result = $this->filter->evaluate($buyer, $seller, 'urgent');
        $this->assertSame(SpecFilter::STATUS_PASS_WITH_VARIANCE, $result['status']);
    }

    public function test_null_buyer_gsm_skips_dimension_does_not_hard_fail(): void
    {
        $buyer = ['thickness_gsm' => null, 'thickness_mm' => null, 'quantity' => null, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $seller = ['thickness_gsm' => 210.0, 'thickness_mm' => null, 'quantity' => null, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $result = $this->filter->evaluate($buyer, $seller, 'normal');
        $this->assertSame('SKIPPED', $result['details']['gsm']['status']);
        $this->assertNotSame(SpecFilter::STATUS_HARD_FAIL, $result['status']);
    }

    public function test_null_seller_gsm_skips_dimension(): void
    {
        $buyer = ['thickness_gsm' => 200.0, 'thickness_mm' => null, 'quantity' => null, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $seller = ['thickness_gsm' => null, 'thickness_mm' => null, 'quantity' => null, 'sheet_width' => null, 'sheet_length' => null, 'reel_width' => null];
        $result = $this->filter->evaluate($buyer, $seller, 'normal');
        $this->assertSame('SKIPPED', $result['details']['gsm']['status']);
    }
}
