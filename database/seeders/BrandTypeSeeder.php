<?php

namespace Database\Seeders;

use App\Models\BrandType;
use Illuminate\Database\Seeder;

class BrandTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // FMCG & CONSUMER GOODS
            ['name' => 'Food & Beverage Brand', 'category' => 'fmcg', 'sort_order' => 1],
            ['name' => 'Packaged Food Brand', 'category' => 'fmcg', 'sort_order' => 2],
            ['name' => 'Snacks & Confectionery Brand', 'category' => 'fmcg', 'sort_order' => 3],
            ['name' => 'Dairy Brand', 'category' => 'fmcg', 'sort_order' => 4],
            ['name' => 'Frozen Food Brand', 'category' => 'fmcg', 'sort_order' => 5],
            ['name' => 'Beverage Brand (Juices, Water, Energy Drinks)', 'category' => 'fmcg', 'sort_order' => 6],
            ['name' => 'Alcoholic Beverage Brand', 'category' => 'fmcg', 'sort_order' => 7],
            ['name' => 'Tobacco / Nicotine Brand', 'category' => 'fmcg', 'sort_order' => 8],
            
            // PHARMA, HEALTH & WELLNESS
            ['name' => 'Pharmaceutical Brand', 'category' => 'pharma', 'sort_order' => 9],
            ['name' => 'OTC / Nutraceutical Brand', 'category' => 'pharma', 'sort_order' => 10],
            ['name' => 'Ayurvedic / Herbal Brand', 'category' => 'pharma', 'sort_order' => 11],
            ['name' => 'Medical Device Brand', 'category' => 'pharma', 'sort_order' => 12],
            ['name' => 'Diagnostic Kit Brand', 'category' => 'pharma', 'sort_order' => 13],
            ['name' => 'Health Supplement Brand', 'category' => 'pharma', 'sort_order' => 14],
            
            // BEAUTY, PERSONAL CARE & LUXURY
            ['name' => 'Cosmetics Brand', 'category' => 'beauty', 'sort_order' => 15],
            ['name' => 'Skincare Brand', 'category' => 'beauty', 'sort_order' => 16],
            ['name' => 'Haircare Brand', 'category' => 'beauty', 'sort_order' => 17],
            ['name' => 'Perfume & Fragrance Brand', 'category' => 'beauty', 'sort_order' => 18],
            ['name' => 'Luxury Beauty Brand', 'category' => 'beauty', 'sort_order' => 19],
            ['name' => 'Grooming Brand', 'category' => 'beauty', 'sort_order' => 20],
            
            // APPAREL, FASHION & ACCESSORIES
            ['name' => 'Apparel / Clothing Brand', 'category' => 'apparel', 'sort_order' => 21],
            ['name' => 'Garment Export Brand', 'category' => 'apparel', 'sort_order' => 22],
            ['name' => 'Footwear Brand', 'category' => 'apparel', 'sort_order' => 23],
            ['name' => 'Fashion Accessories Brand', 'category' => 'apparel', 'sort_order' => 24],
            ['name' => 'Jewellery Brand', 'category' => 'apparel', 'sort_order' => 25],
            ['name' => 'Watch Brand', 'category' => 'apparel', 'sort_order' => 26],
            ['name' => 'Eyewear Brand', 'category' => 'apparel', 'sort_order' => 27],
            
            // ELECTRONICS & DURABLES
            ['name' => 'Consumer Electronics Brand', 'category' => 'electronics', 'sort_order' => 28],
            ['name' => 'Mobile & Accessories Brand', 'category' => 'electronics', 'sort_order' => 29],
            ['name' => 'Electrical Appliances Brand', 'category' => 'electronics', 'sort_order' => 30],
            ['name' => 'Home Appliances Brand', 'category' => 'electronics', 'sort_order' => 31],
            ['name' => 'IT Hardware Brand', 'category' => 'electronics', 'sort_order' => 32],
            ['name' => 'Industrial Electronics Brand', 'category' => 'electronics', 'sort_order' => 33],
            
            // E-COMMERCE & D2C BRANDS
            ['name' => 'D2C Consumer Brand', 'category' => 'ecommerce', 'sort_order' => 34],
            ['name' => 'E-commerce Seller Brand', 'category' => 'ecommerce', 'sort_order' => 35],
            ['name' => 'Subscription Box Brand', 'category' => 'ecommerce', 'sort_order' => 36],
            ['name' => 'Marketplace Private Label', 'category' => 'ecommerce', 'sort_order' => 37],
            ['name' => 'Quick Commerce Brand', 'category' => 'ecommerce', 'sort_order' => 38],
            
            // FOOD SERVICE & HOSPITALITY
            ['name' => 'Bakery Brand', 'category' => 'food_service', 'sort_order' => 39],
            ['name' => 'Patisserie / Cake Brand', 'category' => 'food_service', 'sort_order' => 40],
            ['name' => 'QSR / Fast Food Brand', 'category' => 'food_service', 'sort_order' => 41],
            ['name' => 'Restaurant Chain', 'category' => 'food_service', 'sort_order' => 42],
            ['name' => 'Cloud Kitchen', 'category' => 'food_service', 'sort_order' => 43],
            ['name' => 'Café Brand', 'category' => 'food_service', 'sort_order' => 44],
            ['name' => 'Catering Brand', 'category' => 'food_service', 'sort_order' => 45],
            
            // CORPORATE, B2B & INSTITUTIONAL
            ['name' => 'Corporate Gifting Brand', 'category' => 'corporate', 'sort_order' => 46],
            ['name' => 'Promotional Merchandise Brand', 'category' => 'corporate', 'sort_order' => 47],
            ['name' => 'Office Supplies Brand', 'category' => 'corporate', 'sort_order' => 48],
            ['name' => 'Industrial Goods Brand', 'category' => 'corporate', 'sort_order' => 49],
            ['name' => 'Chemical Brand', 'category' => 'corporate', 'sort_order' => 50],
            ['name' => 'Paints & Coatings Brand', 'category' => 'corporate', 'sort_order' => 51],
            
            // EDUCATION, PUBLISHING & STATIONERY
            ['name' => 'School / Education Brand', 'category' => 'education', 'sort_order' => 52],
            ['name' => 'Publishing House', 'category' => 'education', 'sort_order' => 53],
            ['name' => 'Book Publisher', 'category' => 'education', 'sort_order' => 54],
            ['name' => 'EdTech Brand', 'category' => 'education', 'sort_order' => 55],
            ['name' => 'Stationery Brand', 'category' => 'education', 'sort_order' => 56],
            ['name' => 'Notebook / Diary Brand', 'category' => 'education', 'sort_order' => 57],
            
            // AGRICULTURE & RURAL PRODUCTS
            ['name' => 'Agro Products Brand', 'category' => 'agriculture', 'sort_order' => 58],
            ['name' => 'Seeds Brand', 'category' => 'agriculture', 'sort_order' => 59],
            ['name' => 'Fertilizer Brand', 'category' => 'agriculture', 'sort_order' => 60],
            ['name' => 'Organic / Natural Products Brand', 'category' => 'agriculture', 'sort_order' => 61],
            ['name' => 'Tea / Coffee Brand', 'category' => 'agriculture', 'sort_order' => 62],
            ['name' => 'Spices Brand', 'category' => 'agriculture', 'sort_order' => 63],
            
            // HOME, LIFESTYLE & DECOR
            ['name' => 'Home Furnishing Brand', 'category' => 'home_lifestyle', 'sort_order' => 64],
            ['name' => 'Furniture Brand', 'category' => 'home_lifestyle', 'sort_order' => 65],
            ['name' => 'Home Décor Brand', 'category' => 'home_lifestyle', 'sort_order' => 66],
            ['name' => 'Kitchenware Brand', 'category' => 'home_lifestyle', 'sort_order' => 67],
            ['name' => 'Lighting Brand', 'category' => 'home_lifestyle', 'sort_order' => 68],
            
            // EVENTS, GIFTS & PROMOTIONS
            ['name' => 'Event Management Brand', 'category' => 'events_gifts', 'sort_order' => 69],
            ['name' => 'Festive Gifting Brand', 'category' => 'events_gifts', 'sort_order' => 70],
            ['name' => 'Wedding Gifting Brand', 'category' => 'events_gifts', 'sort_order' => 71],
            ['name' => 'Luxury Hampers Brand', 'category' => 'events_gifts', 'sort_order' => 72],
            ['name' => 'Promotional Campaign Brand', 'category' => 'events_gifts', 'sort_order' => 73],
        ];

        foreach ($types as $type) {
            BrandType::firstOrCreate(
                ['name' => $type['name']],
                $type
            );
        }
    }
}
