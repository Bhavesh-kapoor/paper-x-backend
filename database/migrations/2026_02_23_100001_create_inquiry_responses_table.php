<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiry_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();
            $table->foreignId('responder_id')->constrained('users')->cascadeOnDelete();
            $table->string('responder_role');
            $table->decimal('approx_price', 12, 2)->nullable();
            $table->text('description');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['inquiry_id', 'responder_id']);
            $table->index('inquiry_id');
            $table->index('responder_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_responses');
    }
};
