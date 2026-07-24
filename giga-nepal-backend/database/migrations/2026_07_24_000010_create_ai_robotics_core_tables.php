<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Robot Types
        Schema::create('robot_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Robot Applications
        Schema::create('robot_applications', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Robot Models
        Schema::create('robot_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('model_number')->nullable();
            $table->unsignedBigInteger('manufacturer_id')->nullable()->index();
            $table->unsignedBigInteger('robot_type_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('image')->nullable();
            $table->json('images')->nullable();
            $table->json('videos')->nullable();
            // Physical specs
            $table->decimal('payload_kg', 8, 2)->nullable();
            $table->decimal('reach_mm', 8, 2)->nullable();
            $table->integer('degrees_of_freedom')->nullable();
            $table->decimal('length_mm', 8, 2)->nullable();
            $table->decimal('width_mm', 8, 2)->nullable();
            $table->decimal('height_mm', 8, 2)->nullable();
            $table->decimal('weight_kg', 8, 2)->nullable();
            $table->decimal('speed_mps', 6, 2)->nullable();
            // Power
            $table->string('battery_type')->nullable();
            $table->integer('battery_runtime_min')->nullable();
            $table->integer('charging_time_min')->nullable();
            // Sensors
            $table->json('sensors')->nullable();
            $table->string('camera_system')->nullable();
            $table->string('lidar')->nullable();
            $table->string('radar')->nullable();
            // Compute
            $table->string('compute_platform')->nullable();
            $table->string('ai_accelerator')->nullable();
            $table->string('operating_system')->nullable();
            $table->boolean('ros_support')->default(false);
            $table->boolean('ros2_support')->default(false);
            $table->json('programming_languages')->nullable();
            $table->boolean('sdk_available')->default(false);
            $table->boolean('api_available')->default(false);
            $table->boolean('simulation_support')->default(false);
            $table->boolean('digital_twin_support')->default(false);
            // Environment
            $table->string('indoor_outdoor')->nullable(); // indoor, outdoor, both
            $table->string('ip_rating')->nullable();
            $table->json('certifications')->nullable();
            $table->json('safety_features')->nullable();
            // Documentation
            $table->string('documentation_url')->nullable();
            $table->string('datasheet_url')->nullable();
            $table->string('cad_files_url')->nullable();
            $table->string('software_download_url')->nullable();
            // Commerce
            $table->decimal('global_price', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            // SEO
            $table->json('seo_meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'is_featured']);
            $table->index(['robot_type_id', 'is_active']);
        });

        // Robot Model Applications pivot
        Schema::create('robot_model_application', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('robot_model_id');
            $table->unsignedBigInteger('robot_application_id');
            $table->timestamps();

            $table->unique(['robot_model_id', 'robot_application_id']);
            $table->foreign('robot_model_id')->references('id')->on('robot_models')->cascadeOnDelete();
            $table->foreign('robot_application_id')->references('id')->on('robot_applications')->cascadeOnDelete();
        });

        // Robot Model Categories pivot (links to existing product categories)
        Schema::create('robot_model_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('robot_model_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamps();

            $table->unique(['robot_model_id', 'category_id']);
            $table->foreign('robot_model_id')->references('id')->on('robot_models')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('product_categories')->cascadeOnDelete();
        });

        // Robot Compatible Products
        Schema::create('robot_compatible_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('robot_model_id');
            $table->unsignedBigInteger('product_id');
            $table->string('relationship')->default('accessory'); // accessory, replacement, recommended
            $table->timestamps();

            $table->unique(['robot_model_id', 'product_id', 'relationship']);
            $table->foreign('robot_model_id')->references('id')->on('robot_models')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        // Robot Comparison
        Schema::create('robot_comparisons', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('robot_model_ids');
            $table->json('comparison_fields')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // AI Models Directory
        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('provider')->nullable();
            $table->string('model_type')->nullable(); // vision, nlp, speech, generative, reinforcement
            $table->json('supported_tasks')->nullable();
            $table->string('license_type')->nullable(); // open_source, commercial, proprietary
            $table->string('license_name')->nullable();
            $table->json('input_types')->nullable();
            $table->json('output_types')->nullable();
            $table->json('hardware_requirements')->nullable();
            $table->json('supported_accelerators')->nullable();
            $table->boolean('edge_compatible')->default(false);
            $table->boolean('cloud_compatible')->default(false);
            $table->json('robotics_use_cases')->nullable();
            $table->json('cv_use_cases')->nullable();
            $table->json('nlp_use_cases')->nullable();
            $table->string('documentation_url')->nullable();
            $table->string('download_url')->nullable();
            $table->string('github_url')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->json('seo_meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['model_type', 'is_active']);
            $table->index(['provider', 'is_active']);
        });

        // AI Model Compatible Hardware
        Schema::create('ai_model_hardware', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_model_id');
            $table->unsignedBigInteger('product_id');
            $table->boolean('officially_supported')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['ai_model_id', 'product_id']);
            $table->foreign('ai_model_id')->references('id')->on('ai_models')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        // Manufacturers (for AI & Robotics)
        Schema::create('ai_robotics_manufacturers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('country')->nullable();
            $table->json('regions_served')->nullable();
            $table->json('certifications')->nullable();
            $table->string('website_url')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->boolean('is_robot_manufacturer')->default(false);
            $table->boolean('is_ai_hardware_manufacturer')->default(false);
            $table->boolean('is_software_provider')->default(false);
            $table->boolean('is_active')->default(true);
            $table->json('seo_meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Integrators
        Schema::create('ai_robotics_integrators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('country')->nullable();
            $table->json('regions_served')->nullable();
            $table->json('services')->nullable();
            $table->json('certifications')->nullable();
            $table->string('website_url')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('seo_meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Learning Paths
        Schema::create('learning_paths', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('level')->default('beginner'); // beginner, intermediate, advanced
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('estimated_hours')->nullable();
            $table->json('prerequisites')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->json('seo_meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Learning Path Courses pivot
        Schema::create('learning_path_course', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('learning_path_id');
            $table->unsignedBigInteger('course_id');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->unique(['learning_path_id', 'course_id']);
            $table->foreign('learning_path_id')->references('id')->on('learning_paths')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('lms_courses')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_path_course');
        Schema::dropIfExists('learning_paths');
        Schema::dropIfExists('ai_robotics_integrators');
        Schema::dropIfExists('ai_robotics_manufacturers');
        Schema::dropIfExists('ai_model_hardware');
        Schema::dropIfExists('ai_models');
        Schema::dropIfExists('robot_comparisons');
        Schema::dropIfExists('robot_compatible_products');
        Schema::dropIfExists('robot_model_category');
        Schema::dropIfExists('robot_model_application');
        Schema::dropIfExists('robot_models');
        Schema::dropIfExists('robot_applications');
        Schema::dropIfExists('robot_types');
    }
};
