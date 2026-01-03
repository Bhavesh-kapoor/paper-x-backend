<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MaterialFinishSeeder extends Seeder
{
    public function run(): void
    {
        // Complete list of finishes/grades/coating/variants from the document
        $finishes = [
            // Coating Types
            ['name' => 'Uncoated', 'type' => 'coating'],
            ['name' => 'Machine Finished (MF)', 'type' => 'finish'],
            ['name' => 'Machine Glazed (MG)', 'type' => 'finish'],
            ['name' => 'Coated One Side (C1S)', 'type' => 'coating'],
            ['name' => 'Coated Two Side (C2S)', 'type' => 'coating'],
            ['name' => 'Coated', 'type' => 'coating'],
            
            // Surface Finish
            ['name' => 'Smooth', 'type' => 'finish'],
            ['name' => 'Super Smooth', 'type' => 'finish'],
            ['name' => 'Rough', 'type' => 'finish'],
            ['name' => 'Laid', 'type' => 'finish'],
            ['name' => 'Wove', 'type' => 'finish'],
            ['name' => 'Vellum', 'type' => 'finish'],
            ['name' => 'Antique Finish', 'type' => 'finish'],
            
            // Art / Coated Paper Specific
            ['name' => 'Gloss', 'type' => 'finish'],
            ['name' => 'Matt', 'type' => 'finish'],
            ['name' => 'Silk / Satin', 'type' => 'finish'],
            ['name' => 'Dull', 'type' => 'finish'],
            ['name' => 'High Gloss', 'type' => 'finish'],
            ['name' => 'Dull Finish', 'type' => 'finish'],
            ['name' => 'Semi-Gloss', 'type' => 'finish'],
            ['name' => 'Velvet Touch', 'type' => 'finish'],
            
            // Brightness / Shade
            ['name' => 'High Bright', 'type' => 'grade'],
            ['name' => 'Natural Shade', 'type' => 'grade'],
            ['name' => 'Cream Shade', 'type' => 'grade'],
            ['name' => 'Extra White', 'type' => 'grade'],
            ['name' => 'Ivory', 'type' => 'grade'],
            
            // Coating Levels
            ['name' => 'Triple Coated', 'type' => 'coating'],
            ['name' => 'Light Coated', 'type' => 'coating'],
            ['name' => 'Medium Coated', 'type' => 'coating'],
            ['name' => 'Heavy Coated', 'type' => 'coating'],
            
            // Surface Treatment
            ['name' => 'Calendered', 'type' => 'treatment'],
            ['name' => 'Super Calendered', 'type' => 'treatment'],
            ['name' => 'Non-Calendered', 'type' => 'treatment'],
            ['name' => 'Surface Sized', 'type' => 'treatment'],
            
            // Print Compatibility
            ['name' => 'Offset Grade', 'type' => 'grade'],
            ['name' => 'UV Printable', 'type' => 'grade'],
            ['name' => 'Digital Printable', 'type' => 'grade'],
            ['name' => 'Inkjet Compatible', 'type' => 'grade'],
            
            // Back Side
            ['name' => 'Grey Back', 'type' => 'variant'],
            ['name' => 'White Back', 'type' => 'variant'],
            
            // Surface Quality
            ['name' => 'Smooth Finish', 'type' => 'finish'],
            ['name' => 'Standard Finish', 'type' => 'finish'],
            ['name' => 'High Bulk', 'type' => 'grade'],
            ['name' => 'Low Bulk', 'type' => 'grade'],
            
            // Kraft Specific
            ['name' => 'Ribbed', 'type' => 'finish'],
            ['name' => 'Smooth Kraft', 'type' => 'finish'],
            ['name' => 'Rough Kraft', 'type' => 'finish'],
            ['name' => 'Natural Brown', 'type' => 'appearance'],
            ['name' => 'Golden Brown', 'type' => 'appearance'],
            ['name' => 'White Top', 'type' => 'appearance'],
            ['name' => 'Mottled', 'type' => 'appearance'],
            ['name' => 'Speckled', 'type' => 'appearance'],
            
            // Strength Characteristics
            ['name' => 'High RCT', 'type' => 'grade'],
            ['name' => 'High Burst', 'type' => 'grade'],
            ['name' => 'High Tear', 'type' => 'grade'],
            ['name' => 'High Ring Crush', 'type' => 'grade'],
            ['name' => 'Sack Grade', 'type' => 'grade'],
            
            // Fluting Types
            ['name' => 'Semi Chemical Fluting', 'type' => 'grade'],
            ['name' => 'Recycled Fluting', 'type' => 'grade'],
            ['name' => 'Virgin Fluting', 'type' => 'grade'],
            
            // Surface Types
            ['name' => 'Natural', 'type' => 'surface'],
            ['name' => 'Medium Finish', 'type' => 'surface'],
            ['name' => 'Grey Grey', 'type' => 'surface'],
            ['name' => 'White Grey', 'type' => 'surface'],
            ['name' => 'Black Grey', 'type' => 'surface'],
            
            // Coating Types
            ['name' => 'PE Coated', 'type' => 'coating'],
            ['name' => 'Silicone Coated', 'type' => 'coating'],
            ['name' => 'Wax Coated', 'type' => 'coating'],
            ['name' => 'Thermal Coated', 'type' => 'coating'],
            
            // Ply Types
            ['name' => 'Single Ply', 'type' => 'variant'],
            ['name' => 'Multi Ply', 'type' => 'variant'],
            ['name' => 'Laminated Ply', 'type' => 'variant'],
            
            // Edge Quality
            ['name' => 'Hard Edge', 'type' => 'finish'],
            ['name' => 'Soft Edge', 'type' => 'finish'],
            
            // Face Treatment
            ['name' => 'Lined', 'type' => 'treatment'],
            ['name' => 'Unlined', 'type' => 'treatment'],
            ['name' => 'Laminated Face', 'type' => 'treatment'],
            
            // Resistance Types
            ['name' => 'Oil & Grease Resistant (OGR)', 'type' => 'grade'],
            ['name' => 'Greaseproof', 'type' => 'grade'],
            ['name' => 'Water Resistant', 'type' => 'grade'],
            ['name' => 'Moisture Resistant', 'type' => 'grade'],
            ['name' => 'Heat Resistant', 'type' => 'grade'],
            ['name' => 'Tear Resistant', 'type' => 'grade'],
            
            // Food & Compliance
            ['name' => 'Food Grade', 'type' => 'grade'],
            ['name' => 'FDA Compliant', 'type' => 'grade'],
            ['name' => 'BIS Approved', 'type' => 'grade'],
            ['name' => 'FSC Certified', 'type' => 'grade'],
            ['name' => 'Recycled Content', 'type' => 'grade'],
            ['name' => 'Compostable', 'type' => 'grade'],
            
            // Special Surface
            ['name' => 'Glassine', 'type' => 'surface'],
            ['name' => 'Super Calendered', 'type' => 'surface'],
            
            // Label Stock Specific
            ['name' => 'Gloss Face Stock', 'type' => 'finish'],
            ['name' => 'Semi-Gloss Face Stock', 'type' => 'finish'],
            ['name' => 'Matt Face Stock', 'type' => 'finish'],
            ['name' => 'With Release Liner', 'type' => 'variant'],
            ['name' => 'Linerless', 'type' => 'variant'],
            
            // Adhesive Type
            ['name' => 'Permanent', 'type' => 'grade'],
            ['name' => 'Removable', 'type' => 'grade'],
            ['name' => 'Freezer Grade', 'type' => 'grade'],
            ['name' => 'High Tack', 'type' => 'grade'],
            
            // Texture
            ['name' => 'Embossed', 'type' => 'texture'],
            ['name' => 'Linen', 'type' => 'texture'],
            ['name' => 'Felt', 'type' => 'texture'],
            ['name' => 'Canvas', 'type' => 'texture'],
            ['name' => 'Hammered', 'type' => 'texture'],
            
            // Visual
            ['name' => 'Metallic', 'type' => 'appearance'],
            ['name' => 'Pearlised', 'type' => 'appearance'],
            ['name' => 'Shimmer', 'type' => 'appearance'],
            ['name' => 'Translucent', 'type' => 'appearance'],
            ['name' => 'Opaque', 'type' => 'appearance'],
            
            // Special
            ['name' => 'Handmade', 'type' => 'variant'],
            ['name' => 'Recycled Look', 'type' => 'appearance'],
            ['name' => 'Deckle Edge', 'type' => 'finish'],
        ];

        foreach ($finishes as $finish) {
            DB::table('material_finishes')->updateOrInsert(
                ['name' => $finish['name']],
                [
                    'type' => $finish['type'],
                    'material_id' => null, // Global finishes, can be assigned to materials later
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
