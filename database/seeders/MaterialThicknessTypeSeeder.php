<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialThicknessTypeSeeder extends Seeder
{
    public function run(): void
    {
        // Get material IDs by category
        $paperMaterials = DB::table('materials')
            ->where('category', 'LIKE', '%PAPER%')
            ->where('category', 'NOT LIKE', '%PAPERBOARD%')
            ->pluck('id');

        $paperboardMaterials = DB::table('materials')
            ->where('category', 'PAPERBOARDS')
            ->pluck('id');

        $allMaterials = DB::table('materials')->pluck('id');

        // Paper materials use GSM as primary unit
        foreach ($paperMaterials as $materialId) {
            DB::table('material_thickness_types')->updateOrInsert(
                ['material_id' => $materialId, 'unit' => 'GSM'],
                [
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Paperboard materials use both GSM and MM
        foreach ($paperboardMaterials as $materialId) {
            // GSM as primary
            DB::table('material_thickness_types')->updateOrInsert(
                ['material_id' => $materialId, 'unit' => 'GSM'],
                [
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            
            // MM as secondary
            DB::table('material_thickness_types')->updateOrInsert(
                ['material_id' => $materialId, 'unit' => 'MM'],
                [
                    'is_primary' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // Some materials might use other units (OUNCE, BF, MICRON)
        // Add these as needed for specific materials
        // For now, we'll add common units to all materials as optional
        $optionalUnits = ['OUNCE', 'BF', 'MICRON'];
        
        // Add optional units to all materials (can be used if needed)
        foreach ($allMaterials as $materialId) {
            foreach ($optionalUnits as $unit) {
                DB::table('material_thickness_types')->updateOrInsert(
                    ['material_id' => $materialId, 'unit' => $unit],
                    [
                        'is_primary' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
