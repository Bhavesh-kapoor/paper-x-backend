<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiry_items', function (Blueprint $table) {
            $table->decimal('sheet_width', 8, 2)->nullable()->after('quantity_unit');
            $table->decimal('sheet_length', 8, 2)->nullable()->after('sheet_width');
            $table->decimal('reel_width', 8, 2)->nullable()->after('sheet_length');
            $table->string('size_unit')->nullable()->after('reel_width');
        });
    }

    public function down(): void
    {
        Schema::table('inquiry_items', function (Blueprint $table) {
            $table->dropColumn(['sheet_width', 'sheet_length', 'reel_width', 'size_unit']);
        });
    }
};
