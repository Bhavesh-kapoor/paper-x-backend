<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Extend sender_type to support converter and machine_dealer for role-agnostic chat.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE chat_messages MODIFY COLUMN sender_type ENUM('DEALER', 'BRAND', 'CONVERTER', 'MACHINE_DEALER') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE chat_messages MODIFY COLUMN sender_type ENUM('DEALER', 'BRAND') NOT NULL");
        }
    }
};
