<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rtd_dispatch_proofs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('rtd_orders')->cascadeOnDelete();
            $table->string('proof_type');
            $table->string('file_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rtd_dispatch_proofs');
    }
};
