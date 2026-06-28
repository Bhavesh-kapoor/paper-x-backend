<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rtd_products', function (Blueprint $table) {
            $table->string('gst_rate', 10)->nullable()->after('base_price');
        });
    }

    public function down(): void
    {
        Schema::table('rtd_products', function (Blueprint $table) {
            $table->dropColumn('gst_rate');
        });
    }
};
