<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * PHASE 2: Canonical Product Catalog Architecture
     * Creates the foundation for multi-seller, multi-warehouse, multi-region product management
     */
    public function up(): void
    {
        // Create canonical products table (master product record)
        if (! Schema::hasTable('canonical_products')) {
            Schema::create('canonical_products', function (Blueprint $table) {
                $table->id();
                
                // Core identification
                $table->string('name', 500);
                $table->string('slug', 300)->unique()->index();
                $table->string('sku', 150)->nullable()->index(); // Internal SKU
                $table->string('mpn', 150)->nullable()->index(); // Manufacturer Part Number
                $table->string('gtin', 50)->nullable()->index(); // Global Trade Item Number
                $table->string('upc', 20)->nullable();
                $table->string('ean', 20)->nullable();
                $table->string('isbn', 20)->nullable();
                
                // Brand & Manufacturer
                $table->foreignId('brand_id')->nullable()->constrained('product_brands')->nullOnDelete();
                $table->foreignId('manufacturer_id')->nullable()->constrained('vendors')->nullOnDelete();
                
                // Classification
                $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
                $table->string('product_type', 100)->nullable(); // e.g., "Component", "Module", "Board"
                $table->string('series', 200)->nullable();
                $table->string('family', 200)->nullable();
                $table->string('model', 200)->nullable();
                
                // Descriptions
                $table->text('short_description')->nullable();
                $table->longText('description')->nullable();
                $table->json('features')->nullable(); // Array of feature strings
                $table->json('applications')->nullable(); // Array of application areas
                
                // Specifications (structured)
                $table->json('specifications')->nullable(); // Key-value pairs
                $table->json('attributes')->nullable(); // Category-specific attributes
                
                // Variants & Options
                $table->boolean('has_variants')->default(false);
                $table->json('variant_options')->nullable(); // e.g., ["color", "size"]
                
                // Packaging & Ordering
                $table->string('packaging_type', 100)->nullable(); // "Tape", "Tube", "Tray", "Bulk"
                $table->unsignedInteger('moq')->default(1); // Minimum Order Quantity
                $table->unsignedInteger('order_multiple')->default(1);
                $table->unsignedInteger('lead_time_days')->nullable();
                
                // Origin & Compliance
                $table->foreignId('country_of_origin_id')->nullable()->constrained('countries')->nullOnDelete();
                $table->foreignId('manufacturing_country_id')->nullable()->constrained('countries')->nullOnDelete();
                $table->string('warranty_period', 50)->nullable(); // e.g., "1 year", "90 days"
                $table->enum('condition', ['new', 'refurbished', 'used', 'obsolete'])->default('new');
                $table->enum('lifecycle_status', ['active', 'nrnd', 'end_of_life', 'obsolete'])->default('active');
                $table->boolean('rohs_compliant')->nullable();
                $table->boolean('reach_compliant')->nullable();
                $table->string('export_control_class', 50)->nullable(); // ECCN code
                $table->string('reach_svhc', 100)->nullable();
                
                // Status & Workflow
                $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'archived'])->default('draft');
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('published_at')->nullable();
                
                // SEO (base level)
                $table->string('meta_title', 300)->nullable();
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords', 500)->nullable();
                $table->json('seo_overrides')->nullable(); // Regional SEO overrides
                
                // Media references
                $table->unsignedInteger('primary_image_id')->nullable();
                $table->unsignedInteger('datasheet_count')->default(0);
                $table->unsignedInteger('image_count')->default(0);
                
                // Relationships tracking
                $table->unsignedInteger('related_products_count')->default(0);
                $table->unsignedInteger('compatible_products_count')->default(0);
                $table->unsignedInteger('alternates_count')->default(0);
                
                // BOM & Project usage
                $table->unsignedInteger('bom_usage_count')->default(0);
                
                // Quality & Completeness
                $table->unsignedTinyInteger('completeness_score')->default(0); // 0-100
                $table->json('missing_fields')->nullable(); // Array of missing required fields
                $table->json('quality_warnings')->nullable(); // Array of quality issues
                
                // Duplicate detection
                $table->string('normalized_name', 300)->nullable()->index(); // For duplicate detection
                $table->string('name_hash', 64)->nullable()->index(); // Hash for fuzzy matching
                
                // Audit
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                
                // Indexes for performance
                $table->index(['status', 'is_active']);
                $table->index(['category_id', 'status']);
                $table->index(['brand_id', 'status']);
                $table->index(['manufacturer_id', 'status']);
                $table->index(['lifecycle_status']);
                $table->index(['created_at']);
                $table->index(['published_at']);
            });
        }

        // Product variations (specific configurations of canonical product)
        if (! Schema::hasTable('product_variations')) {
            Schema::create('product_variations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('canonical_product_id')->constrained('canonical_products')->cascadeOnDelete();
                $table->string('sku', 150)->index();
                $table->string('mpn', 150)->nullable();
                $table->string('name', 300)->nullable(); // Variation-specific name
                $table->json('options')->nullable(); // e.g., {"color": "red", "size": "large"}
                $table->text('description')->nullable();
                $table->decimal('weight', 10, 4)->nullable();
                $table->string('weight_unit', 10)->default('g');
                $table->decimal('length', 10, 4)->nullable();
                $table->decimal('width', 10, 4)->nullable();
                $table->decimal('height', 10, 4)->nullable();
                $table->string('dimension_unit', 10)->default('mm');
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->unique(['canonical_product_id', 'sku']);
                // Index on sku is already created by unique index above, avoid duplicate
                $table->index(['mpn']);
            });
        }

        // Seller offers (each seller's offer for a canonical product)
        if (! Schema::hasTable('seller_offers')) {
            Schema::create('seller_offers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('canonical_product_id')->constrained('canonical_products')->cascadeOnDelete();
                $table->foreignId('variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
                $table->foreignId('seller_id')->constrained('vendors')->cascadeOnDelete();
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
                
                // Pricing
                $table->decimal('base_price', 15, 4);
                $table->decimal('sale_price', 15, 4)->nullable();
                $table->decimal('cost_price', 15, 4)->nullable(); // Seller's cost
                $table->string('currency_code', 3)->default('USD');
                $table->date('price_valid_from')->nullable();
                $table->date('price_valid_until')->nullable();
                
                // Quantity breaks
                $table->json('quantity_breaks')->nullable(); // [{"min_qty": 10, "price": 9.99}, ...]
                $table->unsignedInteger('moq')->default(1);
                $table->unsignedInteger('order_multiple')->default(1);
                $table->unsignedInteger('max_order_qty')->nullable();
                
                // Stock
                $table->unsignedInteger('stock_quantity')->default(0);
                $table->unsignedInteger('reserved_quantity')->default(0);
                $table->unsignedInteger('incoming_quantity')->default(0);
                $table->boolean('allow_backorder')->default(false);
                $table->unsignedInteger('backorder_limit')->nullable();
                $table->boolean('allow_preorder')->default(false);
                $table->date('preorder_available_date')->nullable();
                
                // Fulfillment
                $table->unsignedInteger('lead_time_days')->nullable();
                $table->enum('fulfillment_type', ['in_stock', 'dropship', 'preorder', 'build_to_order'])->default('in_stock');
                $table->string('shipping_restrictions', 500)->nullable();
                
                // Status
                $table->enum('status', ['active', 'inactive', 'out_of_stock', 'discontinued'])->default('active');
                $table->boolean('is_featured')->default(false);
                $table->boolean('is_buybox_winner')->default(false);
                
                // Seller-specific info
                $table->string('seller_sku', 150)->nullable();
                $table->text('seller_notes')->nullable();
                $table->json('conditions')->nullable(); // Seller-specific conditions
                
                // Performance
                $table->unsignedInteger('sales_count')->default(0);
                $table->decimal('rating_average', 3, 2)->nullable();
                $table->unsignedInteger('rating_count')->default(0);
                
                // Audit
                $table->timestamp('last_synced_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
                
                // Indexes
                $table->index(['canonical_product_id', 'status']);
                $table->index(['seller_id', 'status']);
                $table->index(['warehouse_id', 'status']);
                $table->index(['status', 'is_buybox_winner']);
                $table->index(['base_price']);
                $table->index(['stock_quantity']);
            });
        }

        // Regional inventory (stock by region/marketplace)
        if (! Schema::hasTable('regional_inventory')) {
            Schema::create('regional_inventory', function (Blueprint $table) {
                $table->id();
                $table->foreignId('canonical_product_id')->constrained('canonical_products')->cascadeOnDelete();
                $table->foreignId('variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
                $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
                $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
                $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
                
                // Stock levels
                $table->unsignedInteger('quantity_available')->default(0);
                $table->unsignedInteger('quantity_reserved')->default(0);
                $table->unsignedInteger('quantity_incoming')->default(0);
                $table->unsignedInteger('quantity_damaged')->default(0);
                $table->unsignedInteger('quantity_quarantined')->default(0);
                
                // Reorder management
                $table->unsignedInteger('safety_stock')->default(0);
                $table->unsignedInteger('reorder_point')->default(0);
                $table->unsignedInteger('reorder_quantity')->default(0);
                
                // Batch/lot tracking (for batteries, sensitive items)
                $table->string('batch_number', 100)->nullable();
                $table->string('serial_number_prefix', 50)->nullable();
                $table->date('expiry_date')->nullable();
                $table->date('manufacture_date')->nullable();
                
                // Location
                $table->string('bin_location', 100)->nullable();
                $table->string('aisle', 50)->nullable();
                $table->string('shelf', 50)->nullable();
                
                // Valuation
                $table->decimal('average_cost', 15, 4)->nullable();
                $table->string('currency_code', 3)->default('USD');
                
                // Status
                $table->boolean('is_active')->default(true);
                $table->timestamp('last_counted_at')->nullable();
                $table->foreignId('last_counted_by')->nullable()->constrained('users')->nullOnDelete();
                
                // Audit
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                
                // Indexes
                $table->unique(['canonical_product_id', 'variation_id', 'warehouse_id', 'marketplace_id'], 'regional_inv_unique');
                $table->index(['marketplace_id', 'canonical_product_id']);
                $table->index(['country_id', 'canonical_product_id']);
                $table->index(['warehouse_id', 'canonical_product_id']);
                $table->index(['quantity_available']);
                $table->index(['expiry_date']);
            });
        }

        // Regional prices (price by country/marketplace)
        if (! Schema::hasTable('regional_prices')) {
            Schema::create('regional_prices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('canonical_product_id')->constrained('canonical_products')->cascadeOnDelete();
                $table->foreignId('variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
                $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
                $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
                $table->foreignId('seller_offer_id')->nullable()->constrained('seller_offers')->nullOnDelete();
                
                // Base pricing
                $table->decimal('base_price', 15, 4);
                $table->decimal('sale_price', 15, 4)->nullable();
                $table->decimal('compare_at_price', 15, 4)->nullable(); // MSRP or similar
                $table->decimal('cost_price', 15, 4)->nullable(); // Landed cost
                
                // Price components (traceable)
                $table->decimal('base_cost', 15, 4)->nullable(); // Seller base price
                $table->decimal('currency_adjustment', 15, 4)->default(0);
                $table->decimal('duty_amount', 15, 4)->default(0);
                $table->decimal('tax_amount', 15, 4)->default(0);
                $table->decimal('shipping_allocation', 15, 4)->default(0);
                $table->decimal('commission_amount', 15, 4)->default(0);
                $table->decimal('margin_amount', 15, 4)->default(0);
                
                // Quantity breaks
                $table->json('quantity_breaks')->nullable();
                
                // Customer group pricing
                $table->json('customer_group_prices')->nullable(); // {"retail": 10.99, "wholesale": 8.99}
                
                // Validity
                $table->string('currency_code', 3)->default('USD');
                $table->date('price_valid_from')->nullable();
                $table->date('price_valid_until')->nullable();
                $table->timestamp('last_calculated_at')->nullable();
                
                // Tax treatment
                $table->boolean('is_tax_inclusive')->default(false);
                $table->foreignId('tax_rule_id')->nullable()->constrained('tax_rules')->nullOnDelete();
                
                // Status
                $table->boolean('is_active')->default(true);
                $table->boolean('auto_calculated')->default(true);
                
                // Audit trail reference
                $table->foreignId('calculation_log_id')->nullable()->constrained('price_calculation_logs')->nullOnDelete();
                
                // Audit
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                
                // Indexes
                $table->unique(['canonical_product_id', 'variation_id', 'marketplace_id', 'country_id'], 'regional_price_unique');
                $table->index(['marketplace_id', 'canonical_product_id']);
                $table->index(['country_id', 'canonical_product_id']);
                $table->index(['base_price']);
                $table->index(['sale_price']);
            });
        }

        // Product relationships (enhanced)
        if (! Schema::hasTable('product_relationships')) {
            Schema::create('product_relationships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('canonical_product_id')->constrained('canonical_products')->cascadeOnDelete();
                $table->foreignId('related_product_id')->constrained('canonical_products')->cascadeOnDelete();
                $table->enum('relationship_type', [
                    'related',
                    'compatible',
                    'alternate',
                    'substitute_generic',
                    'substitute_higher_perf',
                    'substitute_lower_cost',
                    'frequently_bought_together',
                    'required_component',
                    'accessory',
                    'upgrade',
                    'downgrade'
                ])->default('related')->index();
                $table->text('reason')->nullable();
                $table->text('compatibility_notes')->nullable();
                $table->json('requirements')->nullable(); // Requirements for compatibility
                $table->boolean('is_mutual')->default(false); // Bidirectional relationship
                $table->unsignedInteger('sort_order')->default(100);
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                
                $table->unique(['canonical_product_id', 'related_product_id', 'relationship_type']);
                $table->index(['relationship_type', 'is_active']);
            });
        }

        // Product documents (datasheets, manuals, CAD, certs)
        if (! Schema::hasTable('product_documents')) {
            // Table may exist from previous migration, ensure it has all columns
            if (! Schema::hasColumn('product_documents', 'canonical_product_id')) {
                Schema::table('product_documents', function (Blueprint $table) {
                    $table->foreignId('canonical_product_id')->nullable()->after('id')->constrained('canonical_products')->nullOnDelete();
                });
            }
            
            // Add any missing columns
            if (! Schema::hasColumn('product_documents', 'document_type')) {
                Schema::table('product_documents', function (Blueprint $table) {
                    $table->enum('document_type', [
                        'datasheet',
                        'manual',
                        'cad_file',
                        'certification',
                        'compliance_doc',
                        'test_report',
                        'application_note',
                        'reference_design',
                        'firmware',
                        'software',
                        'video',
                        'other'
                    ])->default('datasheet')->after('title')->index();
                });
            }
            
            if (! Schema::hasColumn('product_documents', 'language')) {
                Schema::table('product_documents', function (Blueprint $table) {
                    $table->string('language', 10)->default('en')->after('document_type');
                });
            }
            
            if (! Schema::hasColumn('product_documents', 'version')) {
                Schema::table('product_documents', function (Blueprint $table) {
                    $table->string('version', 50)->nullable()->after('language');
                });
            }
            
            if (! Schema::hasColumn('product_documents', 'file_hash')) {
                Schema::table('product_documents', function (Blueprint $table) {
                    $table->string('file_hash', 64)->nullable()->after('file_path')->index(); // For deduplication
                });
            }
        }

        // Product images (enhanced with ordering and alt text)
        if (! Schema::hasTable('product_images')) {
            Schema::create('product_images', function (Blueprint $table) {
                $table->id();
                $table->foreignId('canonical_product_id')->constrained('canonical_products')->cascadeOnDelete();
                $table->foreignId('variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
                $table->string('url', 500);
                $table->string('thumbnail_url', 500)->nullable();
                $table->string('alt_text', 300)->nullable();
                $table->string('caption', 500)->nullable();
                $table->unsignedInteger('sort_order')->default(100);
                $table->boolean('is_primary')->default(false);
                $table->enum('image_type', ['main', 'gallery', 'lifestyle', 'technical', 'packaging', 'other'])->default('main');
                $table->string('photographer_credit', 200)->nullable();
                $table->string('license_type', 100)->nullable();
                $table->boolean('is_verified')->default(false); // Verified by admin/manufacturer
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                
                $table->index(['canonical_product_id', 'sort_order']);
                $table->index(['is_primary']);
            });
        }

        // Product reviews and ratings
        if (! Schema::hasTable('product_reviews')) {
            Schema::create('product_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('canonical_product_id')->constrained('canonical_products')->cascadeOnDelete();
                $table->foreignId('variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete(); // Verified purchase
                $table->unsignedTinyInteger('rating')->default(5);
                $table->string('title', 200)->nullable();
                $table->text('content')->nullable();
                $table->json('pros')->nullable(); // Array of pros
                $table->json('cons')->nullable(); // Array of cons
                $table->boolean('verified_purchase')->default(false);
                $table->boolean('is_approved')->default(false);
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('admin_response')->nullable();
                $table->timestamp('admin_response_at')->nullable();
                $table->unsignedInteger('helpful_count')->default(0);
                $table->unsignedInteger('not_helpful_count')->default(0);
                $table->unsignedInteger('report_count')->default(0);
                $table->json('images')->nullable(); // Array of image URLs
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();
                
                $table->index(['canonical_product_id', 'is_approved']);
                $table->index(['rating']);
                $table->index(['verified_purchase']);
            });
        }

        // Product questions and answers
        if (! Schema::hasTable('product_questions')) {
            Schema::create('product_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('canonical_product_id')->constrained('canonical_products')->cascadeOnDelete();
                $table->foreignId('variation_id')->nullable()->constrained('product_variations')->nullOnDelete();
                $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('question');
                $table->boolean('is_approved')->default(false);
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('answer_count')->default(0);
                $table->timestamps();
                
                $table->index(['canonical_product_id', 'is_approved']);
            });
        }

        if (! Schema::hasTable('product_answers')) {
            Schema::create('product_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('question_id')->constrained('product_questions')->cascadeOnDelete();
                $table->foreignId('canonical_product_id')->constrained('canonical_products')->cascadeOnDelete();
                $table->foreignId('vendor_id')->nullable()->constrained('vendors')->nullOnDelete(); // Seller/manufacturer answer
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('answer');
                $table->boolean('is_official')->default(false); // From manufacturer/seller
                $table->boolean('is_approved')->default(false);
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedInteger('helpful_count')->default(0);
                $table->timestamps();
                
                $table->index(['question_id']);
                $table->index(['is_official']);
            });
        }

        // Product view and engagement tracking
        if (! Schema::hasTable('product_view_logs')) {
            Schema::create('product_view_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('canonical_product_id');
                $table->unsignedBigInteger('variation_id')->nullable();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('session_id', 255)->nullable(); // varchar to match sessions.id
                $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
                $table->foreignId('country_id')->nullable()->constrained('countries')->nullOnDelete();
                $table->string('referrer', 500)->nullable();
                $table->string('utm_source', 100)->nullable();
                $table->string('utm_medium', 100)->nullable();
                $table->string('utm_campaign', 200)->nullable();
                $table->timestamp('viewed_at');
                
                $table->index(['canonical_product_id', 'viewed_at']);
                $table->index(['marketplace_id', 'viewed_at']);
                $table->index(['viewed_at']);
            });
        }

        // Duplicate detection queue
        if (! Schema::hasTable('product_duplicate_candidates')) {
            Schema::create('product_duplicate_candidates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_1_id')->constrained('canonical_products')->cascadeOnDelete();
                $table->foreignId('product_2_id')->constrained('canonical_products')->cascadeOnDelete();
                $table->decimal('similarity_score', 5, 4); // 0.0000 to 1.0000
                $table->enum('match_type', [
                    'exact_mpn',
                    'exact_sku',
                    'exact_gtin',
                    'similar_name',
                    'similar_specs',
                    'same_datasheet'
                ])->default('similar_name');
                $table->json('matching_fields')->nullable(); // Which fields matched
                $table->enum('status', ['pending', 'reviewed', 'merged', 'dismissed'])->default('pending');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamps();
                
                $table->unique(['product_1_id', 'product_2_id']);
                $table->index(['status', 'similarity_score']);
            });
        }

        // Product approval workflow history
        if (! Schema::hasTable('product_approval_history')) {
            Schema::create('product_approval_history', function (Blueprint $table) {
                $table->id();
                $table->foreignId('canonical_product_id')->constrained('canonical_products')->cascadeOnDelete();
                $table->enum('action', ['submitted', 'approved', 'rejected', 'returned_for_changes', 'archived', 'restored']);
                $table->foreignId('user_id')->constrained('users');
                $table->text('comments')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->json('changes_made')->nullable(); // Diff of changes
                $table->timestamps();
                
                $table->index(['canonical_product_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_approval_history');
        Schema::dropIfExists('product_duplicate_candidates');
        Schema::dropIfExists('product_view_logs');
        Schema::dropIfExists('product_answers');
        Schema::dropIfExists('product_questions');
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_documents');
        Schema::dropIfExists('product_relationships');
        Schema::dropIfExists('regional_prices');
        Schema::dropIfExists('regional_inventory');
        Schema::dropIfExists('seller_offers');
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('canonical_products');
    }
};
