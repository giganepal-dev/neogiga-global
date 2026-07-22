<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 6: Analytics, AI Insights & Global Dashboard
     */
    public function up(): void
    {
        // Analytics Dashboards Configuration
        Schema::create('analytics_dashboards', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('dashboard_name');
            $table->enum('dashboard_type', ['executive', 'sales', 'inventory', 'financial', 'operations', 'custom'])->default('custom');
            $table->json('layout_config'); // Widget positions, sizes
            $table->json('widget_config'); // Widget definitions and filters
            $table->boolean('is_default')->default(false);
            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['marketplace_id', 'dashboard_type']);
            $table->index(['user_id', 'is_default']);
        });

        // KPI Definitions
        Schema::create('kpi_definitions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->string('kpi_code')->unique()->index();
            $table->string('kpi_name');
            $table->text('description')->nullable();
            $table->enum('category', ['sales', 'inventory', 'financial', 'customer', 'operations', 'hr'])->default('sales');
            $table->string('formula_definition')->nullable(); // SQL or calculation logic
            $table->json('data_sources'); // Tables/queries to use
            $table->string('unit_type')->default('number'); // number, percentage, currency, count
            $table->string('currency', 3)->nullable();
            $table->integer('decimal_places')->default(2);
            $table->string('aggregation_method')->default('sum'); // sum, avg, count, min, max
            $table->integer('refresh_interval_minutes')->default(60);
            $table->json('thresholds')->nullable(); // Warning/critical thresholds
            $table->boolean('show_trend')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['category', 'is_active']);
            $table->index(['marketplace_id', 'category']);
        });

        // KPI Values (Cached/Pre-calculated)
        Schema::create('kpi_values', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('kpi_definition_id')->constrained('kpi_definitions')->cascadeOnDelete();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->date('value_date');
            $table->string('period_type')->index(); // daily, weekly, monthly, quarterly, yearly
            $table->string('period_value'); // e.g., "2024-01" for monthly
            $table->decimal('value', 15, 4)->default(0);
            $table->decimal('previous_value', 15, 4)->nullable();
            $table->decimal('target_value', 15, 4)->nullable();
            $table->decimal('variance_percent', 8, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->json('breakdown')->nullable(); // Dimensional breakdown
            $table->timestamp('calculated_at');
            $table->timestamps();
            
            $table->index(['kpi_definition_id', 'value_date']);
            $table->index(['period_type', 'period_value']);
            $table->index(['marketplace_id', 'value_date']);
        });

        // Report Executions
        Schema::create('report_executions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->nullOnDelete();
            $table->string('report_name');
            $table->enum('report_type', ['sales', 'inventory', 'financial', 'customer', 'supplier', 'product', 'custom'])->default('custom');
            $table->json('filters'); // Applied filters
            $table->json('columns'); // Selected columns
            $table->json('grouping'); // Group by fields
            $table->json('sorting'); // Sort order
            $table->string('output_format')->default('json'); // json, csv, xlsx, pdf
            $table->integer('row_count')->default(0);
            $table->integer('execution_time_ms')->default(0);
            $table->string('status')->default('completed'); // pending, running, completed, failed
            $table->text('error_message')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['report_type', 'created_at']);
            $table->index(['status', 'created_at']);
        });

        // AI Query Logs
        Schema::create('ai_query_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->nullOnDelete();
            $table->text('query_text');
            $table->string('query_intent')->nullable(); // sales_summary, inventory_alert, etc.
            $table->json('extracted_filters')->nullable();
            $table->json('generated_sql')->nullable();
            $table->json('response_data')->nullable();
            $table->integer('response_time_ms')->default(0);
            $table->integer('token_usage')->default(0);
            $table->string('model_used')->nullable();
            $table->boolean('was_successful')->default(true);
            $table->text('error_message')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['query_intent', 'was_successful']);
            $table->index(['created_at', 'was_successful']);
        });

        // Anomaly Detection
        Schema::create('anomaly_detections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->string('anomaly_type')->index(); // sales_spike, inventory_shortage, fraud_suspect, etc.
            $table->string('severity')->default('medium'); // low, medium, high, critical
            $table->string('affected_entity_type'); // pos_sale, product, customer, etc.
            $table->unsignedBigInteger('affected_entity_id');
            $table->text('description');
            $table->json('detection_details'); // What was detected
            $table->json('baseline_data'); // Expected values
            $table->json('actual_data'); // Actual values
            $table->decimal('deviation_percent', 8, 2)->nullable();
            $table->string('recommended_action')->nullable();
            $table->enum('status', ['new', 'investigating', 'confirmed', 'false_positive', 'resolved'])->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('investigation_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['anomaly_type', 'severity']);
            $table->index(['status', 'created_at']);
            $table->index(['affected_entity_type', 'affected_entity_id']);
        });

        // Forecasting Models
        Schema::create('forecasting_models', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->string('model_name');
            $table->enum('model_type', ['sales_forecast', 'inventory_demand', 'cash_flow', 'seasonal'])->default('sales_forecast');
            $table->string('algorithm')->default('moving_average'); // moving_average, exponential_smoothing, arima, prophet
            $table->json('model_parameters'); // Algorithm-specific parameters
            $table->string('target_entity_type'); // product, category, branch, etc.
            $table->unsignedBigInteger('target_entity_id')->nullable();
            $table->integer('training_data_days')->default(365);
            $table->integer('forecast_horizon_days')->default(90);
            $table->decimal('accuracy_score', 5, 4)->nullable(); // MAPE or similar
            $table->date('last_trained_at')->nullable();
            $table->json('performance_metrics')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['model_type', 'is_active']);
            $table->index(['target_entity_type', 'target_entity_id']);
        });

        // Forecast Results
        Schema::create('forecast_results', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('forecasting_model_id')->constrained('forecasting_models')->cascadeOnDelete();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->string('target_entity_type');
            $table->unsignedBigInteger('target_entity_id');
            $table->date('forecast_date');
            $table->decimal('predicted_value', 15, 4)->default(0);
            $table->decimal('lower_bound', 15, 4)->nullable(); // Confidence interval
            $table->decimal('upper_bound', 15, 4)->nullable();
            $table->decimal('confidence_level', 5, 4)->default(0.95);
            $table->json('contributing_factors')->nullable();
            $table->timestamps();
            
            $table->index(['forecasting_model_id', 'forecast_date']);
            $table->index(['target_entity_type', 'target_entity_id', 'forecast_date']);
        });

        // Alert Rules
        Schema::create('alert_rules', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->string('rule_name');
            $table->enum('alert_type', ['inventory', 'sales', 'financial', 'system', 'custom'])->default('custom');
            $table->string('trigger_condition'); // SQL or expression
            $table->json('condition_parameters');
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->json('notification_channels'); // email, sms, slack, webhook
            $table->json('recipients'); // User IDs, emails, channels
            $table->string('message_template')->nullable();
            $table->integer('cooldown_minutes')->default(60);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->integer('trigger_count')->default(0);
            $table->foreignId('created_by')->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['alert_type', 'is_active']);
            $table->index(['marketplace_id', 'is_active']);
        });

        // Alert History
        Schema::create('alert_history', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('alert_rule_id')->constrained('alert_rules')->cascadeOnDelete();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->text('alert_message');
            $table->json('trigger_data'); // Data that triggered the alert
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->enum('status', ['sent', 'failed', 'acknowledged', 'resolved'])->default('sent');
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['alert_rule_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index(['severity', 'created_at']);
        });

        // System Health Metrics
        Schema::create('system_health_metrics', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->string('metric_name')->index();
            $table->string('metric_category')->index(); // performance, availability, capacity, security
            $table->string('component')->nullable(); // database, api, queue, cache, etc.
            $table->decimal('value', 15, 4)->default(0);
            $table->string('unit')->nullable(); // ms, percent, count, MB, etc.
            $table->string('status')->default('healthy'); // healthy, warning, critical, unknown
            $table->json('metadata')->nullable();
            $table->timestamp('measured_at');
            $table->timestamps();
            
            $table->index(['metric_category', 'measured_at']);
            $table->index(['component', 'status']);
        });

        // Data Export Queue
        Schema::create('data_export_jobs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('marketplace_id')->nullable()->constrained('marketplaces')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('export_name');
            $table->enum('export_type', ['products', 'customers', 'orders', 'inventory', 'financial', 'analytics'])->default('products');
            $table->json('filters');
            $table->json('columns');
            $table->string('output_format')->default('csv'); // csv, xlsx, json
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->integer('total_records')->default(0);
            $table->integer('processed_records')->default(0);
            $table->string('file_path')->nullable();
            $table->string('download_url')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
            $table->index(['export_type', 'status']);
            $table->index(['status', 'created_at']);
        });

        // User Preferences for Analytics
        Schema::create('user_analytics_preferences', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique()->index();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('default_dashboard_id')->nullable()->constrained('analytics_dashboards')->nullOnDelete();
            $table->string('default_currency', 3)->default('USD');
            $table->string('default_date_range')->default('last_30_days');
            $table->json('favorite_kpis')->nullable();
            $table->json('saved_filters')->nullable();
            $table->boolean('enable_email_reports')->default(false);
            $table->string('email_report_frequency')->default('weekly'); // daily, weekly, monthly
            $table->json('email_report_schedule')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'default_currency']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_analytics_preferences');
        Schema::dropIfExists('data_export_jobs');
        Schema::dropIfExists('system_health_metrics');
        Schema::dropIfExists('alert_history');
        Schema::dropIfExists('alert_rules');
        Schema::dropIfExists('forecast_results');
        Schema::dropIfExists('forecasting_models');
        Schema::dropIfExists('anomaly_detections');
        Schema::dropIfExists('ai_query_logs');
        Schema::dropIfExists('report_executions');
        Schema::dropIfExists('kpi_values');
        Schema::dropIfExists('kpi_definitions');
        Schema::dropIfExists('analytics_dashboards');
    }
};
