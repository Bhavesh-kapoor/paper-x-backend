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
        Schema::table('brands', function (Blueprint $table) {
            if (!Schema::hasColumn('brands', 'state')) {
                $table->string('state')->nullable()->after('city');
            }
            if (!Schema::hasColumn('brands', 'address')) {
                $table->text('address')->nullable()->after('state');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (Schema::hasColumn('brands', 'state')) {
                $table->dropColumn('state');
            }
            if (Schema::hasColumn('brands', 'address')) {
                $table->dropColumn('address');
            }
        });
    }
};
