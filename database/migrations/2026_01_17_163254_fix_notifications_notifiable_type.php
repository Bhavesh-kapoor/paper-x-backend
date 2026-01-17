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
        // Clean up any notifications with User as notifiable_type
        // Set them to null to prevent morph map errors
        DB::table('notifications')
            ->where(function ($query) {
                $query->where('notifiable_type', 'App\\Models\\User')
                    ->orWhere('notifiable_type', 'user')
                    ->orWhere('notifiable_type', 'User')
                    ->orWhere('notifiable_type', 'LIKE', '%User%');
            })
            ->update([
                'notifiable_type' => null,
                'notifiable_id' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
