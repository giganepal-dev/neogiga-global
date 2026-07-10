<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('support_tickets')) {
            return;
        }

        Schema::table('support_tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('support_tickets', 'support_channel')) {
                $table->string('support_channel')->default('admin')->index();
            }
            if (! Schema::hasColumn('support_tickets', 'first_response_due_at')) {
                $table->timestamp('first_response_due_at')->nullable()->index();
            }
            if (! Schema::hasColumn('support_tickets', 'sla_due_at')) {
                $table->timestamp('sla_due_at')->nullable()->index();
            }
            if (! Schema::hasColumn('support_tickets', 'first_responded_at')) {
                $table->timestamp('first_responded_at')->nullable();
            }
            if (! Schema::hasColumn('support_tickets', 'escalated_at')) {
                $table->timestamp('escalated_at')->nullable()->index();
            }
            if (! Schema::hasColumn('support_tickets', 'escalation_level')) {
                $table->unsignedTinyInteger('escalation_level')->default(0)->index();
            }
            if (! Schema::hasColumn('support_tickets', 'related_product_id')) {
                $table->unsignedBigInteger('related_product_id')->nullable()->index();
            }
            if (! Schema::hasColumn('support_tickets', 'related_order_id')) {
                $table->unsignedBigInteger('related_order_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        // Additive live upgrade: keep SLA and relationship metadata.
    }
};
