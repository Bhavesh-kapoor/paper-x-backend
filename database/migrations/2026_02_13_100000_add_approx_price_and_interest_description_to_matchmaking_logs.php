<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matchmaking_logs', function (Blueprint $table) {
            $table->decimal('approx_price', 12, 2)->nullable()->after('declined_at');
            $table->text('interest_description')->nullable()->after('approx_price');
        });
    }

    public function down(): void
    {
        Schema::table('matchmaking_logs', function (Blueprint $table) {
            $table->dropColumn(['approx_price', 'interest_description']);
        });
    }
};
