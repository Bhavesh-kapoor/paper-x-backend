<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rtd_orders', function (Blueprint $table) {
            $table->text('delivery_address')->nullable()->after('logo_path');
            $table->text('order_notes')->nullable()->after('delivery_address');
        });
    }

    public function down(): void
    {
        Schema::table('rtd_orders', function (Blueprint $table) {
            $table->dropColumn(['delivery_address', 'order_notes']);
        });
    }
};
