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
                'name' => 'Starter Pack',
                'slug' => 'starter-pack',
                'credits' => 50,
                'price' => 50.00,
                'gst_percentage' => 18,
                'description' => 'Ideal for new users to start unlocking matches and responding to inquiries.',
                'validity' => 'Lifetime',
                'is_best_value' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Active Pack',
                'slug' => 'active-pack',
                'credits' => 150,
                'price' => 150.00,
                'gst_percentage' => 18,
                'description' => 'Best for regular marketplace activity including match unlocks and conversations.',
                'validity' => 'Lifetime',
                'is_best_value' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Pro Pack',
                'slug' => 'pro-pack',
                'credits' => 300,
                'price' => 300.00,
                'gst_percentage' => 18,
                'description' => 'For growing businesses actively sourcing and connecting on the platform.',
                'validity' => 'Lifetime',
                'is_best_value' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Enterprise Pack',
                'slug' => 'enterprise-pack',
                'credits' => 600,
                'price' => 600.00,
                'gst_percentage' => 18,
                'description' => 'Designed for high-volume users managing frequent requirements and interactions.',
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
