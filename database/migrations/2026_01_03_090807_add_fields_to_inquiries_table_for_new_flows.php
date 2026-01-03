<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            // Remove brand_id requirement (nullable for now, can be dealer/converter/machine_dealer)
            $table->foreignId('brand_id')->nullable()->change();
            
            // Add poster info (polymorphic-style)
            $table->foreignId('poster_id')->nullable()->after('id');
            $table->string('poster_type')->nullable()->after('poster_id'); // dealer, converter, brand, machine_dealer
            
            // Add inquiry type and intent
            $table->string('inquiry_type')->nullable()->after('title'); // material, machine, job
            $table->string('intent')->nullable()->after('inquiry_type'); // buy, sell
            
            // Add fields for material inquiries
            $table->string('size')->nullable()->after('quantity_unit'); // e.g., 28x40
            $table->decimal('price', 15, 2)->nullable()->after('size');
            $table->string('price_unit')->nullable()->after('price'); // per_sheet, per_kg
            $table->boolean('price_negotiable')->default(true)->after('price_unit');
            $table->text('approx_price_note')->nullable()->after('price_negotiable');
            
            // Add fields for machine inquiries
            $table->string('machine_condition')->nullable()->after('approx_price_note'); // Brand New, Excellent, Working Condition, Needs Repair
            $table->foreignId('machine_listing_id')->nullable()->constrained('machine_listings')->nullOnDelete()->after('machine_condition');
            
            // Add fields for job inquiries
            $table->string('job_type')->nullable()->after('machine_listing_id'); // e.g., Rigid Boxes
            $table->integer('timeline_days')->nullable()->after('job_type');
            
            // Add attachments (already have json attachments, but add explicit field)
            $table->json('attachment_paths')->nullable()->after('specs');
            
            // Add payment tracking
            $table->boolean('posting_fee_paid')->default(false)->after('attachment_paths');
            $table->decimal('posting_fee_amount', 10, 2)->nullable()->after('posting_fee_paid');
            
            $table->index('poster_id');
            $table->index('poster_type');
            $table->index('inquiry_type');
            $table->index('intent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropForeign(['machine_listing_id']);
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
