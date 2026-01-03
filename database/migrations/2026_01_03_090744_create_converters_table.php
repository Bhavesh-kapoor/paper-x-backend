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
        Schema::create('converters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('converter_type_custom')->nullable(); // if not in predefined list
            $table->decimal('capacity_daily', 15, 2)->nullable();
            $table->decimal('capacity_monthly', 15, 2)->nullable();
            $table->string('capacity_unit')->nullable(); // pieces, kg, tons
            $table->text('factory_address')->nullable();
            $table->string('factory_city')->nullable();
            $table->string('factory_state')->nullable();
            $table->decimal('factory_latitude', 10, 8)->nullable();
            $table->decimal('factory_longitude', 11, 8)->nullable();
            $table->enum('status', ['PENDING', 'ACTIVE', 'INACTIVE'])->default('PENDING');
            $table->boolean('profile_complete')->default(false);
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
        Schema::dropIfExists('converters');
    }
};
