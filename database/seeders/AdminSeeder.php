<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::firstOrCreate(
            ['email' => 'zupply@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin@admin'),
                'email_verified_at' => now(),
            ]
        );
    }
}
