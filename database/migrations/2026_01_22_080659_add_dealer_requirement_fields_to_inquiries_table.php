<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            // Add size_unit if it doesn't exist
            if (!Schema::hasColumn('inquiries', 'size_unit')) {
                $table->enum('size_unit', ['inches', 'cm', 'mm'])->nullable()->after('size');
            }
            
            // Add visibility field if it doesn't exist
            if (!Schema::hasColumn('inquiries', 'visibility')) {
                $table->enum('visibility', ['dealers', 'converters', 'all'])->default('all')->after('urgency');
            } else {
                if (DB::getDriverName() === 'mysql') {
                    DB::statement("ALTER TABLE inquiries MODIFY COLUMN visibility ENUM('dealers', 'converters', 'all') NOT NULL DEFAULT 'all'");
                    DB::table('inquiries')->where('visibility', 'manufacturers')->update(['visibility' => 'all']);
                }
            }
            
            // Add location_source if it doesn't exist
            if (!Schema::hasColumn('inquiries', 'location_source')) {
                $table->enum('location_source', ['saved', 'manual'])->nullable()->after('location');
            }
            
            // Add location_id if it doesn't exist (foreign key to dealer_locations)
            if (!Schema::hasColumn('inquiries', 'location_id')) {
                $table->foreignId('location_id')->nullable()->after('location_source')->constrained('dealer_locations')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            if (Schema::hasColumn('inquiries', 'location_id')) {
                $table->dropForeign(['location_id']);
                $table->dropColumn('location_id');
            }
            if (Schema::hasColumn('inquiries', 'location_source')) {
                $table->dropColumn('location_source');
            }
            if (Schema::hasColumn('inquiries', 'visibility')) {
                $table->dropColumn('visibility');
            }
            if (Schema::hasColumn('inquiries', 'size_unit')) {
                $table->dropColumn('size_unit');
            }
        });
    }
};
