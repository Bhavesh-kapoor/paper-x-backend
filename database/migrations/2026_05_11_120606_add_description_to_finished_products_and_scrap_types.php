<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('finished_products') && !Schema::hasColumn('finished_products', 'description')) {
            Schema::table('finished_products', function (Blueprint $table) {
                $table->text('description')->nullable()->after('category');
            });
        }

        if (Schema::hasTable('scrap_types') && !Schema::hasColumn('scrap_types', 'description')) {
            Schema::table('scrap_types', function (Blueprint $table) {
                $table->text('description')->nullable()->after('category');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('finished_products') && Schema::hasColumn('finished_products', 'description')) {
            Schema::table('finished_products', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }

        if (Schema::hasTable('scrap_types') && Schema::hasColumn('scrap_types', 'description')) {
            Schema::table('scrap_types', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
