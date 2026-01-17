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
        // Clean up any inquiries with invalid poster_type values
        // If poster_type is user or contains User, set it to null
        DB::table('inquiries')
            ->where(function ($query) {
                $query->where('poster_type', 'user')
                    ->orWhere('poster_type', 'User')
                    ->orWhere('poster_type', 'LIKE', '%User%')
                    ->orWhere('poster_type', 'App\\Models\\User');
            })
            ->update([
                'poster_type' => null,
                'poster_id' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reverse this migration
    }
};
