<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('converter_finished_products')) {
            Schema::create('converter_finished_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('converter_id')->constrained()->cascadeOnDelete();
                $table->foreignId('finished_product_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['converter_id', 'finished_product_id'], 'conv_fp_unique');
            });
        } else {
            // Table exists, just add the unique constraint if it doesn't exist
            Schema::table('converter_finished_products', function (Blueprint $table) {
                if (DB::getDriverName() === 'mysql') {
                    $indexes = DB::select("SHOW INDEXES FROM converter_finished_products");
                    $indexNames = array_column($indexes, 'Key_name');
                    if (in_array('conv_fp_unique', $indexNames)) {
                        return;
                    }
                }
                $table->unique(['converter_id', 'finished_product_id'], 'conv_fp_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('converter_finished_products');
    }
};
