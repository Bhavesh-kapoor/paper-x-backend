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
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->string('brand_name')->nullable();
            $table->string('contact_person_name');
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('gst')->nullable();
            $table->string('city')->nullable();
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
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
        Schema::dropIfExists('brands');
    }
};
