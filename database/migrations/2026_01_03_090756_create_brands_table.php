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
        // Check if brands table already exists (from previous migration)
        if (Schema::hasTable('brands')) {
            // Alter existing table to add new columns for user brand profiles
            Schema::table('brands', function (Blueprint $table) {
                // Add user_id column if it doesn't exist
                if (!Schema::hasColumn('brands', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete()->after('id');
                }
                // Add company_name if it doesn't exist
                if (!Schema::hasColumn('brands', 'company_name')) {
                    $table->string('company_name')->nullable()->after('name');
                }
                // Add brand_name if it doesn't exist
                if (!Schema::hasColumn('brands', 'brand_name')) {
                    $table->string('brand_name')->nullable()->after('company_name');
                }
                // Add contact_person_name if it doesn't exist
                if (!Schema::hasColumn('brands', 'contact_person_name')) {
                    $table->string('contact_person_name')->nullable()->after('brand_name');
                }
                // Add mobile if it doesn't exist
                if (!Schema::hasColumn('brands', 'mobile')) {
                    $table->string('mobile')->nullable()->after('contact_person_name');
                }
                // Add email if it doesn't exist
                if (!Schema::hasColumn('brands', 'email')) {
                    $table->string('email')->nullable()->after('mobile');
                }
                // Add gst if it doesn't exist
                if (!Schema::hasColumn('brands', 'gst')) {
                    $table->string('gst')->nullable()->after('email');
                }
                // Add city if it doesn't exist
                if (!Schema::hasColumn('brands', 'city')) {
                    $table->string('city')->nullable()->after('gst');
                }
                // Add location if it doesn't exist
                if (!Schema::hasColumn('brands', 'location')) {
                    $table->string('location')->nullable()->after('city');
                }
                // Add latitude if it doesn't exist
                if (!Schema::hasColumn('brands', 'latitude')) {
                    $table->decimal('latitude', 10, 8)->nullable()->after('location');
                }
                // Add longitude if it doesn't exist
                if (!Schema::hasColumn('brands', 'longitude')) {
                    $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
                }
                // Add status if it doesn't exist
                if (!Schema::hasColumn('brands', 'status')) {
                    $table->enum('status', ['PENDING', 'ACTIVE', 'INACTIVE'])->default('PENDING')->after('longitude');
                }
                // Add profile_complete if it doesn't exist
                if (!Schema::hasColumn('brands', 'profile_complete')) {
                    $table->boolean('profile_complete')->default(false)->after('status');
                }
            });

            // Add indexes if they don't exist (using raw SQL check)
            $indexes = DB::select("SHOW INDEXES FROM brands");
            $indexNames = array_column($indexes, 'Key_name');
            
            if (!in_array('brands_status_index', $indexNames) && Schema::hasColumn('brands', 'status')) {
                Schema::table('brands', function (Blueprint $table) {
                    $table->index('status', 'brands_status_index');
                });
            }
            
            if (!in_array('brands_profile_complete_index', $indexNames) && Schema::hasColumn('brands', 'profile_complete')) {
                Schema::table('brands', function (Blueprint $table) {
                    $table->index('profile_complete', 'brands_profile_complete_index');
                });
            }
        } else {
            // Create new table if it doesn't exist
            Schema::create('brands', function (Blueprint $table) {
                $table->id();
                $table->string('name'); // For mill brands
                $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
                $table->string('company_name')->nullable(); // For user brand profiles
                $table->string('brand_name')->nullable();
                $table->string('contact_person_name')->nullable();
                $table->string('mobile')->nullable();
                $table->string('email')->nullable();
                $table->string('gst')->nullable();
                $table->string('city')->nullable();
                $table->string('location')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->enum('status', ['PENDING', 'ACTIVE', 'INACTIVE'])->default('PENDING');
                $table->boolean('profile_complete')->default(false);
                $table->timestamps();

                $table->index('status');
                $table->index('profile_complete');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
