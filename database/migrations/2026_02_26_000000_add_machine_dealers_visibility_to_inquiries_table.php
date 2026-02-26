<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE inquiries
                MODIFY COLUMN visibility ENUM('dealers', 'converters', 'machine_dealers', 'all')
                NOT NULL DEFAULT 'all'
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE inquiries
                MODIFY COLUMN visibility ENUM('dealers', 'converters', 'all')
                NOT NULL DEFAULT 'all'
            ");
        }
    }
};

