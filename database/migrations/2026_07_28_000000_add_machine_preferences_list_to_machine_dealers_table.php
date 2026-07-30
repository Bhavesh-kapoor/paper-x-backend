<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Stores the full list of machine preferences a dealer selects at registration
     * (each an object of {machine_category, machine_id}). The legacy single
     * primary_machine_* columns are kept in sync from the first preference for the
     * matching engine.
     */
    public function up(): void
    {
        Schema::table('machine_dealers', function (Blueprint $table) {
            $table->json('machine_preferences')->nullable()->after('preferred_brand_names');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machine_dealers', function (Blueprint $table) {
            $table->dropColumn('machine_preferences');
        });
    }
};
