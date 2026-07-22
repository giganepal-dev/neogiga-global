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
        Schema::create('email_automation_workflows', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->string('trigger_type', 50)->index(); // subscriber_created, order_placed, rfq_submitted, bom_uploaded, seller_applied, etc.
            $table->json('trigger_conditions')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('priority')->default(100);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('email_automation_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('email_automation_workflows')->cascadeOnDelete()->index();
            $table->integer('step_order')->index();
            $table->string('action_type', 50)->index(); // send_email, wait, condition, update_subscriber, add_tag, remove_tag
            $table->json('action_config')->nullable();
            $table->integer('delay_minutes')->default(0);
            $table->json('conditions')->nullable();
            $table->foreignId('template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('email_campaigns')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['workflow_id', 'step_order']);
        });

        Schema::create('email_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('entity_type', 50)->index(); // subscriber, group, campaign, template, import, etc.
            $table->unsignedBigInteger('entity_id')->index();
            $table->string('action', 50)->index(); // created, updated, deleted, imported, exported, assigned, unassigned, subscribed, unsubscribed, suppressed
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id', 'created_at']);
        });

        Schema::create('email_regional_assignment_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->integer('priority')->unique()->index();
            $table->string('rule_type', 50)->index(); // subdomain, user_region, billing_country, shipping_country, explicit_country, import_country, phone_country, ip_geolocation, admin_default, fallback
            $table->json('rule_config')->nullable();
            $table->foreignId('group_id')->constrained('email_groups')->cascadeOnDelete();
            $table->string('confidence_level', 20)->default('medium'); // low, medium, high
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('can_override')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['priority', 'is_active']);
        });

        Schema::create('email_regional_assignments_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_id')->constrained('email_subscribers')->cascadeOnDelete()->index();
            $table->foreignId('from_group_id')->nullable()->constrained('email_groups')->nullOnDelete();
            $table->foreignId('to_group_id')->constrained('email_groups')->cascadeOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained('email_regional_assignment_rules')->nullOnDelete();
            $table->string('assignment_source', 50)->index();
            $table->string('confidence_level', 20)->default('medium');
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamps();

            $table->index(['subscriber_id', 'assigned_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_regional_assignments_log');
        Schema::dropIfExists('email_regional_assignment_rules');
        Schema::dropIfExists('email_audit_logs');
        Schema::dropIfExists('email_automation_steps');
        Schema::dropIfExists('email_automation_workflows');
    }
};
