<?php

namespace Tests\Feature\RTD;

use App\Enums\RTDLeadTime;
use App\Models\RtdProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RtdProductTest extends TestCase
{
    use RefreshDatabase, RtdTestHelpers;

    /** TC-P1: Create Product – Valid */
    public function test_create_product_valid(): void
    {
        $converter = $this->createConverterUser();

        $response = $this->withHeaders($this->authHeaders($converter))
            ->postJson('/api/v1/rtd/products', [
                'category'           => 'Box',
                'product_name'       => 'Corrugated Box',
                'lead_time'          => RTDLeadTime::H24->value,
                'moq'                => 10,
                'max_capacity'       => 500,
                'base_price'         => 25.50,
                'buy_now_enabled'    => true,
                'delivery_geography' => 'North India',
                'price_slabs'        => [
                    ['min_qty' => 10, 'max_qty' => 99, 'price_per_unit' => 25.50],
                    ['min_qty' => 100, 'max_qty' => 500, 'price_per_unit' => 23.00],
                ],
            ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('data.id'));
        $this->assertCount(2, $response->json('data.price_slabs') ?? []);

        $this->assertDatabaseHas('rtd_products', [
            'converter_id'    => $converter->id,
            'product_name'    => 'Corrugated Box',
            'status'          => 'active',
            'decline_count'   => 0,
            'visibility_score'=> 100,
        ]);

        $product = RtdProduct::find($response->json('data.id'));
        $this->assertCount(2, $product->priceSlabs);
    }

    /** TC-P2: Create Product – Overlapping Slabs */
    public function test_create_product_overlapping_slabs_fails(): void
    {
        $converter = $this->createConverterUser();

        $response = $this->withHeaders($this->authHeaders($converter))
            ->postJson('/api/v1/rtd/products', [
                'category'     => 'Box',
                'product_name' => 'Bad Slabs',
                'lead_time'    => RTDLeadTime::H24->value,
                'moq'          => 10,
                'base_price'   => 10,
                'price_slabs'  => [
                    ['min_qty' => 10, 'max_qty' => 100, 'price_per_unit' => 10],
                    ['min_qty' => 50, 'max_qty' => 200, 'price_per_unit' => 9],
                ],
            ]);

        $this->assertContains($response->status(), [400, 422]);
        $this->assertDatabaseMissing('rtd_products', ['product_name' => 'Bad Slabs']);
    }

    /** TC-P3: Create Product – Invalid MOQ (MOQ > max_capacity when max_capacity set) */
    public function test_create_product_moq_greater_than_max_capacity_fails(): void
    {
        $converter = $this->createConverterUser();

        $response = $this->withHeaders($this->authHeaders($converter))
            ->postJson('/api/v1/rtd/products', [
                'category'     => 'Box',
                'product_name' => 'Bad MOQ',
                'lead_time'    => RTDLeadTime::H24->value,
                'moq'          => 100,
                'max_capacity' => 50,
                'base_price'   => 10,
                'price_slabs'  => [
                    ['min_qty' => 100, 'max_qty' => 200, 'price_per_unit' => 10],
                ],
            ]);

        $response->assertStatus(422);
    }

    /** TC-P5: Pause Product */
    public function test_pause_product(): void
    {
        $converter = $this->createConverterUser();
        $product  = $this->createProductAsConverter($converter);

        $response = $this->withHeaders($this->authHeaders($converter))
            ->postJson("/api/v1/rtd/products/{$product->id}/pause");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'paused');

        $this->assertDatabaseHas('rtd_products', ['id' => $product->id, 'status' => 'paused']);
    }

    /** TC-P6: Resume Inactive Product fails */
    public function test_resume_inactive_product_fails(): void
    {
        $converter = $this->createConverterUser();
        $product  = $this->createProductAsConverter($converter);
        $product->update(['status' => 'inactive']);

        $response = $this->withHeaders($this->authHeaders($converter))
            ->postJson("/api/v1/rtd/products/{$product->id}/resume");

        $response->assertStatus(422);
    }

    /** My Products + Get Product by ID (happy path checks) */
    public function test_my_products_and_get_by_id(): void
    {
        $converter = $this->createConverterUser();
        $product  = $this->createProductAsConverter($converter);

        $list = $this->withHeaders($this->authHeaders($converter))
            ->getJson('/api/v1/rtd/products/my')
            ->assertStatus(200);

        $items = $list->json('data.data') ?? $list->json('data') ?? [];
        $this->assertGreaterThanOrEqual(1, is_array($items) ? count($items) : 0);

        $detail = $this->withHeaders($this->authHeaders($converter))
            ->getJson("/api/v1/rtd/products/{$product->id}")
            ->assertStatus(200);

        $this->assertEquals($product->id, $detail->json('data.id'));
        $this->assertArrayHasKey('price_slabs', $detail->json('data'));
    }

    /** Phase 2: Brand → Catalog – product visible, lead_time and price slabs correct */
    public function test_catalog_shows_product_to_brand(): void
    {
        $converter = $this->createConverterUser();
        $brand    = $this->createBrandUser();
        $product  = $this->createProductAsConverter($converter);

        $response = $this->withHeaders($this->authHeaders($brand))
            ->getJson('/api/v1/rtd/products/catalog?per_page=15')
            ->assertStatus(200);

        $data = $response->json('data.data') ?? $response->json('data') ?? [];
        $this->assertNotEmpty($data);
        $first = collect(is_array($data) ? $data : [])->firstWhere('id', $product->id);
        $this->assertNotNull($first);
        $this->assertEquals('H24', $first['lead_time'] ?? null);
        $this->assertArrayHasKey('price_slabs', $first);
        $this->assertNotEmpty($first['price_slabs']);
    }
}
