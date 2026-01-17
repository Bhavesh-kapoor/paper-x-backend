<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CreditPack;

class CreditPackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packs = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'credits' => 50,
                'price' => 500.00,
                'gst_percentage' => 18,
                'description' => 'Perfect for getting started with matchmaking',
                'validity' => 'Lifetime',
                'is_best_value' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'credits' => 120,
                'price' => 1000.00,
                'gst_percentage' => 18,
                'description' => 'Best value for growing businesses',
                'validity' => 'Lifetime',
                'is_best_value' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'credits' => 300,
                'price' => 2500.00,
                'gst_percentage' => 18,
                'description' => 'Ideal for established businesses',
                'validity' => 'Lifetime',
                'is_best_value' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Factory',
                'slug' => 'factory',
                'credits' => 750,
                'price' => 5000.00,
                'gst_percentage' => 18,
                'description' => 'Maximum credits for large operations',
                'validity' => 'Lifetime',
                'is_best_value' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($packs as $pack) {
            CreditPack::updateOrCreate(
                ['slug' => $pack['slug']],
                $pack
            );
        }
    }
}
