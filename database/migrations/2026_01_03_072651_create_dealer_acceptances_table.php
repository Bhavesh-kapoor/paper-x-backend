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
        Schema::create('dealer_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dealer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['ACCEPTED', 'DECLINED'])->default('ACCEPTED');
            $table->text('decline_reason')->nullable();
            $table->timestamps();

            $table->unique(['dealer_id', 'inquiry_id']);
            $table->index('inquiry_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dealer_acceptances');
    }
};
