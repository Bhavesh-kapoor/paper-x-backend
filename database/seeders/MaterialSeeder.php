<?php

// database/seeders/MaterialSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialSeeder extends Seeder
{
    public function run(): void
    {
        // 1️⃣ Materials
        $materials = [
            // PAPER – WRITING & PRINTING
            ['name' => 'Maplitho Paper', 'category' => 'PAPER – WRITING & PRINTING'],
            ['name' => 'Creamwove Paper', 'category' => 'PAPER – WRITING & PRINTING'],
            ['name' => 'Ledger Paper', 'category' => 'PAPER – WRITING & PRINTING'],
            ['name' => 'Bond Paper', 'category' => 'PAPER – WRITING & PRINTING'],
            ['name' => 'Copier Paper', 'category' => 'PAPER – WRITING & PRINTING'],
            ['name' => 'Offset Printing Paper', 'category' => 'PAPER – WRITING & PRINTING'],
            ['name' => 'Book Printing Paper', 'category' => 'PAPER – WRITING & PRINTING'],
            ['name' => 'Bible Paper', 'category' => 'PAPER – WRITING & PRINTING'],
            ['name' => 'NCR Paper', 'category' => 'PAPER – WRITING & PRINTING'],
            ['name' => 'Carbonless Paper', 'category' => 'PAPER – WRITING & PRINTING'],
            ['name' => 'Digital Printing Paper', 'category' => 'PAPER – WRITING & PRINTING'],
            ['name' => 'Inkjet Paper', 'category' => 'PAPER – WRITING & PRINTING'],
            ['name' => 'Laser Printing Paper', 'category' => 'PAPER – WRITING & PRINTING'],

            // B. PACKAGING PAPERS
            ['name' => 'Kraft Paper (MG)', 'category' => 'PACKAGING PAPERS'],
            ['name' => 'PE coated kraft', 'category' => 'PACKAGING PAPERS'],
            ['name' => 'Kraft Paper (MF)', 'category' => 'PACKAGING PAPERS'],
            ['name' => 'Sack Kraft Paper', 'category' => 'PACKAGING PAPERS'],
            ['name' => 'Semi Kraft Paper', 'category' => 'PACKAGING PAPERS'],
            ['name' => 'Test Liner', 'category' => 'PACKAGING PAPERS'],
            ['name' => 'Fluting Paper', 'category' => 'PACKAGING PAPERS'],
            ['name' => 'Corrugating Medium', 'category' => 'PACKAGING PAPERS'],
            ['name' => 'Linerboard', 'category' => 'PACKAGING PAPERS'],
            ['name' => 'White Top Kraft', 'category' => 'PACKAGING PAPERS'],
            ['name' => 'Ribbed Kraft', 'category' => 'PACKAGING PAPERS'],
            ['name' => 'Clupak Kraft', 'category' => 'PACKAGING PAPERS'],

            // C. PAPERBOARDS
            ['name' => 'Art Card', 'category' => 'PAPERBOARDS'],
            ['name' => 'Duplex Board (Grey Back)', 'category' => 'PAPERBOARDS'],
            ['name' => 'Duplex Board (White Back)', 'category' => 'PAPERBOARDS'],
            ['name' => 'Folding Box Board (FBB)', 'category' => 'PAPERBOARDS'],
            ['name' => 'Solid Bleached Sulphate (SBS)', 'category' => 'PAPERBOARDS'],
            ['name' => 'Solid Unbleached Sulphate (SUS)', 'category' => 'PAPERBOARDS'],
            ['name' => 'Coated Board', 'category' => 'PAPERBOARDS'],
            ['name' => 'Uncoated Board', 'category' => 'PAPERBOARDS'],
            ['name' => 'Ivory Board', 'category' => 'PAPERBOARDS'],
            ['name' => 'Chipboard', 'category' => 'PAPERBOARDS'],
            ['name' => 'Grey Board', 'category' => 'PAPERBOARDS'],
            ['name' => 'Kappa Board', 'category' => 'PAPERBOARDS'],
            ['name' => 'Kraft Board', 'category' => 'PAPERBOARDS'],
            ['name' => 'Straw Board', 'category' => 'PAPERBOARDS'],
            ['name' => 'Mill Board', 'category' => 'PAPERBOARDS'],
            ['name' => 'Box Board', 'category' => 'PAPERBOARDS'],
            ['name' => 'Bristol Board', 'category' => 'PAPERBOARDS'],

            // D. SPECIALITY & FUNCTIONAL PAPERS
            ['name' => 'Black Paper', 'category' => 'SPECIALITY & FUNCTIONAL PAPERS'],
            ['name' => 'Thermal Paper', 'category' => 'SPECIALITY & FUNCTIONAL PAPERS'],
            ['name' => 'OGR Paper', 'category' => 'SPECIALITY & FUNCTIONAL PAPERS'],
            ['name' => 'Butter Paper', 'category' => 'SPECIALITY & FUNCTIONAL PAPERS'],
            ['name' => 'Glassine Paper', 'category' => 'SPECIALITY & FUNCTIONAL PAPERS'],
            ['name' => 'Wax Paper', 'category' => 'SPECIALITY & FUNCTIONAL PAPERS'],
            ['name' => 'Silicone Paper', 'category' => 'SPECIALITY & FUNCTIONAL PAPERS'],
            ['name' => 'Release Paper', 'category' => 'SPECIALITY & FUNCTIONAL PAPERS'],
            ['name' => 'Parchment Paper', 'category' => 'SPECIALITY & FUNCTIONAL PAPERS'],
            ['name' => 'Filter Paper', 'category' => 'SPECIALITY & FUNCTIONAL PAPERS'],
            ['name' => 'Security Paper', 'category' => 'SPECIALITY & FUNCTIONAL PAPERS'],
            ['name' => 'Label Stock Paper', 'category' => 'SPECIALITY & FUNCTIONAL PAPERS'],
            ['name' => 'Fluorescent Paper', 'category' => 'SPECIALITY & FUNCTIONAL PAPERS'],
            ['name' => 'Coloured Paper', 'category' => 'SPECIALITY & FUNCTIONAL PAPERS'],
            ['name' => 'Textured Paper', 'category' => 'SPECIALITY & FUNCTIONAL PAPERS'],
            ['name' => 'Handmade Paper', 'category' => 'SPECIALITY & FUNCTIONAL PAPERS'],

            // E. DECORATIVE & FANCY PAPERS
            ['name' => 'Art Paper', 'category' => 'DECORATIVE & FANCY PAPERS'],
            ['name' => 'Metallic Paper', 'category' => 'DECORATIVE & FANCY PAPERS'],
            ['name' => 'Pearlised Paper', 'category' => 'DECORATIVE & FANCY PAPERS'],
            ['name' => 'Embossed Paper', 'category' => 'DECORATIVE & FANCY PAPERS'],
            ['name' => 'Linen Paper', 'category' => 'DECORATIVE & FANCY PAPERS'],
            ['name' => 'Kraft Texture Paper', 'category' => 'DECORATIVE & FANCY PAPERS'],
            ['name' => 'Imported Fancy Paper', 'category' => 'DECORATIVE & FANCY PAPERS'],
            ['name' => 'Eco Kraft Paper', 'category' => 'DECORATIVE & FANCY PAPERS'],

            // F. INDUSTRIAL & TECHNICAL PAPERS
            ['name' => 'Insulation Paper', 'category' => 'INDUSTRIAL & TECHNICAL PAPERS'],
            ['name' => 'Electrical Paper', 'category' => 'INDUSTRIAL & TECHNICAL PAPERS'],
            ['name' => 'Lamination Base Paper', 'category' => 'INDUSTRIAL & TECHNICAL PAPERS'],
            ['name' => 'Tissue Base Paper', 'category' => 'INDUSTRIAL & TECHNICAL PAPERS'],
            ['name' => 'Cigarette Paper', 'category' => 'INDUSTRIAL & TECHNICAL PAPERS'],
            ['name' => 'Food Wrapping Paper', 'category' => 'INDUSTRIAL & TECHNICAL PAPERS'],
            ['name' => 'Cupstock Paper', 'category' => 'INDUSTRIAL & TECHNICAL PAPERS'],
            ['name' => 'Straw Paper', 'category' => 'INDUSTRIAL & TECHNICAL PAPERS'],

            // G. ANCILLARY MATERIALS
            ['name' => 'Adhesives (PVA)', 'category' => 'ANCILLARY MATERIALS'],
            ['name' => 'Cold Glue', 'category' => 'ANCILLARY MATERIALS'],
            ['name' => 'Hot Melt Glue', 'category' => 'ANCILLARY MATERIALS'],
            ['name' => 'Dextrin Glue', 'category' => 'ANCILLARY MATERIALS'],
            ['name' => 'Starch Adhesive', 'category' => 'ANCILLARY MATERIALS'],
            ['name' => 'Offset Inks', 'category' => 'ANCILLARY MATERIALS'],
            ['name' => 'Flexo Inks', 'category' => 'ANCILLARY MATERIALS'],
            ['name' => 'Gravure Inks', 'category' => 'ANCILLARY MATERIALS'],
            ['name' => 'UV Inks', 'category' => 'ANCILLARY MATERIALS'],
            ['name' => 'Lamination Films (BOPP)', 'category' => 'ANCILLARY MATERIALS'],
            ['name' => 'PET Films', 'category' => 'ANCILLARY MATERIALS'],
            ['name' => 'Foils (Gold/Silver)', 'category' => 'ANCILLARY MATERIALS'],
            ['name' => 'Release Liners', 'category' => 'ANCILLARY MATERIALS'],
            ['name' => 'Tapes', 'category' => 'ANCILLARY MATERIALS'],
            ['name' => 'Binding Wire', 'category' => 'ANCILLARY MATERIALS'],
            ['name' => 'Stitching Wire', 'category' => 'ANCILLARY MATERIALS'],
            ['name' => 'Packaging Chemicals', 'category' => 'ANCILLARY MATERIALS'],
        ];

        DB::table('materials')->insert($materials);

        // Note: Brands are now seeded via BrandSeeder

        // 3️⃣ Material Grades / Finishes / Coatings / Variants
        $grades = [
            'Uncoated',
            'Machine Finished (MF)',
            'Machine Glazed (MG)',
            'Coated One Side (C1S)',
            'Coated Two Side (C2S)',
            'Smooth',
            'Super Smooth',
            'Rough',
            'Laid',
            'Wove',
            'Vellum',
            'Antique Finish',
            'Gloss',
            'Matt',
            'Silk / Satin',
            'Dull',
            'High Gloss',
            'High Bright',
            'Natural Shade',
            'Cream Shade',
            'Extra White',
            'Ivory',
            'Triple Coated',
            'Light Coated',
            'Heavy Coated',
            'Velvet Touch',
            'Calendered',
            'Super Calendered',
            'Non-Calendered',
            'Surface Sized',
            'Offset Grade',
            'UV Printable',
            'Digital Printable',
            'Inkjet Compatible',
            'Grey Back',
            'White Back',
            'Semi-Gloss',
            'Low Bulk',
            'High RCT',
            'High Burst',
            'High Tear',
            'High Ring Crush',
            'Sack Grade',
            'Semi Chemical Fluting',
            'Recycled Fluting',
            'Virgin Fluting',
            'Glassine',
            'Silicone Coated',
            'Wax Coated',
            'PE Coated',
            'Thermal Coated',
            'With Release Liner',
            'Linerless',
            'Permanent',
            'Removable',
            'Freezer Grade',
            'High Tack',
            'Embossed',
            'Linen',
            'Felt',
            'Canvas',
            'Hammered',
            'Metallic',
            'Pearlised',
            'Shimmer',
            'Translucent',
            'Opaque',
            'Handmade',
            'Recycled Look',
            'Deckle Edge',
        ];

        // Assign grades to materials (for demonstration, assigning random grades to materials)
        $materialIds = DB::table('materials')->pluck('id');
        
        foreach ($materialIds as $materialId) {
            // Assign 3-5 random grades to each material
            $randomGrades = array_rand(array_flip($grades), rand(3, 5));
            if (!is_array($randomGrades)) {
                $randomGrades = [$randomGrades];
            }

            foreach ($randomGrades as $gradeName) {
                DB::table('material_grades')->insert([
                    'name' => $gradeName,
                    'material_id' => $materialId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}

?>