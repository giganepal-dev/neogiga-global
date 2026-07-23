<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_warehouses', function (Blueprint $table) {
            // Add verification workflow
            $table->string('verification_status')->default('pending')->after('is_active'); // pending, verified, rejected, suspended
            $table->foreignId('verified_by')->nullable()->after('verification_status')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->text('verification_notes')->nullable()->after('verified_at');
            $table->text('rejection_reason')->nullable()->after('verification_notes');
            
            // Add operating details
            $table->json('operating_hours')->nullable()->after('address_line2'); // {"monday": "9:00-18:00", ...}
            $table->time('dispatch_cutoff_time')->nullable()->after('operating_hours');
            $table->integer('dispatch_cutoff_timezone_offset')->default(0)->after('dispatch_cutoff_time'); // hours from UTC
            
            // Add return address details
            $table->string('return_contact_name')->nullable()->after('phone');
            $table->string('return_email')->nullable()->after('return_contact_name');
            $table->string('return_phone')->nullable()->after('return_email');
            $table->text('return_address_line1')->nullable()->after('return_phone');
            $table->text('return_address_line2')->nullable()->after('return_address_line1');
            $table->foreignId('return_city_id')->nullable()->after('return_address_line2')->constrained('cities')->nullOnDelete();
            $table->foreignId('return_region_id')->nullable()->after('return_city_id')->constrained('regions')->nullOnDelete();
            $table->foreignId('return_country_id')->nullable()->after('return_region_id')->constrained('countries')->nullOnDelete();
            $table->string('return_postal_code')->nullable()->after('return_country_id');
            
            // Add marketplace coverage
            $table->json('marketplace_coverage')->nullable()->after('warehouse_type'); // ["global", "nepal", "india"]
            
            // Add document references
            $table->json('document_paths')->nullable()->after('metadata'); // paths to warehouse documents
            
            // Add indexes
            $table->index(['vendor_id', 'verification_status']);
            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_warehouses', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropForeign(['return_city_id']);
            $table->dropForeign(['return_region_id']);
            $table->dropForeign(['return_country_id']);
            
            $table->dropIndex(['vendor_id', 'verification_status']);
            $table->dropIndex('verification_status');
            
            $table->dropColumn([
                'verification_status',
                'verified_by',
                'verified_at',
                'verification_notes',
                'rejection_reason',
                'operating_hours',
                'dispatch_cutoff_time',
                'dispatch_cutoff_timezone_offset',
                'return_contact_name',
                'return_email',
                'return_phone',
                'return_address_line1',
                'return_address_line2',
                'return_city_id',
                'return_region_id',
                'return_country_id',
                'return_postal_code',
                'marketplace_coverage',
                'document_paths',
            ]);
        });
    }
};
