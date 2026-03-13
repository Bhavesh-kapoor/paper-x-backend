<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rtd_converter_entitlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('pack_slug', 32);
            $table->unsignedSmallInteger('product_limit');
            $table->unsignedSmallInteger('used_count')->default(0);
            $table->dateTime('validity_ends_at');
            $table->dateTime('purchased_at');
            $table->timestamps();

            $table->index(['user_id', 'validity_ends_at']);
            $table->index('pack_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rtd_converter_entitlements');
    }
};
