<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Physical Lab Bookings
        Schema::create('lab_bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('booking_type', 50); // demonstration, workshop, training, testing, prototyping
            $table->string('status', 30)->default('pending'); // pending, confirmed, completed, cancelled
            $table->date('preferred_date');
            $table->time('preferred_time')->nullable();
            $table->string('institution_name')->nullable();
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->text('requirements')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // Institutional Lab Packages
        Schema::create('institutional_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('target_institution')->nullable(); // school, university, college, institute, government, industrial, research, startup
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();
            $table->string('image')->nullable();
            $table->json('equipment_list')->nullable();
            $table->json('includes')->nullable(); // courses, trainer, installation, warranty, maintenance
            $table->decimal('base_price', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->json('seo_meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['target_institution', 'is_active']);
        });

        // Institutional Package Products (BOM)
        Schema::create('institutional_package_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('package_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(1);
            $table->boolean('is_required')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['package_id', 'product_id']);
            $table->foreign('package_id')->references('id')->on('institutional_packages')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });

        // Demo Requests
        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('robot_model_id')->nullable()->index();
            $table->unsignedBigInteger('manufacturer_id')->nullable()->index();
            $table->string('status', 30)->default('pending');
            $table->string('institution_name')->nullable();
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->text('requirements')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('robot_model_id')->references('id')->on('robot_models')->nullOnDelete();
            $table->foreign('manufacturer_id')->references('id')->on('ai_robotics_manufacturers')->nullOnDelete();
        });

        // AI & Robotics Events
        Schema::create('ai_robotics_events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('event_type', 50); // webinar, workshop, competition, conference, demo, hackathon
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('location')->nullable();
            $table->string('location_type', 20)->default('online'); // online, offline, hybrid
            $table->datetime('starts_at');
            $table->datetime('ends_at')->nullable();
            $table->string('registration_url')->nullable();
            $table->decimal('ticket_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->integer('max_attendees')->nullable();
            $table->integer('current_attendees')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->json('seo_meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['event_type', 'starts_at']);
            $table->index(['is_active', 'is_featured']);
        });

        // Event Registrations
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('institution')->nullable();
            $table->string('status', 30)->default('registered');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('ai_robotics_events')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // AI & Robotics Projects (community)
        Schema::create('ai_robotics_projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->json('images')->nullable();
            $table->json('tags')->nullable();
            $table->json('robot_model_ids')->nullable();
            $table->json('product_ids')->nullable();
            $table->string('difficulty', 20)->default('intermediate');
            $table->string('status', 20)->default('draft'); // draft, published, archived
            $table->boolean('is_featured')->default(false);
            $table->json('seo_meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // AI & Robotics News/Articles
        Schema::create('ai_robotics_articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('article_type', 50)->default('news'); // news, product_launch, research, case_study, press_release
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->string('featured_image')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->json('tags')->nullable();
            $table->json('related_product_ids')->nullable();
            $table->json('related_course_ids')->nullable();
            $table->json('related_robot_model_ids')->nullable();
            $table->string('status', 20)->default('draft');
            $table->datetime('published_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->json('seo_meta')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['article_type', 'status']);
            $table->index(['published_at', 'is_featured']);
        });

        // AI & Robotics Settings
        Schema::create('ai_robotics_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_robotics_settings');
        Schema::dropIfExists('ai_robotics_articles');
        Schema::dropIfExists('ai_robotics_projects');
        Schema::dropIfExists('event_registrations');
        Schema::dropIfExists('ai_robotics_events');
        Schema::dropIfExists('demo_requests');
        Schema::dropIfExists('institutional_package_product');
        Schema::dropIfExists('institutional_packages');
        Schema::dropIfExists('lab_bookings');
    }
};
