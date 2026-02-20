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
        Schema::table('inquiries', function (Blueprint $table) {
            // Remove brand_id requirement (nullable for now, can be dealer/converter/machine_dealer)
            if (Schema::hasColumn('inquiries', 'brand_id')) {
                $table->foreignId('brand_id')->nullable()->change();
            }
            
            // Add poster info (polymorphic-style) - only if doesn't exist
            if (!Schema::hasColumn('inquiries', 'poster_id')) {
                $table->foreignId('poster_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('inquiries', 'poster_type')) {
                $table->string('poster_type')->nullable()->after('poster_id'); // dealer, converter, brand, machine_dealer
            }
            
            // Add inquiry type and intent - only if doesn't exist
            if (!Schema::hasColumn('inquiries', 'inquiry_type')) {
                $table->string('inquiry_type')->nullable()->after('title'); // material, machine, job
            }
            if (!Schema::hasColumn('inquiries', 'intent')) {
                $table->string('intent')->nullable()->after('inquiry_type'); // buy, sell
            }
            
            // Add fields for material inquiries - only if doesn't exist
            if (!Schema::hasColumn('inquiries', 'size')) {
                $table->string('size')->nullable()->after('quantity_unit'); // e.g., 28x40
            }
            if (!Schema::hasColumn('inquiries', 'price')) {
                $table->decimal('price', 15, 2)->nullable()->after('size');
            }
            if (!Schema::hasColumn('inquiries', 'price_unit')) {
                $table->string('price_unit')->nullable()->after('price'); // per_sheet, per_kg
            }
            if (!Schema::hasColumn('inquiries', 'price_negotiable')) {
                $table->boolean('price_negotiable')->default(true)->after('price_unit');
            }
            if (!Schema::hasColumn('inquiries', 'approx_price_note')) {
                $table->text('approx_price_note')->nullable()->after('price_negotiable');
            }
            
            // Add fields for machine inquiries - only if doesn't exist
            if (!Schema::hasColumn('inquiries', 'machine_condition')) {
                $table->string('machine_condition')->nullable()->after('approx_price_note'); // Brand New, Excellent, Working Condition, Needs Repair
            }
            if (!Schema::hasColumn('inquiries', 'machine_listing_id')) {
                $table->foreignId('machine_listing_id')->nullable()->after('machine_condition');
            }
            
            // Add fields for job inquiries - only if doesn't exist
            if (!Schema::hasColumn('inquiries', 'job_type')) {
                $table->string('job_type')->nullable()->after('machine_listing_id'); // e.g., Rigid Boxes
            }
            if (!Schema::hasColumn('inquiries', 'timeline_days')) {
                $table->integer('timeline_days')->nullable()->after('job_type');
            }
            
            // Add attachments (already have json attachments, but add explicit field) - only if doesn't exist
            if (!Schema::hasColumn('inquiries', 'attachment_paths')) {
                $table->json('attachment_paths')->nullable()->after('specs');
            }
            
            // Add payment tracking - only if doesn't exist
            if (!Schema::hasColumn('inquiries', 'posting_fee_paid')) {
                $table->boolean('posting_fee_paid')->default(false)->after('attachment_paths');
            }
            if (!Schema::hasColumn('inquiries', 'posting_fee_amount')) {
                $table->decimal('posting_fee_amount', 10, 2)->nullable()->after('posting_fee_paid');
            }
        });

        // Add indexes only if columns exist and indexes don't exist
        if (DB::getDriverName() === 'mysql') {
            $indexes = DB::select("SHOW INDEXES FROM inquiries");
            $indexNames = array_column($indexes, 'Key_name');

            Schema::table('inquiries', function (Blueprint $table) use ($indexNames) {
                if (Schema::hasColumn('inquiries', 'poster_id') && !in_array('inquiries_poster_id_index', $indexNames)) {
                    $table->index('poster_id');
                }
                if (Schema::hasColumn('inquiries', 'poster_type') && !in_array('inquiries_poster_type_index', $indexNames)) {
                    $table->index('poster_type');
                }
                if (Schema::hasColumn('inquiries', 'inquiry_type') && !in_array('inquiries_inquiry_type_index', $indexNames)) {
                    $table->index('inquiry_type');
                }
                if (Schema::hasColumn('inquiries', 'intent') && !in_array('inquiries_intent_index', $indexNames)) {
                    $table->index('intent');
                }
            });
        } else {
            Schema::table('inquiries', function (Blueprint $table) {
                if (Schema::hasColumn('inquiries', 'poster_id')) {
                    $table->index('poster_id');
                }
                if (Schema::hasColumn('inquiries', 'poster_type')) {
                    $table->index('poster_type');
                }
                if (Schema::hasColumn('inquiries', 'inquiry_type')) {
                    $table->index('inquiry_type');
                }
                if (Schema::hasColumn('inquiries', 'intent')) {
                    $table->index('intent');
                }
            });
        }

        // Add foreign key constraint only if machine_listings table exists
        if (Schema::hasTable('machine_listings') && Schema::hasColumn('inquiries', 'machine_listing_id')) {
            if (DB::getDriverName() === 'mysql') {
                $foreignKeys = DB::select("
                    SELECT CONSTRAINT_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'inquiries'
                    AND COLUMN_NAME = 'machine_listing_id'
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                ");
                if (!empty($foreignKeys)) {
                    return;
                }
            }
            Schema::table('inquiries', function (Blueprint $table) {
                $table->foreign('machine_listing_id')
                    ->references('id')
                    ->on('machine_listings')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            // Drop foreign key only if it exists
            if (Schema::hasTable('machine_listings')) {
                try {
                    $table->dropForeign(['machine_listing_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist, ignore
                }
            }
            
            $table->dropIndex(['poster_id']);
            $table->dropIndex(['poster_type']);
            $table->dropIndex(['inquiry_type']);
            $table->dropIndex(['intent']);
            
            $table->dropColumn([
                'poster_id',
                'poster_type',
                'inquiry_type',
                'intent',
                'size',
                'price',
                'price_unit',
                'price_negotiable',
                'approx_price_note',
                'machine_condition',
                'machine_listing_id',
                'job_type',
                'timeline_days',
                'attachment_paths',
                'posting_fee_paid',
                'posting_fee_amount',
            ]);
            
            $table->foreignId('brand_id')->nullable(false)->change();
        });
    }
};
