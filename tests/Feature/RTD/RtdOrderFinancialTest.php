<?php

namespace Tests\Feature\RTD;

use App\Models\RtdProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RtdOrderFinancialTest extends TestCase
{
    use RefreshDatabase, RtdTestHelpers;

    /**
     * TC-O4 + Layer 9: Order above ₹3,00,000 cap fails.
     */
    public function test_order_above_cap_fails(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);

        $product->priceSlabs()->delete();
        $product->priceSlabs()->create([
            'min_qty'        => 1,
            'max_qty'        => 10000,
            'price_per_unit' => 3500,
        ]);
        $product->update(['max_capacity' => 10000]);

        $response = $this->withHeaders($this->authHeaders($brand))
            ->postJson('/api/v1/rtd/orders', [
                'product_id' => $product->id,
                'quantity'   => 100,
            ]);

        $response->assertStatus(422);
        $body = $response->json();
        $this->assertTrue(
            isset($body['message']) && (str_contains($body['message'], 'cap') || str_contains($body['message'], '300000'))
        );
    }

    /**
     * Layer 9: Commission slabs 20k→9%, 50k→8%, 1L→6%, 2.5L→5%.
     */
    public function test_commission_slabs(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();

        $product = $this->createProductAsConverter($converter);
        $product->priceSlabs()->delete();
        $product->priceSlabs()->create([
            'min_qty'        => 1,
            'max_qty'        => 100000,
            'price_per_unit' => 1000,
        ]);
        $product->update(['max_capacity' => 100000]);

        $cases = [
            ['qty' => 20, 'expected_percent' => 9],
            ['qty' => 50, 'expected_percent' => 8],
            ['qty' => 100, 'expected_percent' => 6],
            ['qty' => 250, 'expected_percent' => 5],
        ];

        foreach ($cases as $case) {
            $response = $this->withHeaders($this->authHeaders($brand))
                ->postJson('/api/v1/rtd/orders', [
                    'product_id' => $product->id,
                    'quantity'   => $case['qty'],
                ]);

            $response->assertStatus(201);
            $order = $response->json('data');
            $this->assertEquals($case['expected_percent'], (float) $order['commission_percent']);
            $subtotal = $case['qty'] * 1000;
            $expectedCommission = round($subtotal * $case['expected_percent'] / 100, 2);
            $this->assertEqualsWithDelta($expectedCommission, (float) $order['commission_amount'], 0.02);
            $this->assertGreaterThan(0, (float) $order['gst_amount']);
            $this->assertEqualsWithDelta(
                (float) $order['commission_amount'] + (float) $order['gst_amount'],
                (float) $order['total_amount'],
                0.02
            );
        }
    }
}
