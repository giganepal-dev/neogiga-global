<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('distributor_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distributor_application_id')->constrained()->onDelete('cascade');
            $table->string('activity_type'); // lead_added, customer_visited, order_placed, meeting_scheduled, etc.
            $table->text('description')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('lead_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->date('activity_date');
            $table->time('activity_time')->nullable();
            $table->string('location')->nullable();
            $table->json('metadata')->nullable(); // Additional activity data
            $table->decimal('potential_value', 15, 2)->nullable();
            $table->string('status')->default('completed'); // planned, completed, cancelled, follow_up_needed
            $table->timestamp('follow_up_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['distributor_application_id', 'activity_type']);
            $table->index(['activity_date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distributor_activities');
    }
};
