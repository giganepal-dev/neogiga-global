<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createCourseCategories();
        $this->completeCourses();
        $this->completeSkillLevels();
        $this->completeProjects();
        $this->createModules();
        $this->completeLessons();
        $this->completeProjectComponents();
        $this->completeCodeSamples();
        $this->completeProductLinks();
        $this->createLearningTables();
    }

    public function down(): void
    {
        // Production safety: this migration is additive only. Do not drop LMS data automatically.
    }

    private function createCourseCategories(): void
    {
        $this->create('lms_course_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('seo_meta')->nullable();
            $table->timestamps();
        });
    }

    private function completeCourses(): void
    {
        $this->table('lms_courses', [
            'lms_course_category_id' => fn (Blueprint $t) => $t->unsignedBigInteger('lms_course_category_id')->nullable()->index(),
            'marketplace_id' => fn (Blueprint $t) => $t->unsignedBigInteger('marketplace_id')->nullable()->index(),
            'country_id' => fn (Blueprint $t) => $t->unsignedBigInteger('country_id')->nullable()->index(),
            'vendor_id' => fn (Blueprint $t) => $t->unsignedBigInteger('vendor_id')->nullable()->index(),
            'instructor_user_id' => fn (Blueprint $t) => $t->unsignedBigInteger('instructor_user_id')->nullable()->index(),
            'title' => fn (Blueprint $t) => $t->string('title')->nullable(),
            'slug' => fn (Blueprint $t) => $t->string('slug')->nullable()->unique(),
            'subtitle' => fn (Blueprint $t) => $t->string('subtitle')->nullable(),
            'description' => fn (Blueprint $t) => $t->longText('description')->nullable(),
            'level' => fn (Blueprint $t) => $t->string('level')->default('beginner')->index(),
            'status' => fn (Blueprint $t) => $t->string('status')->default('draft')->index(),
            'language' => fn (Blueprint $t) => $t->string('language', 12)->default('en')->index(),
            'estimated_minutes' => fn (Blueprint $t) => $t->unsignedInteger('estimated_minutes')->default(0),
            'thumbnail_url' => fn (Blueprint $t) => $t->string('thumbnail_url')->nullable(),
            'seo_title' => fn (Blueprint $t) => $t->string('seo_title')->nullable(),
            'seo_description' => fn (Blueprint $t) => $t->text('seo_description')->nullable(),
            'metadata' => fn (Blueprint $t) => $t->json('metadata')->nullable(),
            'published_at' => fn (Blueprint $t) => $t->timestamp('published_at')->nullable()->index(),
        ]);
    }

    private function completeSkillLevels(): void
    {
        $this->table('lms_skill_levels', [
            'name' => fn (Blueprint $t) => $t->string('name')->nullable(),
            'slug' => fn (Blueprint $t) => $t->string('slug')->nullable()->unique(),
            'description' => fn (Blueprint $t) => $t->text('description')->nullable(),
            'sort_order' => fn (Blueprint $t) => $t->unsignedInteger('sort_order')->default(0)->index(),
            'is_active' => fn (Blueprint $t) => $t->boolean('is_active')->default(true)->index(),
        ]);
    }

    private function completeProjects(): void
    {
        $this->table('lms_projects', [
            'lms_course_id' => fn (Blueprint $t) => $t->unsignedBigInteger('lms_course_id')->nullable()->index(),
            'lms_skill_level_id' => fn (Blueprint $t) => $t->unsignedBigInteger('lms_skill_level_id')->nullable()->index(),
            'marketplace_id' => fn (Blueprint $t) => $t->unsignedBigInteger('marketplace_id')->nullable()->index(),
            'country_id' => fn (Blueprint $t) => $t->unsignedBigInteger('country_id')->nullable()->index(),
            'title' => fn (Blueprint $t) => $t->string('title')->nullable(),
            'slug' => fn (Blueprint $t) => $t->string('slug')->nullable()->unique(),
            'summary' => fn (Blueprint $t) => $t->text('summary')->nullable(),
            'description' => fn (Blueprint $t) => $t->longText('description')->nullable(),
            'difficulty_level' => fn (Blueprint $t) => $t->string('difficulty_level')->default('beginner')->index(),
            'estimated_minutes' => fn (Blueprint $t) => $t->unsignedInteger('estimated_minutes')->default(0),
            'status' => fn (Blueprint $t) => $t->string('status')->default('draft')->index(),
            'thumbnail_url' => fn (Blueprint $t) => $t->string('thumbnail_url')->nullable(),
            'metadata' => fn (Blueprint $t) => $t->json('metadata')->nullable(),
            'published_at' => fn (Blueprint $t) => $t->timestamp('published_at')->nullable()->index(),
        ]);
    }

    private function createModules(): void
    {
        $this->create('lms_modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lms_course_id')->index();
            $table->string('title');
            $table->string('slug')->nullable()->index();
            $table->text('summary')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('status')->default('published')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    private function completeLessons(): void
    {
        $this->table('lms_lessons', [
            'lms_course_id' => fn (Blueprint $t) => $t->unsignedBigInteger('lms_course_id')->nullable()->index(),
            'lms_project_id' => fn (Blueprint $t) => $t->unsignedBigInteger('lms_project_id')->nullable()->index(),
            'lms_module_id' => fn (Blueprint $t) => $t->unsignedBigInteger('lms_module_id')->nullable()->index(),
            'title' => fn (Blueprint $t) => $t->string('title')->nullable(),
            'slug' => fn (Blueprint $t) => $t->string('slug')->nullable()->index(),
            'type' => fn (Blueprint $t) => $t->string('type')->default('article')->index(),
            'summary' => fn (Blueprint $t) => $t->text('summary')->nullable(),
            'content' => fn (Blueprint $t) => $t->longText('content')->nullable(),
            'video_url' => fn (Blueprint $t) => $t->string('video_url')->nullable(),
            'duration_minutes' => fn (Blueprint $t) => $t->unsignedInteger('duration_minutes')->default(0),
            'sort_order' => fn (Blueprint $t) => $t->unsignedInteger('sort_order')->default(0)->index(),
            'is_preview' => fn (Blueprint $t) => $t->boolean('is_preview')->default(false)->index(),
            'status' => fn (Blueprint $t) => $t->string('status')->default('draft')->index(),
            'metadata' => fn (Blueprint $t) => $t->json('metadata')->nullable(),
        ]);
    }

    private function completeProjectComponents(): void
    {
        $this->table('lms_project_components', [
            'lms_project_id' => fn (Blueprint $t) => $t->unsignedBigInteger('lms_project_id')->nullable()->index(),
            'product_id' => fn (Blueprint $t) => $t->unsignedBigInteger('product_id')->nullable()->index(),
            'product_variant_id' => fn (Blueprint $t) => $t->unsignedBigInteger('product_variant_id')->nullable()->index(),
            'name' => fn (Blueprint $t) => $t->string('name')->nullable(),
            'quantity' => fn (Blueprint $t) => $t->decimal('quantity', 12, 3)->default(1),
            'unit' => fn (Blueprint $t) => $t->string('unit', 40)->nullable(),
            'is_required' => fn (Blueprint $t) => $t->boolean('is_required')->default(true)->index(),
            'sort_order' => fn (Blueprint $t) => $t->unsignedInteger('sort_order')->default(0)->index(),
            'notes' => fn (Blueprint $t) => $t->text('notes')->nullable(),
        ]);
    }

    private function completeCodeSamples(): void
    {
        $this->table('lms_code_samples', [
            'lms_project_id' => fn (Blueprint $t) => $t->unsignedBigInteger('lms_project_id')->nullable()->index(),
            'lms_lesson_id' => fn (Blueprint $t) => $t->unsignedBigInteger('lms_lesson_id')->nullable()->index(),
            'title' => fn (Blueprint $t) => $t->string('title')->nullable(),
            'language' => fn (Blueprint $t) => $t->string('language', 80)->default('text')->index(),
            'code' => fn (Blueprint $t) => $t->longText('code')->nullable(),
            'explanation' => fn (Blueprint $t) => $t->text('explanation')->nullable(),
            'sort_order' => fn (Blueprint $t) => $t->unsignedInteger('sort_order')->default(0)->index(),
            'metadata' => fn (Blueprint $t) => $t->json('metadata')->nullable(),
        ]);
    }

    private function completeProductLinks(): void
    {
        foreach (['lms_product_links', 'product_lms_links'] as $table) {
            $this->table($table, [
                'lms_course_id' => fn (Blueprint $t) => $t->unsignedBigInteger('lms_course_id')->nullable()->index(),
                'lms_project_id' => fn (Blueprint $t) => $t->unsignedBigInteger('lms_project_id')->nullable()->index(),
                'lms_lesson_id' => fn (Blueprint $t) => $t->unsignedBigInteger('lms_lesson_id')->nullable()->index(),
                'product_id' => fn (Blueprint $t) => $t->unsignedBigInteger('product_id')->nullable()->index(),
                'product_variant_id' => fn (Blueprint $t) => $t->unsignedBigInteger('product_variant_id')->nullable()->index(),
                'link_type' => fn (Blueprint $t) => $t->string('link_type')->default('related')->index(),
                'is_required' => fn (Blueprint $t) => $t->boolean('is_required')->default(false)->index(),
                'sort_order' => fn (Blueprint $t) => $t->unsignedInteger('sort_order')->default(0)->index(),
                'notes' => fn (Blueprint $t) => $t->text('notes')->nullable(),
            ]);
        }
    }

    private function createLearningTables(): void
    {
        $this->create('lms_enrollments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->unsignedBigInteger('lms_course_id')->index();
            $table->string('status')->default('active')->index();
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'email', 'lms_course_id'], 'lms_enrollment_unique');
        });

        $this->create('lms_progress_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lms_enrollment_id')->index();
            $table->unsignedBigInteger('lms_course_id')->index();
            $table->unsignedBigInteger('lms_lesson_id')->nullable()->index();
            $table->string('event_type')->default('lesson_completed')->index();
            $table->decimal('progress_percent', 5, 2)->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->nullable()->index();
            $table->timestamps();
        });

        $this->create('lms_quizzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lms_course_id')->nullable()->index();
            $table->unsignedBigInteger('lms_lesson_id')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('passing_score')->default(70);
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        $this->create('lms_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lms_quiz_id')->index();
            $table->text('question');
            $table->string('type')->default('single_choice')->index();
            $table->json('options')->nullable();
            $table->json('correct_answers')->nullable();
            $table->unsignedInteger('points')->default(1);
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->timestamps();
        });

        $this->create('lms_quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lms_quiz_id')->index();
            $table->unsignedBigInteger('lms_enrollment_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->decimal('score', 6, 2)->default(0);
            $table->boolean('passed')->default(false)->index();
            $table->json('answers')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        $this->create('lms_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lms_course_id')->nullable()->index();
            $table->unsignedBigInteger('lms_lesson_id')->nullable()->index();
            $table->string('title');
            $table->longText('instructions')->nullable();
            $table->timestamp('due_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        $this->create('lms_assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lms_assignment_id')->index();
            $table->unsignedBigInteger('lms_enrollment_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->longText('submission_text')->nullable();
            $table->json('attachments')->nullable();
            $table->string('status')->default('submitted')->index();
            $table->decimal('score', 6, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('submitted_at')->nullable()->index();
            $table->timestamps();
        });

        $this->create('lms_certificates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lms_enrollment_id')->index();
            $table->unsignedBigInteger('lms_course_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('certificate_number')->unique();
            $table->string('status')->default('issued')->index();
            $table->timestamp('issued_at')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    private function create(string $table, callable $callback): void
    {
        if (!Schema::hasTable($table)) {
            Schema::create($table, $callback);
        }
    }

    private function table(string $table, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column => $callback) {
            if (!Schema::hasColumn($table, $column)) {
                Schema::table($table, fn (Blueprint $t) => $callback($t));
            }
        }
    }
};
