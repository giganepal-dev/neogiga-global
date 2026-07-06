<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_agents', function (Blueprint $table) {
            $this->baseColumns($table);
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('surface')->index();
            $table->string('status')->default('draft')->index();
            $table->json('allowed_tools')->nullable();
            $table->json('guardrail_profile')->nullable();
            $table->text('system_purpose')->nullable();
            $this->scopeColumns($table);
            $this->auditColumns($table);
        });

        Schema::create('ai_conversations', function (Blueprint $table) {
            $this->baseColumns($table);
            $table->foreignId('ai_agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
            $table->foreignId('ai_session_id')->nullable()->constrained('ai_sessions')->nullOnDelete();
            $table->string('channel')->default('web')->index();
            $table->string('status')->default('open')->index();
            $table->string('intent')->nullable()->index();
            $table->json('summary')->nullable();
            $this->scopeColumns($table);
            $this->auditColumns($table);
        });

        Schema::create('ai_tool_calls', function (Blueprint $table) {
            $this->baseColumns($table);
            $table->foreignId('ai_conversation_id')->nullable()->constrained('ai_conversations')->nullOnDelete();
            $table->string('tool_name')->index();
            $table->string('status')->default('pending')->index();
            $table->json('arguments')->nullable();
            $table->json('result_summary')->nullable();
            $table->json('permission_context')->nullable();
            $table->boolean('requires_confirmation')->default(false);
            $table->timestamp('confirmed_at')->nullable();
            $table->string('failure_code')->nullable();
            $table->text('failure_message')->nullable();
            $this->scopeColumns($table);
            $this->provenanceColumns($table);
            $this->auditColumns($table);
        });

        Schema::create('ai_knowledge_sources', function (Blueprint $table) {
            $this->baseColumns($table);
            $table->string('knowledge_type')->index();
            $table->string('name');
            $table->string('status')->default('draft')->index();
            $table->string('language', 16)->nullable()->index();
            $table->string('source_uri')->nullable();
            $table->string('checksum')->nullable();
            $table->unsignedTinyInteger('quality_score')->nullable();
            $table->timestamp('last_ingested_at')->nullable();
            $this->scopeColumns($table);
            $this->provenanceColumns($table);
            $this->auditColumns($table);
        });

        Schema::create('ai_documents', function (Blueprint $table) {
            $this->baseColumns($table);
            $table->foreignUuid('ai_knowledge_source_uuid')->nullable()->references('uuid')->on('ai_knowledge_sources')->nullOnDelete();
            $table->string('document_type')->index();
            $table->string('title');
            $table->string('status')->default('uploaded')->index();
            $table->string('language', 16)->nullable()->index();
            $table->string('source_uri')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('checksum')->nullable();
            $table->json('parser_metadata')->nullable();
            $table->timestamp('parsed_at')->nullable();
            $this->scopeColumns($table);
            $this->provenanceColumns($table);
            $this->auditColumns($table);
        });

        Schema::create('ai_document_chunks', function (Blueprint $table) {
            $this->baseColumns($table);
            $table->foreignUuid('ai_document_uuid')->references('uuid')->on('ai_documents')->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->string('heading_path')->nullable();
            $table->unsignedInteger('page_start')->nullable();
            $table->unsignedInteger('page_end')->nullable();
            $table->longText('content');
            $table->text('citation_text')->nullable();
            $table->json('metadata')->nullable();
            $this->scopeColumns($table);
            $this->provenanceColumns($table);
            $this->auditColumns($table);
            $table->unique(['ai_document_uuid', 'chunk_index']);
        });

        Schema::create('ai_embeddings', function (Blueprint $table) {
            $this->baseColumns($table);
            $table->foreignUuid('ai_document_chunk_uuid')->nullable()->references('uuid')->on('ai_document_chunks')->cascadeOnDelete();
            $table->string('provider')->index();
            $table->string('model')->index();
            $table->unsignedInteger('dimensions')->nullable();
            $table->string('vector_store')->nullable()->index();
            $table->string('vector_id')->nullable()->index();
            $table->json('embedding_metadata')->nullable();
            $this->scopeColumns($table);
            $this->provenanceColumns($table);
            $this->auditColumns($table);
        });

        Schema::create('ai_project_templates', function (Blueprint $table) {
            $this->baseColumns($table);
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->index();
            $table->string('difficulty_level')->index();
            $table->string('estimated_build_time')->nullable();
            $table->json('required_components');
            $table->json('optional_components')->nullable();
            $table->json('required_tools')->nullable();
            $table->json('battery_power_requirements')->nullable();
            $table->text('wiring_overview')->nullable();
            $table->json('lms_lesson_links')->nullable();
            $table->json('sample_code_placeholders')->nullable();
            $table->json('product_matching_placeholders')->nullable();
            $table->json('safety_notes')->nullable();
            $table->string('status')->default('draft')->index();
            $this->scopeColumns($table);
            $this->provenanceColumns($table);
            $this->auditColumns($table);
        });

        foreach ($this->jsonRecordTables() as $tableName => $columns) {
            Schema::create($tableName, function (Blueprint $table) use ($columns) {
                $this->baseColumns($table);
                foreach ($columns as $name => $type) {
                    match ($type) {
                        'text' => $table->text($name)->nullable(),
                        'longText' => $table->longText($name)->nullable(),
                        'integer' => $table->integer($name)->nullable(),
                        'decimal' => $table->decimal($name, 10, 4)->nullable(),
                        'boolean_true' => $table->boolean($name)->default(true),
                        'boolean_false' => $table->boolean($name)->default(false),
                        'timestamp' => $table->timestamp($name)->nullable(),
                        'json_required' => $table->json($name),
                        default => $table->string($name)->nullable()->index(),
                    };
                }
                $this->scopeColumns($table);
                $this->provenanceColumns($table);
                $this->auditColumns($table);
            });
        }
    }

    public function down(): void
    {
        foreach (array_merge(array_reverse(array_keys($this->jsonRecordTables())), [
            'ai_project_templates',
            'ai_embeddings',
            'ai_document_chunks',
            'ai_documents',
            'ai_knowledge_sources',
            'ai_tool_calls',
            'ai_conversations',
            'ai_agents',
        ]) as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function jsonRecordTables(): array
    {
        return [
            'ai_evaluations' => [
                'evaluation_type' => 'string',
                'route_name' => 'string',
                'provider' => 'string',
                'model' => 'string',
                'score' => 'decimal',
                'input' => 'json_required',
                'expected' => 'json_required',
                'actual' => 'json_required',
                'metrics' => 'json_required',
            ],
            'ai_feedback' => [
                'feedback_type' => 'string',
                'rating' => 'integer',
                'comment' => 'text',
                'status' => 'string',
                'review_metadata' => 'json_required',
            ],
            'ai_generated_boms' => [
                'status' => 'string',
                'goal_description' => 'text',
                'lines' => 'json_required',
                'unresolved_lines' => 'json_required',
                'safety_notes' => 'json_required',
            ],
            'ai_cart_drafts' => [
                'status' => 'string',
                'items' => 'json_required',
                'requires_confirmation' => 'boolean_true',
                'confirmed_at' => 'timestamp',
            ],
            'ai_quote_drafts' => [
                'status' => 'string',
                'lines' => 'json_required',
                'commercial_facts' => 'json_required',
                'requires_human_review' => 'boolean_true',
            ],
            'ai_order_actions' => [
                'action_type' => 'string',
                'status' => 'string',
                'payload' => 'json_required',
                'requires_confirmation' => 'boolean_true',
                'requires_admin_review' => 'boolean_true',
                'confirmed_at' => 'timestamp',
                'reviewed_at' => 'timestamp',
            ],
            'ai_handoff_tickets' => [
                'reference' => 'string',
                'queue' => 'string',
                'status' => 'string',
                'reason' => 'text',
                'context' => 'json_required',
                'priority' => 'integer',
            ],
            'ai_prompt_versions' => [
                'name' => 'string',
                'version' => 'string',
                'status' => 'string',
                'surface' => 'string',
                'system_prompt' => 'longText',
                'tool_policy' => 'json_required',
                'guardrail_policy' => 'json_required',
            ],
            'ai_model_providers' => [
                'provider' => 'string',
                'display_name' => 'string',
                'status' => 'string',
                'capabilities' => 'json_required',
                'cost_policy' => 'json_required',
                'privacy_policy' => 'json_required',
            ],
            'ai_model_routes' => [
                'route_name' => 'string',
                'task_type' => 'string',
                'provider' => 'string',
                'model' => 'string',
                'fallback_provider' => 'string',
                'fallback_model' => 'string',
                'max_tokens' => 'integer',
                'max_cost_usd' => 'decimal',
                'status' => 'string',
            ],
            'ai_guardrail_rules' => [
                'rule_key' => 'string',
                'risk_category' => 'string',
                'severity' => 'string',
                'action' => 'string',
                'description' => 'text',
                'match_policy' => 'json_required',
                'response_policy' => 'json_required',
                'status' => 'string',
            ],
        ];
    }

    private function baseColumns(Blueprint $table): void
    {
        $table->id();
        $table->uuid('uuid')->unique();
        $table->timestamps();
        $table->softDeletes();
    }

    private function scopeColumns(Blueprint $table): void
    {
        $table->foreignId('user_id')->nullable()->index();
        $table->foreignId('organization_id')->nullable()->index();
        $table->foreignId('marketplace_id')->nullable()->index();
        $table->foreignId('country_id')->nullable()->index();
        $table->string('permission_scope')->default('public')->index();
    }

    private function provenanceColumns(Blueprint $table): void
    {
        $table->string('source_type')->nullable()->index();
        $table->string('source_id')->nullable()->index();
        $table->json('source_provenance')->nullable();
    }

    private function auditColumns(Blueprint $table): void
    {
        $table->foreignId('created_by')->nullable()->index();
        $table->foreignId('updated_by')->nullable()->index();
        $table->foreignId('deleted_by')->nullable()->index();
        $table->json('audit_metadata')->nullable();
    }
};
