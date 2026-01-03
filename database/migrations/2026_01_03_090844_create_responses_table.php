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
        Schema::create('responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('responder_id')->constrained('users')->cascadeOnDelete();
            $table->string('responder_type'); // dealer, converter, machine_dealer
            
            // Response details
            $table->decimal('quantity_offered', 15, 2)->nullable(); // full or partial
            $table->string('quantity_unit')->nullable();
            $table->decimal('quoted_price', 15, 2)->nullable();
            $table->string('price_unit')->nullable(); // per_sheet, per_kg
            $table->enum('price_status', ['agreed', 'negotiable', 'needs_more_details'])->nullable();
            $table->text('additional_details')->nullable();
            
            // Status
            $table->enum('status', ['PENDING', 'SHORTLISTED', 'SELECTED', 'REJECTED', 'WITHDRAWN'])->default('PENDING');
            
            // Session relationship (if selected)
            $table->foreignId('session_id')->nullable()->constrained('matching_sessions')->nullOnDelete();
            
            $table->timestamps();

            $table->index('inquiry_id');
            $table->index('responder_id');
            $table->index('status');
            $table->index('session_id');
            
            // Prevent duplicate responses
            $table->unique(['inquiry_id', 'responder_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('responses');
    }
};
