<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RtdListingPackSeeder extends Seeder
{
    public function run(): void
    {
        $packs = [
            [
                'slug'           => 'single',
                'name'           => 'Single product listing',
                'product_limit'  => 1,
                'validity_days'  => 180,
                'price'          => 249,
                'is_active'      => true,
                'sort_order'     => 1,
            ],
            [
                'slug'           => 'pack_5',
                'name'           => '5 product pack',
                'product_limit'  => 5,
                'validity_days'  => 180,
                'price'          => 999,
                'is_active'      => true,
                'sort_order'     => 2,
            ],
            [
                'slug'           => 'pack_15',
                'name'           => '15 product pack',
                'product_limit'  => 15,
                'validity_days'  => 180,
                'price'          => 2499,
                'is_active'      => true,
                'sort_order'     => 3,
            ],
        ];

        foreach ($packs as $pack) {
            DB::table('rtd_listing_packs')->updateOrInsert(
                ['slug' => $pack['slug']],
                array_merge($pack, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
