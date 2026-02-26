<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained('inquiries')->cascadeOnDelete();
            $table->foreignId('matched_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('matched_role');
            $table->unsignedTinyInteger('match_score');
            $table->json('reason_json')->nullable();
            $table->timestamps();

            $table->unique(['inquiry_id', 'matched_user_id']);
            $table->index(['inquiry_id', 'match_score']);
            $table->index('matched_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_histories');
    }
};
