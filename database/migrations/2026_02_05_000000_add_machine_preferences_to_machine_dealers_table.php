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
        Schema::table('machine_dealers', function (Blueprint $table) {
            $table->string('primary_machine_category', 100)->nullable()->after('location');
            $table->unsignedBigInteger('primary_machine_id')->nullable()->after('primary_machine_category');
            $table->json('preferred_brand_names')->nullable()->after('primary_machine_id');

            $table->index('primary_machine_category');
            $table->index('primary_machine_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('machine_dealers', function (Blueprint $table) {
            $table->dropIndex(['primary_machine_category']);
            $table->dropIndex(['primary_machine_id']);
            $table->dropColumn(['primary_machine_category', 'primary_machine_id', 'preferred_brand_names']);
        });
    }
};

