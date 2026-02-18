<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * New posts start as open (Inquiries); locked_at set when 10 people have responded.
     */
    public function up(): void
    {
        Schema::table('matching_sessions', function (Blueprint $table) {
            $table->timestamp('locked_at')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matching_sessions', function (Blueprint $table) {
            $table->timestamp('locked_at')->nullable(false)->change();
        });
    }
};
