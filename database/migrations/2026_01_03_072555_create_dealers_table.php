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
        Schema::create('dealers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('status', ['PENDING', 'ACTIVE', 'INACTIVE'])->default('PENDING');
            $table->boolean('profile_complete')->default(false);
            $table->json('grades')->nullable(); // grades & finishes
            $table->decimal('capacity_daily', 15, 2)->nullable();
            $table->decimal('capacity_monthly', 15, 2)->nullable();
            $table->string('capacity_unit')->nullable(); // kg, tons, pieces, etc
            $table->timestamps();

            $table->index('status');
            $table->index('profile_complete');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dealers');
    }
};
