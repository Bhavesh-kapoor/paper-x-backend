<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            MaterialSeeder::class,
            BrandSeeder::class,
            MaterialFinishSeeder::class,
            MaterialThicknessTypeSeeder::class,
            MaterialMillSeeder::class,
            MachineSeeder::class,
            MachineBrandSeeder::class,
            ConverterTypeSeeder::class,
            BrandTypeSeeder::class,
            FinishedProductSeeder::class,
            ScrapTypeSeeder::class,
        ]);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
