<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matchmaking_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('matchmaking_logs', 'declined_at')) {
                $table->timestamp('declined_at')->nullable()->after('selected_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('matchmaking_logs', function (Blueprint $table) {
            $table->dropColumn('declined_at');
        });
    }
};
