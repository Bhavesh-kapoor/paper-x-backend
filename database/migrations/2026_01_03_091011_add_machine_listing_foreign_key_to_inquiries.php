<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This migration adds the foreign key constraint for machine_listing_id
     * after the machine_listings table has been created.
     */
    public function up(): void
    {
        // Add foreign key constraint only if both tables exist
        if (Schema::hasTable('inquiries') && Schema::hasTable('machine_listings')) {
            // Check if the column exists
            if (Schema::hasColumn('inquiries', 'machine_listing_id')) {
                // Check if foreign key already exists
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'inquiries' 
                    AND COLUMN_NAME = 'machine_listing_id' 
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                
                if (empty($foreignKeys)) {
                    Schema::table('inquiries', function (Blueprint $table) {
                        $table->foreign('machine_listing_id')
                            ->references('id')
                            ->on('machine_listings')
                            ->onDelete('set null');
                    });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('inquiries')) {
            Schema::table('inquiries', function (Blueprint $table) {
                try {
                    $table->dropForeign(['machine_listing_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist, ignore
                }
            });
        }
    }
};

