<?php

namespace Database\Seeders;

use App\Models\FinishedProduct;
use Illuminate\Database\Seeder;

class FinishedProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // 📦 Packaging
            ['name' => 'Corrugated Boxes (all ply variants)', 'category' => 'packaging', 'sort_order' => 1],
            ['name' => 'Rigid Boxes (all structures)', 'category' => 'packaging', 'sort_order' => 2],
            ['name' => 'Folding Cartons', 'category' => 'packaging', 'sort_order' => 3],
            ['name' => 'Mono Cartons', 'category' => 'packaging', 'sort_order' => 4],
            ['name' => 'Display Units', 'category' => 'packaging', 'sort_order' => 5],
            ['name' => 'Shelf Ready Packaging', 'category' => 'packaging', 'sort_order' => 6],
            ['name' => 'Dump Bins', 'category' => 'packaging', 'sort_order' => 7],
            ['name' => 'Counter Displays', 'category' => 'packaging', 'sort_order' => 8],
            ['name' => 'POS Materials', 'category' => 'packaging', 'sort_order' => 9],
            
            // 🎁 Premium / Gifting
            ['name' => 'Gift Boxes', 'category' => 'premium', 'sort_order' => 10],
            ['name' => 'Corporate Gifting Boxes', 'category' => 'premium', 'sort_order' => 11],
            ['name' => 'Promotional Kits', 'category' => 'premium', 'sort_order' => 12],
            ['name' => 'Subscription Boxes', 'category' => 'premium', 'sort_order' => 13],
            ['name' => 'Festive Hampers', 'category' => 'premium', 'sort_order' => 14],
            ['name' => 'Luxury Presentation Boxes', 'category' => 'premium', 'sort_order' => 15],
            
            // 🍔 Food & Beverage
            ['name' => 'Cake Boxes', 'category' => 'food_beverage', 'sort_order' => 16],
            ['name' => 'Pizza Boxes', 'category' => 'food_beverage', 'sort_order' => 17],
            ['name' => 'Burger Boxes', 'category' => 'food_beverage', 'sort_order' => 18],
            ['name' => 'Meal Boxes', 'category' => 'food_beverage', 'sort_order' => 19],
            ['name' => 'Food Trays', 'category' => 'food_beverage', 'sort_order' => 20],
            ['name' => 'Paper Cups', 'category' => 'food_beverage', 'sort_order' => 21],
            ['name' => 'Paper Bowls', 'category' => 'food_beverage', 'sort_order' => 22],
            ['name' => 'Paper Plates', 'category' => 'food_beverage', 'sort_order' => 23],
            ['name' => 'Paper Lids', 'category' => 'food_beverage', 'sort_order' => 24],
            ['name' => 'Food Wrap Sheets', 'category' => 'food_beverage', 'sort_order' => 25],
            
            // 📚 Print & Stationery
            ['name' => 'Books', 'category' => 'print_stationery', 'sort_order' => 26],
            ['name' => 'Notebooks', 'category' => 'print_stationery', 'sort_order' => 27],
            ['name' => 'Diaries', 'category' => 'print_stationery', 'sort_order' => 28],
            ['name' => 'Planners', 'category' => 'print_stationery', 'sort_order' => 29],
            ['name' => 'Registers', 'category' => 'print_stationery', 'sort_order' => 30],
            ['name' => 'Catalogues', 'category' => 'print_stationery', 'sort_order' => 31],
            ['name' => 'Brochures', 'category' => 'print_stationery', 'sort_order' => 32],
            ['name' => 'Flyers', 'category' => 'print_stationery', 'sort_order' => 33],
            ['name' => 'Manuals', 'category' => 'print_stationery', 'sort_order' => 34],
            ['name' => 'Annual Reports', 'category' => 'print_stationery', 'sort_order' => 35],
            ['name' => 'Certificates', 'category' => 'print_stationery', 'sort_order' => 36],
            ['name' => 'Envelopes', 'category' => 'print_stationery', 'sort_order' => 37],
            ['name' => 'Files & Folders', 'category' => 'print_stationery', 'sort_order' => 38],
            ['name' => 'Sleeves', 'category' => 'print_stationery', 'sort_order' => 39],
            ['name' => 'Inserts', 'category' => 'print_stationery', 'sort_order' => 40],
            ['name' => 'Leaflets', 'category' => 'print_stationery', 'sort_order' => 41],
            ['name' => 'Warranty Cards', 'category' => 'print_stationery', 'sort_order' => 42],
            
            // 🏷 Labels & Tags
            ['name' => 'Product Labels', 'category' => 'labels_tags', 'sort_order' => 43],
            ['name' => 'Barcode Labels', 'category' => 'labels_tags', 'sort_order' => 44],
            ['name' => 'Security Labels', 'category' => 'labels_tags', 'sort_order' => 45],
            ['name' => 'Garment Tags', 'category' => 'labels_tags', 'sort_order' => 46],
            ['name' => 'Hang Tags', 'category' => 'labels_tags', 'sort_order' => 47],
            ['name' => 'RFID Tags', 'category' => 'labels_tags', 'sort_order' => 48],
            
            // 🏭 Industrial
            ['name' => 'Paper Tubes', 'category' => 'industrial', 'sort_order' => 49],
            ['name' => 'Paper Cores', 'category' => 'industrial', 'sort_order' => 50],
            ['name' => 'Honeycomb Panels', 'category' => 'industrial', 'sort_order' => 51],
            ['name' => 'Edge Protectors', 'category' => 'industrial', 'sort_order' => 52],
            ['name' => 'Paper Pallets', 'category' => 'industrial', 'sort_order' => 53],
            ['name' => 'Cushioning Sheets', 'category' => 'industrial', 'sort_order' => 54],
        ];

        foreach ($products as $product) {
            FinishedProduct::firstOrCreate(
                ['name' => $product['name']],
                $product
            );
        }
    }
}
