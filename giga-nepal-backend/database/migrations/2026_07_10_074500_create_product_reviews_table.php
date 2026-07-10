<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_reviews')) {
            return;
        }

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reviewer_name', 120)->nullable();
            $table->string('reviewer_email', 190)->nullable();
            $table->unsignedTinyInteger('rating');
            $table->string('title', 180)->nullable();
            $table->text('body')->nullable();
            $table->string('use_case', 160)->nullable();
            $table->boolean('is_verified_buyer')->default(false);
            $table->string('status', 32)->default('pending')->index();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->text('moderation_note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['rating', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
