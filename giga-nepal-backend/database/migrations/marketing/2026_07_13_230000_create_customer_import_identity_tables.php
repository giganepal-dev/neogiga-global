<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createCustomerSources();
        $this->createCustomerImports();
        $this->createCustomerImportFiles();
        $this->createCustomerAccounts();
        $this->createCustomerContacts();
        $this->createContactEmailAddresses();
        $this->createContactPhoneNumbers();
        $this->createCustomerImportRows();
        $this->createCustomerImportErrors();
        $this->createCustomerInvoiceReferences();
        $this->createCustomerValueHistories();
        $this->createCustomerMergeLogs();
        $this->createCustomerDataProvenance();
        $this->extendExistingCustomerRecords();
    }

    public function down(): void
    {
        $this->dropExistingCustomerRecordColumns();

        foreach ([
            'customer_data_provenance',
            'customer_merge_logs',
            'customer_value_histories',
            'customer_invoice_references',
            'customer_import_errors',
            'customer_import_rows',
            'contact_phone_numbers',
            'contact_email_addresses',
            'customer_contacts',
            'customer_accounts',
            'customer_import_files',
            'customer_imports',
            'customer_sources',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function createCustomerSources(): void
    {
        if (Schema::hasTable('customer_sources')) {
            return;
        }

        Schema::create('customer_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('source_url', 2048)->nullable();
            $table->string('source_page_url', 2048)->nullable();
            $table->string('source_file')->nullable();
            $table->string('license_note')->nullable();
            $table->string('confidence_level', 40)->default('source_provided');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    private function createCustomerImports(): void
    {
        if (Schema::hasTable('customer_imports')) {
            return;
        }

        Schema::create('customer_imports', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('batch_key')->nullable()->index();
            $table->unsignedBigInteger('customer_source_id')->nullable()->index();
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            $table->unsignedBigInteger('uploaded_by')->nullable()->index();
            $table->string('profile_name')->default('Customer Invoice Details')->index();
            $table->string('worksheet')->nullable();
            $table->string('file_name');
            $table->string('original_file_name');
            $table->string('stored_file_path')->nullable();
            $table->string('file_sha256', 64)->index();
            $table->string('status', 40)->default('pending')->index();
            $table->boolean('dry_run')->default(true)->index();
            $table->boolean('only_valid')->default(false);
            $table->boolean('update_existing')->default(false);
            $table->boolean('no_marketing_consent')->default(true);
            $table->json('column_mapping')->nullable();
            $table->json('rules')->nullable();
            $table->unsignedInteger('resume_row')->default(2);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('updated_rows')->default(0);
            $table->unsignedInteger('skipped_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('warning_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->unsignedInteger('unresolved_countries')->default(0);
            $table->unsignedInteger('unresolved_companies')->default(0);
            $table->string('consent_state', 80)->default('unknown');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $this->provenance($table);
            $table->timestamps();
        });
    }

    private function createCustomerImportFiles(): void
    {
        if (Schema::hasTable('customer_import_files')) {
            return;
        }

        Schema::create('customer_import_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_import_id')->index();
            $table->string('original_file_name');
            $table->string('stored_file_path')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('extension', 12)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('sha256', 64)->index();
            $table->json('worksheet_names')->nullable();
            $table->string('selected_worksheet')->nullable();
            $table->boolean('is_primary')->default(true);
            $this->provenance($table);
            $table->timestamps();
        });
    }

    private function createCustomerAccounts(): void
    {
        if (Schema::hasTable('customer_accounts')) {
            return;
        }

        Schema::create('customer_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('legal_name');
            $table->string('display_name')->nullable();
            $table->string('normalized_name')->index();
            $table->string('primary_domain')->nullable()->index();
            $table->unsignedBigInteger('country_id')->nullable()->index();
            $table->unsignedBigInteger('region_id')->nullable()->index();
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            $table->unsignedBigInteger('customer_source_id')->nullable()->index();
            $table->unsignedBigInteger('last_customer_import_id')->nullable()->index();
            $table->string('customer_type', 60)->default('business')->index();
            $table->string('status', 40)->default('active')->index();
            $table->json('metadata')->nullable();
            $this->provenance($table);
            $table->timestamps();
            $table->unique(['normalized_name', 'country_id'], 'customer_accounts_name_country_unique');
        });
    }

    private function createCustomerContacts(): void
    {
        if (Schema::hasTable('customer_contacts')) {
            return;
        }

        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_account_id')->nullable()->index();
            $table->unsignedBigInteger('customer_profile_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('country_id')->nullable()->index();
            $table->unsignedBigInteger('marketplace_id')->nullable()->index();
            $table->unsignedBigInteger('last_customer_import_id')->nullable()->index();
            $table->string('full_name');
            $table->string('original_full_name')->nullable();
            $table->string('normalized_name')->index();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('job_title')->nullable();
            $table->string('status', 40)->default('active')->index();
            $table->boolean('transactional_eligible')->default(true)->index();
            $table->string('marketing_status', 80)->default('unknown')->index();
            $table->json('metadata')->nullable();
            $this->provenance($table);
            $table->timestamps();
        });
    }

    private function createContactEmailAddresses(): void
    {
        if (Schema::hasTable('contact_email_addresses')) {
            return;
        }

        Schema::create('contact_email_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_contact_id')->index();
            $table->unsignedBigInteger('customer_profile_id')->nullable()->index();
            $table->unsignedBigInteger('last_customer_import_id')->nullable()->index();
            $table->string('email');
            $table->string('normalized_email')->unique();
            $table->string('domain')->nullable()->index();
            $table->boolean('is_primary')->default(true);
            $table->boolean('is_valid')->default(true)->index();
            $table->boolean('is_verified')->default(false)->index();
            $table->string('status', 60)->default('active')->index();
            $table->unsignedSmallInteger('soft_bounce_count')->default(0);
            $table->timestamp('last_bounced_at')->nullable();
            $this->provenance($table);
            $table->timestamps();
        });
    }

    private function createContactPhoneNumbers(): void
    {
        if (Schema::hasTable('contact_phone_numbers')) {
            return;
        }

        Schema::create('contact_phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_contact_id')->index();
            $table->unsignedBigInteger('last_customer_import_id')->nullable()->index();
            $table->string('phone');
            $table->string('normalized_phone')->index();
            $table->string('country_calling_code', 12)->nullable();
            $table->boolean('is_primary')->default(true);
            $table->boolean('is_valid')->default(true)->index();
            $table->boolean('is_verified')->default(false)->index();
            $table->string('status', 60)->default('active')->index();
            $this->provenance($table);
            $table->timestamps();
            $table->unique(['customer_contact_id', 'normalized_phone'], 'contact_phone_contact_number_unique');
        });
    }

    private function createCustomerImportRows(): void
    {
        if (Schema::hasTable('customer_import_rows')) {
            return;
        }

        Schema::create('customer_import_rows', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_import_id')->index();
            $table->unsignedBigInteger('customer_import_file_id')->nullable()->index();
            $table->unsignedInteger('row_number');
            $table->string('row_hash', 64)->index();
            $table->string('idempotency_key', 128)->index();
            $table->string('status', 40)->default('pending')->index();
            $table->string('action', 40)->nullable()->index();
            $table->unsignedBigInteger('customer_account_id')->nullable()->index();
            $table->unsignedBigInteger('customer_contact_id')->nullable()->index();
            $table->unsignedBigInteger('customer_profile_id')->nullable()->index();
            $table->unsignedBigInteger('customer_invoice_reference_id')->nullable()->index();
            $table->unsignedBigInteger('resolved_country_id')->nullable()->index();
            $table->string('resolved_country_iso2', 2)->nullable();
            $table->string('resolved_country_iso3', 3)->nullable();
            $table->string('resolved_country_name')->nullable();
            $table->string('country_resolution_confidence', 40)->nullable();
            $table->boolean('country_conflict')->default(false)->index();
            $table->json('original_values');
            $table->json('normalized_values')->nullable();
            $table->json('validation_errors')->nullable();
            $table->json('validation_warnings')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $this->provenance($table);
            $table->timestamps();
            $table->unique(['customer_import_id', 'row_number'], 'customer_import_row_number_unique');
        });
    }

    private function createCustomerImportErrors(): void
    {
        if (Schema::hasTable('customer_import_errors')) {
            return;
        }

        Schema::create('customer_import_errors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_import_id')->index();
            $table->unsignedBigInteger('customer_import_row_id')->nullable()->index();
            $table->unsignedInteger('row_number')->nullable();
            $table->string('field')->nullable()->index();
            $table->string('code', 80)->index();
            $table->string('severity', 20)->default('error')->index();
            $table->text('message');
            $table->json('context')->nullable();
            $table->boolean('is_resolved')->default(false)->index();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $this->provenance($table);
            $table->timestamps();
        });
    }

    private function createCustomerInvoiceReferences(): void
    {
        if (Schema::hasTable('customer_invoice_references')) {
            return;
        }

        Schema::create('customer_invoice_references', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_source_id')->nullable()->index();
            $table->unsignedBigInteger('customer_import_id')->nullable()->index();
            $table->unsignedBigInteger('customer_import_row_id')->nullable()->index();
            $table->unsignedBigInteger('customer_account_id')->index();
            $table->unsignedBigInteger('customer_contact_id')->nullable()->index();
            $table->unsignedBigInteger('customer_profile_id')->nullable()->index();
            $table->string('source_key')->index();
            $table->string('external_invoice_id')->index();
            $table->date('invoice_or_sales_order_date')->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->decimal('amount', 18, 4)->nullable();
            $table->json('metadata')->nullable();
            $this->provenance($table);
            $table->timestamps();
            $table->unique(['source_key', 'external_invoice_id'], 'customer_invoice_source_external_unique');
        });
    }

    private function createCustomerValueHistories(): void
    {
        if (Schema::hasTable('customer_value_histories')) {
            return;
        }

        Schema::create('customer_value_histories', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 80)->index();
            $table->unsignedBigInteger('entity_id')->index();
            $table->string('field')->index();
            $table->longText('old_value')->nullable();
            $table->longText('new_value')->nullable();
            $table->unsignedBigInteger('customer_import_row_id')->nullable()->index();
            $table->string('change_reason')->nullable();
            $table->string('confidence_level', 40)->default('exact');
            $table->timestamps();
        });
    }

    private function createCustomerMergeLogs(): void
    {
        if (Schema::hasTable('customer_merge_logs')) {
            return;
        }

        Schema::create('customer_merge_logs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 80)->index();
            $table->unsignedBigInteger('canonical_record_id')->index();
            $table->unsignedBigInteger('merged_record_id')->index();
            $table->unsignedBigInteger('customer_import_id')->nullable()->index();
            $table->unsignedBigInteger('merged_by')->nullable();
            $table->string('status', 40)->default('applied')->index();
            $table->string('confidence_level', 40);
            $table->text('reason')->nullable();
            $table->json('preserved_values')->nullable();
            $table->json('reassigned_relationships')->nullable();
            $table->string('rollback_token_hash', 64)->nullable()->unique();
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamps();
        });
    }

    private function createCustomerDataProvenance(): void
    {
        if (Schema::hasTable('customer_data_provenance')) {
            return;
        }

        Schema::create('customer_data_provenance', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 80)->index();
            $table->unsignedBigInteger('entity_id')->index();
            $table->string('field')->nullable()->index();
            $table->unsignedBigInteger('customer_import_id')->nullable()->index();
            $table->unsignedBigInteger('customer_import_row_id')->nullable()->index();
            $this->provenance($table);
            $table->timestamps();
        });
    }

    private function extendExistingCustomerRecords(): void
    {
        $this->addColumns('customer_profiles', [
            'customer_account_id' => fn (Blueprint $table) => $table->unsignedBigInteger('customer_account_id')->nullable()->index(),
            'customer_contact_id' => fn (Blueprint $table) => $table->unsignedBigInteger('customer_contact_id')->nullable()->index(),
            'last_customer_import_id' => fn (Blueprint $table) => $table->unsignedBigInteger('last_customer_import_id')->nullable()->index(),
            'transactional_eligible' => fn (Blueprint $table) => $table->boolean('transactional_eligible')->default(true)->index(),
            'marketing_status' => fn (Blueprint $table) => $table->string('marketing_status', 80)->default('unknown')->index(),
        ]);

        $this->addColumns('customer_addresses', [
            'customer_account_id' => fn (Blueprint $table) => $table->unsignedBigInteger('customer_account_id')->nullable()->index(),
            'customer_contact_id' => fn (Blueprint $table) => $table->unsignedBigInteger('customer_contact_id')->nullable()->index(),
            'customer_import_id' => fn (Blueprint $table) => $table->unsignedBigInteger('customer_import_id')->nullable()->index(),
            'customer_import_row_id' => fn (Blueprint $table) => $table->unsignedBigInteger('customer_import_row_id')->nullable()->index(),
            'original_city' => fn (Blueprint $table) => $table->string('original_city')->nullable(),
            'original_country' => fn (Blueprint $table) => $table->string('original_country')->nullable(),
            'original_region' => fn (Blueprint $table) => $table->string('original_region')->nullable(),
            'provenance' => fn (Blueprint $table) => $table->json('provenance')->nullable(),
        ]);
    }

    private function dropExistingCustomerRecordColumns(): void
    {
        $this->dropColumns('customer_addresses', [
            'customer_account_id', 'customer_contact_id', 'customer_import_id', 'customer_import_row_id',
            'original_city', 'original_country', 'original_region', 'provenance',
        ]);
        $this->dropColumns('customer_profiles', [
            'customer_account_id', 'customer_contact_id', 'last_customer_import_id',
            'transactional_eligible', 'marketing_status',
        ]);
    }

    private function provenance(Blueprint $table): void
    {
        $table->string('source_name')->nullable();
        $table->string('source_url', 2048)->nullable();
        $table->string('source_file')->nullable();
        $table->string('source_page_url', 2048)->nullable();
        $table->timestamp('downloaded_at')->nullable();
        $table->timestamp('imported_at')->nullable();
        $table->unsignedSmallInteger('data_year')->nullable();
        $table->string('license_note')->nullable();
        $table->string('confidence_level', 40)->default('source_provided');
        $table->longText('original_raw_value')->nullable();
        $table->longText('normalized_value')->nullable();
    }

    /** @param array<string, callable(Blueprint): void> $columns */
    private function addColumns(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        foreach ($columns as $column => $callback) {
            if (! Schema::hasColumn($tableName, $column)) {
                Schema::table($tableName, $callback);
            }
        }
    }

    /** @param list<string> $columns */
    private function dropColumns(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        $existing = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn($tableName, $column)));
        if ($existing !== []) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn($existing));
        }
    }
};
