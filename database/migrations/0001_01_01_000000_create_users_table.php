<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            // roles
            $table->string('primary_role')->nullable();
            $table->boolean('has_secondary_role')->default(false);
            $table->string('secondary_role')->nullable();

            // operation area
            $table->string('operation_area')->nullable(); // local , pan india , state

            // company info
            $table->string('company_name')->nullable();
            $table->string('gst_in')->nullable();

            // state and city 
            $table->string('state')->nullable();
            $table->string('city')->nullable();

            // certificate
            $table->string('udyam_certificate')->nullable();
            $table->string('udyam_verified_at')->nullable();




            $table->rememberToken();
            $table->timestamps();

            $table->unique('email');
            $table->unique('mobile');
            $table->index('primary_role');
            $table->index('company_name');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
