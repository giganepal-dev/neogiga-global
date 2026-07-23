<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_offers', function (Blueprint $table) {
            // Add marketplace association
            $table->foreignId('marketplace_id')->nullable()->after('warehouse_id')
                  ->constrained('marketplaces')->nullOnDelete();
            
            // Add approval workflow fields
            $table->string('approval_status')->default('pending')->after('status'); // pending, approved, rejected, suspended
            $table->foreignId('approved_by')->nullable()->after('approval_status')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->text('approval_notes')->nullable()->after('approved_at');
            $table->text('rejection_reason')->nullable()->after('approval_notes');
            
            // Add product condition and identification fields
            $table->string('date_code')->nullable()->after('conditions');
            $table->string('condition_grade')->default('new')->after('date_code'); // new, refurbished, used, damaged
            $table->string('packaging_type')->default('original')->after('condition_grade'); // original, bulk, retail
            $table->string('lot_number')->nullable()->after('packaging_type');
            $table->string('country_of_origin')->nullable()->after('lot_number');
            
            // Add offer validity dates
            $table->date('offer_start_date')->nullable()->after('price_valid_until');
            $table->date('offer_end_date')->nullable()->after('offer_start_date');
            
            // Add warranty info
            $table->string('warranty_type')->nullable()->after('country_of_origin'); // manufacturer, seller, none
            $table->string('warranty_period')->nullable()->after('warranty_type'); // e.g., "12 months"
            $table->text('warranty_terms')->nullable()->after('warranty_period');
            
            // Add selling status tracking
            $table->boolean('is_published')->default(false)->after('approval_status');
            $table->timestamp('published_at')->nullable()->after('is_published');
            $table->timestamp('paused_at')->nullable()->after('published_at');
            $table->text('pause_reason')->nullable()->after('paused_at');
            
            // Add indexes
            $table->index(['marketplace_id', 'approval_status']);
            $table->index(['seller_id', 'approval_status']);
            $table->index(['canonical_product_id', 'marketplace_id', 'approval_status']);
        });
    }

    public function down(): void
    {
        Schema::table('seller_offers', function (Blueprint $table) {
            $table->dropForeign(['marketplace_id']);
            $table->dropForeign(['approved_by']);
            $table->dropIndex(['marketplace_id', 'approval_status']);
            $table->dropIndex(['seller_id', 'approval_status']);
            $table->dropIndex(['canonical_product_id', 'marketplace_id', 'approval_status']);
            
            $table->dropColumn([
                'marketplace_id',
                'approval_status',
                'approved_by',
                'approved_at',
                'approval_notes',
                'rejection_reason',
                'date_code',
                'condition_grade',
                'packaging_type',
                'lot_number',
                'country_of_origin',
                'offer_start_date',
                'offer_end_date',
                'warranty_type',
                'warranty_period',
                'warranty_terms',
                'is_published',
                'published_at',
                'paused_at',
                'pause_reason',
            ]);
        });
    }
};
