<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rtd_products', function (Blueprint $table) {
            if (Schema::hasColumn('rtd_products', 'gsm')) {
                $table->dropColumn('gsm');
            }

            $table->string('size_unit', 20)->nullable()->after('size');
            $table->foreignId('material_id')->nullable()->after('size_unit')->constrained('materials')->nullOnDelete();
            $table->string('material_custom', 100)->nullable()->after('material');
            $table->string('thickness', 50)->nullable()->after('material_custom');
            $table->string('thickness_unit', 20)->nullable()->after('thickness');
            $table->json('finish_ids')->nullable()->after('thickness_unit');
            $table->json('branding_methods')->nullable()->after('finish');
            $table->unsignedBigInteger('location_id')->nullable()->after('delivery_geography');
            $table->string('location_source', 20)->nullable()->after('location_id');
            $table->decimal('latitude', 10, 7)->nullable()->after('location_source');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('rtd_products', function (Blueprint $table) {
            if (Schema::hasColumn('rtd_products', 'material_id')) {
                $table->dropForeign(['material_id']);
            }

            $table->dropColumn([
                'size_unit',
                'material_id',
                'material_custom',
                'thickness',
                'thickness_unit',
                'finish_ids',
                'branding_methods',
                'location_id',
                'location_source',
                'latitude',
                'longitude',
            ]);

            $table->string('gsm')->nullable()->after('material');
        });
    }
};
