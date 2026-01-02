<?php

namespace Tests\Feature;

use App\Models\Material;
use App\Models\MaterialGrade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaterialTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_all_materials_with_grades()
    {
        $material = Material::create([
            'name' => 'Test Material',
            'category' => 'PAPER'
        ]);

        $grade = MaterialGrade::create([
            'name' => 'Grade A',
            'material_id' => $material->id
        ]);

        $response = $this->getJson('/api/v1/materials');

        $response->assertStatus(200)
            ->assertJsonPath('data.0.name', 'Test Material')
            ->assertJsonPath('data.0.grades.0.name', 'Grade A');
    }

    public function test_can_filter_materials_by_category()
    {
        Material::create([
            'name' => 'Paper Material',
            'category' => 'PAPER'
        ]);

        Material::create([
            'name' => 'Plastic Material',
            'category' => 'PLASTIC'
        ]);

        $response = $this->getJson('/api/v1/materials?category=PAPER');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Paper Material');
    }
}
