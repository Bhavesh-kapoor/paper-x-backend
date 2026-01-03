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
        Schema::table('inquiries', function (Blueprint $table) {
            $table->decimal('thickness', 10, 3)->nullable()->after('quantity_unit');
            $table->string('thickness_unit', 20)->nullable()->after('thickness'); // GSM, MM, OUNCE, BF, MICRON
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn(['thickness', 'thickness_unit']);
        });
    }
};
