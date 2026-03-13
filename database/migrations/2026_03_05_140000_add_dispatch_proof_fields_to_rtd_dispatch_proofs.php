<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rtd_dispatch_proofs', function (Blueprint $table) {
            $table->string('courier_name')->nullable()->after('order_id');
            $table->string('tracking_number')->nullable()->after('courier_name');
            $table->date('dispatch_date')->nullable()->after('tracking_number');
        });

    }

    public function down(): void
    {
        Schema::table('rtd_dispatch_proofs', function (Blueprint $table) {
            $table->dropColumn(['courier_name', 'tracking_number', 'dispatch_date']);
        });
    }
};
