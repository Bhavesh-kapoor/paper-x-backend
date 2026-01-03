<?php

namespace Database\Seeders;

use App\Models\ScrapType;
use Illuminate\Database\Seeder;

class ScrapTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // 📄 Paper & Board Scrap
            ['name' => 'Kraft Scrap', 'category' => 'paper_board', 'sort_order' => 1],
            ['name' => 'Duplex Scrap', 'category' => 'paper_board', 'sort_order' => 2],
            ['name' => 'FBB / SBS Scrap', 'category' => 'paper_board', 'sort_order' => 3],
            ['name' => 'Art Paper Scrap', 'category' => 'paper_board', 'sort_order' => 4],
            ['name' => 'Art Card Scrap', 'category' => 'paper_board', 'sort_order' => 5],
            ['name' => 'Grey Board Scrap', 'category' => 'paper_board', 'sort_order' => 6],
            ['name' => 'Kappa Board Scrap', 'category' => 'paper_board', 'sort_order' => 7],
            ['name' => 'Maplitho Scrap', 'category' => 'paper_board', 'sort_order' => 8],
            ['name' => 'Mixed Paper Scrap', 'category' => 'paper_board', 'sort_order' => 9],
            
            // ✂️ Process Scrap
            ['name' => 'Trim Waste', 'category' => 'process', 'sort_order' => 10],
            ['name' => 'Die-Cut Skeleton', 'category' => 'process', 'sort_order' => 11],
            ['name' => 'Reel Ends', 'category' => 'process', 'sort_order' => 12],
            ['name' => 'Sheet Offcuts', 'category' => 'process', 'sort_order' => 13],
            ['name' => 'Core Scrap', 'category' => 'process', 'sort_order' => 14],
            
            // 🧴 Finishing Scrap
            ['name' => 'Lamination Waste', 'category' => 'finishing', 'sort_order' => 15],
            ['name' => 'Foil Waste', 'category' => 'finishing', 'sort_order' => 16],
            ['name' => 'Printed Rejection', 'category' => 'finishing', 'sort_order' => 17],
            ['name' => 'UV Coated Waste', 'category' => 'finishing', 'sort_order' => 18],
            ['name' => 'Ink Contaminated Waste', 'category' => 'finishing', 'sort_order' => 19],
            
            // 🧵 Ancillary
            ['name' => 'Stitching Wire Scrap', 'category' => 'ancillary', 'sort_order' => 20],
            ['name' => 'Plastic Film Waste', 'category' => 'ancillary', 'sort_order' => 21],
            ['name' => 'Packing Material Scrap', 'category' => 'ancillary', 'sort_order' => 22],
        ];

        foreach ($types as $type) {
            ScrapType::firstOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
