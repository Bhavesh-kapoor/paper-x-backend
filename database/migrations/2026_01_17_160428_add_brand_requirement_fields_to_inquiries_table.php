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
            // Brand requirement specific fields
            if (!Schema::hasColumn('inquiries', 'requirement_type')) {
                $table->string('requirement_type')->nullable()->after('inquiry_type'); 
                // Packaging, Printing, Packaging + Printing, Corporate Gifting / Stationery
            }
            if (!Schema::hasColumn('inquiries', 'packaging_type')) {
                $table->string('packaging_type')->nullable()->after('requirement_type'); 
                // Conditional, shown only when posting requirement
            }
            if (!Schema::hasColumn('inquiries', 'quantity_range')) {
                $table->string('quantity_range')->nullable()->after('quantity_unit'); 
                // Range in pieces (e.g., "1000-5000", "5000-10000")
            }
            if (!Schema::hasColumn('inquiries', 'timeline')) {
                $table->string('timeline')->nullable()->after('timeline_days'); 
                // Emergency (Urgent), 3-5 Days, Flexible
            }
            if (!Schema::hasColumn('inquiries', 'special_needs')) {
                $table->text('special_needs')->nullable()->after('description'); 
                // Any special needs text
            }
            if (!Schema::hasColumn('inquiries', 'design_attachments')) {
                $table->json('design_attachments')->nullable()->after('attachment_paths'); 
                // Photos/videos/design ideas (array of file paths)
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn([
                'requirement_type',
                'packaging_type',
                'quantity_range',
                'timeline',
                'special_needs',
                'design_attachments',
            ]);
        });
    }
};
