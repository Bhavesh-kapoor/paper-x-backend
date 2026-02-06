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
        Schema::table('matchmaking_logs', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['dealer_id']);
            
            // Make dealer_id nullable
            $table->foreignId('dealer_id')->nullable()->change();
            
            // Re-add foreign key constraint with nullOnDelete
            $table->foreign('dealer_id')->references('id')->on('dealers')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matchmaking_logs', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['dealer_id']);
            
            // Make dealer_id NOT NULL again
            $table->foreignId('dealer_id')->nullable(false)->change();
            
            // Re-add foreign key constraint with cascadeOnDelete
            $table->foreign('dealer_id')->references('id')->on('dealers')->cascadeOnDelete();
        });
    }
};
