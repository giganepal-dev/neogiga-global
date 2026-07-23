<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // News/Blog Categories
        Schema::create('news_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('slug', 200)->unique();
            $table->text('description')->nullable();
            $table->string('type', 50)->default('blog');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // News/Blog Posts
        Schema::create('news_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_category_id')->nullable()->constrained('news_categories')->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 500);
            $table->string('slug', 500)->unique();
            $table->string('subtitle', 500)->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('post_type', 50)->default('blog')->index();
            $table->string('status', 50)->default('draft')->index();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('scheduled_at')->nullable();
            $table->string('hero_image', 500)->nullable();
            $table->string('og_image', 500)->nullable();
            $table->json('tags')->nullable();
            $table->json('downloads')->nullable();
            $table->json('sources')->nullable();
            $table->json('related_product_ids')->nullable();
            $table->json('related_category_ids')->nullable();
            $table->json('regional_targeting')->nullable();
            $table->json('language_targeting')->nullable();
            $table->string('meta_title', 200)->nullable();
            $table->text('meta_description')->nullable();
            $table->string('canonical_url', 500)->nullable();
            $table->integer('view_count')->default(0);
            $table->integer('share_count')->default(0);
            $table->boolean('comments_enabled')->default(true);
            $table->boolean('add_to_modal')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['post_type', 'status']);
        });

        // News Post Tags
        Schema::create('news_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200)->unique();
            $table->string('slug', 200)->unique();
            $table->timestamps();
        });

        // News Post <-> Tag pivot
        Schema::create('news_post_tag', function (Blueprint $table) {
            $table->foreignId('news_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('news_tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['news_post_id', 'news_tag_id']);
        });

        // News Post Relations (related posts)
        Schema::create('news_post_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('related_post_id')->constrained('news_posts')->cascadeOnDelete();
            $table->string('relation_type', 50)->default('related');
            $table->timestamps();

            $table->unique(['news_post_id', 'related_post_id', 'relation_type']);
        });

        // News Modal Campaigns
        Schema::create('news_modal_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_post_id')->nullable()->constrained('news_posts')->nullOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->string('cta_text', 100)->nullable();
            $table->string('target_url', 500)->nullable();
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->json('regional_targeting')->nullable();
            $table->json('audience_targeting')->nullable();
            $table->string('frequency', 50)->default('once_per_session');
            $table->boolean('is_active')->default(false);
            $table->integer('view_count')->default(0);
            $table->integer('click_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        // News Modal Events (view/click tracking)
        Schema::create('news_modal_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_modal_campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 100)->nullable()->index();
            $table->string('event_type', 50)->default('view');
            $table->string('country_code', 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['news_modal_campaign_id', 'event_type']);
        });

        // Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 100);
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->string('channel', 50)->default('in_app');
            $table->string('priority', 20)->default('normal');
            $table->string('category', 50)->nullable();
            $table->string('action_url', 500)->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('news_modal_events');
        Schema::dropIfExists('news_modal_campaigns');
        Schema::dropIfExists('news_post_relations');
        Schema::dropIfExists('news_post_tag');
        Schema::dropIfExists('news_tags');
        Schema::dropIfExists('news_posts');
        Schema::dropIfExists('news_categories');
    }
};
