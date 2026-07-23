<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_warehouses', function (Blueprint $table) {
            // Add verification workflow (only if not exists)
            if (! Schema::hasColumn('vendor_warehouses', 'verification_status')) {
                $table->string('verification_status')->default('pending')->after('is_active');
            }
            if (! Schema::hasColumn('vendor_warehouses', 'verified_by')) {
                $table->foreignId('verified_by')->nullable()->after('verification_status')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('vendor_warehouses', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }
            if (! Schema::hasColumn('vendor_warehouses', 'verification_notes')) {
                $table->text('verification_notes')->nullable()->after('verified_at');
            }
            if (! Schema::hasColumn('vendor_warehouses', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('verification_notes');
            }

            // Add operating details
            if (! Schema::hasColumn('vendor_warehouses', 'operating_hours')) {
                $table->json('operating_hours')->nullable()->after('address_line2');
            }
            if (! Schema::hasColumn('vendor_warehouses', 'dispatch_cutoff_time')) {
                $table->time('dispatch_cutoff_time')->nullable()->after('operating_hours');
            }
            if (! Schema::hasColumn('vendor_warehouses', 'dispatch_cutoff_timezone_offset')) {
                $table->integer('dispatch_cutoff_timezone_offset')->default(0)->after('dispatch_cutoff_time');
            }

            // Add return address details
            if (! Schema::hasColumn('vendor_warehouses', 'return_contact_name')) {
                $table->string('return_contact_name')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('vendor_warehouses', 'return_email')) {
                $table->string('return_email')->nullable()->after('return_contact_name');
            }
            if (! Schema::hasColumn('vendor_warehouses', 'return_phone')) {
                $table->string('return_phone')->nullable()->after('return_email');
            }
            if (! Schema::hasColumn('vendor_warehouses', 'return_address_line1')) {
                $table->text('return_address_line1')->nullable()->after('return_phone');
            }
            if (! Schema::hasColumn('vendor_warehouses', 'return_address_line2')) {
                $table->text('return_address_line2')->nullable()->after('return_address_line1');
            }
            if (! Schema::hasColumn('vendor_warehouses', 'return_city_id')) {
                $table->foreignId('return_city_id')->nullable()->after('return_address_line2')->constrained('cities')->nullOnDelete();
            }
            if (! Schema::hasColumn('vendor_warehouses', 'return_region_id')) {
                $table->foreignId('return_region_id')->nullable()->after('return_city_id')->constrained('regions')->nullOnDelete();
            }
            if (! Schema::hasColumn('vendor_warehouses', 'return_country_id')) {
                $table->foreignId('return_country_id')->nullable()->after('return_region_id')->constrained('countries')->nullOnDelete();
            }
            if (! Schema::hasColumn('vendor_warehouses', 'return_postal_code')) {
                $table->string('return_postal_code')->nullable()->after('return_country_id');
            }

            // Add marketplace coverage
            if (! Schema::hasColumn('vendor_warehouses', 'marketplace_coverage')) {
                $table->json('marketplace_coverage')->nullable()->after('warehouse_type');
            }

            // Add document references
            if (! Schema::hasColumn('vendor_warehouses', 'document_paths')) {
                $table->json('document_paths')->nullable()->after('metadata');
            }

            // Add indexes (only if not exists)
            if (! Schema::hasIndex('vendor_warehouses', 'vendor_warehouses_vendor_id_verification_status_index')) {
                $table->index(['vendor_id', 'verification_status']);
            }
            if (! Schema::hasIndex('vendor_warehouses', 'vendor_warehouses_verification_status_index')) {
                $table->index('verification_status');
            }
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
