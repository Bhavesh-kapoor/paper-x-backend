<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('wallet_payment_orders')) {
            return;
        }

        Schema::table('wallet_payment_orders', function (Blueprint $table) {
            $table->dropForeign(['credit_pack_id']);
            $table->foreignId('credit_pack_id')->nullable()->change();
            $table->foreign('credit_pack_id')
                ->references('id')
                ->on('credit_packs')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('wallet_payment_orders')) {
            return;
        }

        Schema::table('wallet_payment_orders', function (Blueprint $table) {
            $table->dropForeign(['credit_pack_id']);
            $table->foreignId('credit_pack_id')->nullable(false)->change();
            $table->foreign('credit_pack_id')
                ->references('id')
                ->on('credit_packs')
                ->cascadeOnDelete();
        });
    }
};
