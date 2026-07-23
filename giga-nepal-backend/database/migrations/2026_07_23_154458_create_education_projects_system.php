<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Education Projects - core project records
        Schema::create('education_projects', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->string('category', 100)->index();
            $table->string('subcategory', 100)->nullable()->index();
            $table->string('skill_level', 50)->default('beginner')->index();
            $table->string('age_group', 50)->nullable();
            $table->string('grade_level', 50)->nullable();
            $table->integer('estimated_duration_minutes')->nullable();
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->text('learning_outcomes')->nullable();
            $table->text('required_prior_knowledge')->nullable();
            $table->string('main_controller', 100)->nullable()->index();
            $table->json('supported_controllers')->nullable();
            $table->json('sensor_types')->nullable();
            $table->json('actuator_types')->nullable();
            $table->json('communication_methods')->nullable();
            $table->text('power_requirements')->nullable();
            $table->text('tools_required')->nullable();
            $table->text('safety_warnings')->nullable();
            $table->text('wiring_instructions')->nullable();
            $table->json('pin_mapping')->nullable();
            $table->text('assembly_steps')->nullable();
            $table->text('testing_procedure')->nullable();
            $table->text('calibration_procedure')->nullable();
            $table->text('troubleshooting')->nullable();
            $table->text('expected_output')->nullable();
            $table->json('project_images')->nullable();
            $table->json('diagrams')->nullable();
            $table->json('videos')->nullable();
            $table->json('datasheets')->nullable();
            $table->json('downloads')->nullable();
            $table->foreignId('lms_course_id')->nullable()->constrained('lms_courses')->nullOnDelete();
            $table->foreignId('lms_quiz_id')->nullable()->nullOnDelete();
            $table->foreignId('lms_certificate_id')->nullable()->nullOnDelete();
            $table->json('source_references')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verification_status', 50)->default('draft')->index();
            $table->timestamp('last_reviewed_at')->nullable();
            $table->json('supported_marketplaces')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->integer('view_count')->default(0);
            $table->integer('enrollment_count')->default(0);
            $table->decimal('rating_avg', 3, 2)->nullable();
            $table->integer('rating_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['skill_level', 'category']);
            $table->index(['main_controller', 'category']);
        });

        // Education Project BOM Lines
        Schema::create('education_project_bom_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('education_project_id')->constrained()->cascadeOnDelete();
            $table->integer('line_no');
            $table->string('component_role', 100)->nullable();
            $table->string('product_category', 100)->nullable();
            $table->string('preferred_manufacturer', 200)->nullable();
            $table->string('preferred_mpn', 200)->nullable();
            $table->string('neogiga_sku', 100)->nullable();
            $table->integer('quantity')->default(1);
            $table->boolean('is_required')->default(true);
            $table->text('minimum_specification')->nullable();
            $table->text('compatibility_requirements')->nullable();
            $table->foreignId('preferred_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->json('alternative_product_ids')->nullable();
            $table->decimal('unit_price', 12, 4)->nullable();
            $table->decimal('extended_price', 14, 4)->nullable();
            $table->boolean('in_local_stock')->default(false);
            $table->boolean('in_global_stock')->default(false);
            $table->integer('lead_time_days')->nullable();
            $table->string('datasheet_url', 500)->nullable();
            $table->text('product_notes')->nullable();
            $table->timestamps();

            $table->index(['education_project_id', 'line_no']);
            $table->index('preferred_mpn');
            $table->index('preferred_product_id');
        });

        // Education Project Code Files
        Schema::create('education_project_code_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('education_project_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('target_board', 100)->nullable();
            $table->string('language', 50)->default('arduino');
            $table->string('version', 20)->default('1.0');
            $table->json('dependencies')->nullable();
            $table->json('libraries')->nullable();
            $table->json('file_tree')->nullable();
            $table->longText('source_code');
            $table->text('build_instructions')->nullable();
            $table->text('upload_instructions')->nullable();
            $table->text('expected_serial_output')->nullable();
            $table->json('configuration_values')->nullable();
            $table->string('license', 50)->default('MIT');
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verification_status', 50)->default('draft');
            $table->timestamp('last_tested_at')->nullable();
            $table->integer('download_count')->default(0);
            $table->timestamps();

            $table->index(['education_project_id', 'language']);
        });

        // Sensor Knowledge Base
        Schema::create('sensor_knowledge', function (Blueprint $table) {
            $table->id();
            $table->string('sensor_type', 100)->unique();
            $table->string('display_name', 200);
            $table->text('function_description')->nullable();
            $table->text('measurement_principle')->nullable();
            $table->string('input_output_type', 50)->nullable();
            $table->string('voltage_range', 50)->nullable();
            $table->string('current_consumption', 50)->nullable();
            $table->string('interface', 50)->nullable();
            $table->string('range', 100)->nullable();
            $table->string('accuracy', 100)->nullable();
            $table->string('resolution', 100)->nullable();
            $table->string('response_time', 100)->nullable();
            $table->text('operating_conditions')->nullable();
            $table->text('calibration_notes')->nullable();
            $table->json('compatible_controllers')->nullable();
            $table->json('compatible_libraries')->nullable();
            $table->text('wiring_examples')->nullable();
            $table->text('code_examples')->nullable();
            $table->json('applications')->nullable();
            $table->text('limitations')->nullable();
            $table->text('safety_notes')->nullable();
            $table->json('alternative_product_ids')->nullable();
            $table->json('premium_product_ids')->nullable();
            $table->json('budget_product_ids')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Product Recommendations
        Schema::create('product_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recommended_product_id')->constrained('products')->cascadeOnDelete();
            $table->string('recommendation_type', 50)->index();
            $table->decimal('score', 5, 4)->default(0);
            $table->text('explanation')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'recommended_product_id', 'recommendation_type']);
            $table->index('score');
        });

        // Frequently Bought Together
        Schema::create('frequently_bought_together', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('companion_product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('co_occurrence_count')->default(0);
            $table->decimal('confidence', 5, 4)->default(0);
            $table->decimal('lift', 5, 4)->default(0);
            $table->string('source', 50)->default('orders');
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'companion_product_id', 'source']);
            $table->index('co_occurrence_count');
        });

        // Product Search Events (for analytics and recommendations)
        Schema::create('product_search_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 100)->nullable()->index();
            $table->string('search_query', 500);
            $table->string('normalized_query', 500)->nullable();
            $table->string('search_type', 50)->default('text');
            $table->integer('results_count')->default(0);
            $table->foreignId('clicked_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('marketplace_id', 50)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('search_query');
            $table->index('user_id');
            $table->index('created_at');
        });

        // Seller Market Opportunities
        Schema::create('seller_market_opportunities', function (Blueprint $table) {
            $table->id();
            $table->string('mpn', 200)->index();
            $table->string('product_name', 500)->nullable();
            $table->string('brand', 200)->nullable();
            $table->string('category', 100)->nullable();
            $table->decimal('demand_score', 5, 2)->default(0);
            $table->integer('search_volume')->default(0);
            $table->decimal('search_growth', 5, 2)->default(0);
            $table->integer('order_count')->default(0);
            $table->integer('rfq_count')->default(0);
            $table->integer('bom_occurrence')->default(0);
            $table->integer('current_supply')->default(0);
            $table->json('regional_demand')->nullable();
            $table->string('opportunity_reason', 255)->nullable();
            $table->string('marketplace_id', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('demand_score');
            $table->index('search_volume');
        });

        // Behavioral Automation Rules
        Schema::create('behavioral_automation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_name', 200);
            $table->string('trigger_type', 100)->index();
            $table->json('trigger_conditions')->nullable();
            $table->json('action_config')->nullable();
            $table->integer('delay_minutes')->default(0);
            $table->integer('frequency_cap_hours')->nullable();
            $table->json('exit_conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // Behavioral Automation Runs (log of triggered automations)
        Schema::create('behavioral_automation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('behavioral_automation_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('trigger_event', 100);
            $table->string('action_taken', 100);
            $table->string('channel', 50)->default('email');
            $table->string('status', 50)->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'trigger_event']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('behavioral_automation_runs');
        Schema::dropIfExists('behavioral_automation_rules');
        Schema::dropIfExists('seller_market_opportunities');
        Schema::dropIfExists('product_search_events');
        Schema::dropIfExists('frequently_bought_together');
        Schema::dropIfExists('product_recommendations');
        Schema::dropIfExists('sensor_knowledge');
        Schema::dropIfExists('education_project_code_files');
        Schema::dropIfExists('education_project_bom_lines');
        Schema::dropIfExists('education_projects');
    }
};
