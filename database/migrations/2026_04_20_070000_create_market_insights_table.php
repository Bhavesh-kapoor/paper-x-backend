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
        Schema::create('market_insights', function (Blueprint $table) {
            $table->id();
            $table->date('insight_date')->unique();
            $table->longText('insight_text');
            $table->string('sentiment', 16)->default('neutral');
            $table->json('articles');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_insights');
    }
};
