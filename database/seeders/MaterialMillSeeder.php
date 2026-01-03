<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialMillSeeder extends Seeder
{
    public function run(): void
    {
        // This seeder creates associations between materials and mills
        // Based on the document, mills can be associated with multiple materials
        // For now, we'll create a basic structure that can be expanded
        
        // Get some common materials
        $duplexBoard = DB::table('materials')->where('name', 'Duplex Board (Grey Back)')->first();
        $duplexWhite = DB::table('materials')->where('name', 'Duplex Board (White Back)')->first();
        $maplitho = DB::table('materials')->where('name', 'Maplitho Paper')->first();
        $kraftPaper = DB::table('materials')->where('name', 'Kraft Paper (MG)')->first();
        $copierPaper = DB::table('materials')->where('name', 'Copier Paper')->first();
        
        // Get some common mills/brands
        $itc = DB::table('brands')->where('name', 'ITC Paperboards & Specialty Papers')->first();
        $jk = DB::table('brands')->where('name', 'JK Paper Ltd')->first();
        $westCoast = DB::table('brands')->where('name', 'West Coast Paper Mills')->first();
        $emami = DB::table('brands')->where('name', 'Emami Paper Mills')->first();
        $nrAgarwal = DB::table('brands')->where('name', 'N R Agarwal Industries Ltd')->first();
        $cheema = DB::table('brands')->where('name', 'Cheema Papers Ltd')->first();
        
        // Common associations based on industry knowledge
        $associations = [];
        
        // Duplex Board associations
        if ($duplexBoard && $itc) {
            $associations[] = ['material_id' => $duplexBoard->id, 'brand_id' => $itc->id];
        }
        if ($duplexBoard && $emami) {
            $associations[] = ['material_id' => $duplexBoard->id, 'brand_id' => $emami->id];
        }
        if ($duplexBoard && $nrAgarwal) {
            $associations[] = ['material_id' => $duplexBoard->id, 'brand_id' => $nrAgarwal->id];
        }
        if ($duplexBoard && $cheema) {
            $associations[] = ['material_id' => $duplexBoard->id, 'brand_id' => $cheema->id];
        }
        
        if ($duplexWhite && $itc) {
            $associations[] = ['material_id' => $duplexWhite->id, 'brand_id' => $itc->id];
        }
        if ($duplexWhite && $emami) {
            $associations[] = ['material_id' => $duplexWhite->id, 'brand_id' => $emami->id];
        }
        
        // Maplitho Paper associations
        if ($maplitho && $jk) {
            $associations[] = ['material_id' => $maplitho->id, 'brand_id' => $jk->id];
        }
        if ($maplitho && $westCoast) {
            $associations[] = ['material_id' => $maplitho->id, 'brand_id' => $westCoast->id];
        }
        
        // Copier Paper associations
        if ($copierPaper && $jk) {
            $associations[] = ['material_id' => $copierPaper->id, 'brand_id' => $jk->id];
        }
        
        // Kraft Paper associations
        if ($kraftPaper && $westCoast) {
            $associations[] = ['material_id' => $kraftPaper->id, 'brand_id' => $westCoast->id];
        }
        
        // Insert associations
        foreach ($associations as $association) {
            DB::table('material_mills')->updateOrInsert(
                [
                    'material_id' => $association['material_id'],
                    'brand_id' => $association['brand_id'],
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
        
        // Note: In a real scenario, you would have comprehensive data mapping
        // all materials to their available mills. This is a basic structure
        // that can be expanded with more accurate industry data.
    }
}
