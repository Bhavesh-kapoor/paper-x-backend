<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->boolean('has_warehouse')->default(true)->after('capacity_unit');
            $table->text('bulk_orders_note')->nullable()->after('has_warehouse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dealers', function (Blueprint $table) {
            $table->dropColumn(['has_warehouse', 'bulk_orders_note']);
        });
    }
};
