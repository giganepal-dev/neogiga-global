<?php

namespace App\Services\CustomerImport;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class CustomerImportService
{
    public function __construct(
        private SpreadsheetCustomerReader $reader,
        private CustomerImportNormalizer $normalizer,
        private CustomerCountryResolver $countries,
    ) {}

    public function preview(string $path, string $profile = 'Customer Invoice Details', ?string $sheet = null): array
    {
        return $this->reader->preview($path, $profile, $sheet);
    }

    public function run(?string $path, array $options = []): array
    {
        $resume = $options['resume'] ?? null;
        $existingImport = $resume ? $this->findImport((string) $resume) : null;
        if ($resume && ! $existingImport) {
            throw new InvalidArgumentException("Customer import [{$resume}] was not found.");
        }

        $path = $path ?: ($existingImport->stored_file_path ?? null);
        if (! $path) {
            throw new InvalidArgumentException('A spreadsheet path is required.');
        }
        $profileName = (string) ($options['profile'] ?? $existingImport->profile_name ?? 'Customer Invoice Details');
        $sheetName = $options['sheet'] ?? $existingImport->worksheet ?? null;
        $data = $this->reader->read($path, $profileName, $sheetName);
        if ($data['missing_required'] !== []) {
            throw new InvalidArgumentException('Missing required mapped columns: '.implode(', ', $data['missing_required']));
        }

        $dryRun = (bool) ($options['dry_run'] ?? false);
        if ($dryRun) {
            return $this->analyze($data, $options + ['profile' => $profileName, 'path' => $path]);
        }

        $context = $existingImport
            ? $this->resumeContext($existingImport, $path, $data, $options)
            : $this->createImportContext($path, $data, $profileName, $options);

        return $this->process($data, $context, $options);
    }

    private function analyze(array $data, array $options): array
    {
        $report = $this->emptyReport();
        $report['dry_run'] = true;
        $report['profile'] = $options['profile'];
        $report['worksheet'] = $data['worksheet'];
        $report['total_rows'] = count($data['rows']);
        foreach ($data['rows'] as $row) {
            $normalized = $this->normalizer->normalize($row['mapped']);
            $country = $this->countries->resolve(
                $normalized['account_country_name'],
                $normalized['source_country_text'],
                $options['country'] ?? null,
            );
            $validation = $this->normalizer->validate($row['mapped'], $normalized, $country);
            $report['warning_rows'] += $validation['warnings'] !== [] ? 1 : 0;
            $report['error_rows'] += $validation['errors'] !== [] ? 1 : 0;
            $report['valid_rows'] += $validation['errors'] === [] ? 1 : 0;
            $report['unresolved_countries'] += ! $country['resolved'] ? 1 : 0;
            $report['rows'][] = [
                'row_number' => $row['row_number'],
                'normalized' => $normalized,
                'country' => $country,
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings'],
                'action' => $validation['errors'] === [] ? 'would_import_or_link' : 'would_skip',
            ];
        }

        return $report;
    }

    private function process(array $data, array $context, array $options): array
    {
        $report = $this->emptyReport();
        $report['dry_run'] = false;
        $report['import_id'] = $context['import_id'];
        $report['import_uuid'] = $context['uuid'];
        $report['profile'] = $context['profile'];
        $report['worksheet'] = $data['worksheet'];
        $report['total_rows'] = count($data['rows']);
        $resumeRow = (int) $context['resume_row'];

        $report = $this->reconcileCounters($report, $context['import_id']);
        DB::table('customer_imports')->where('id', $context['import_id'])->update([
            'status' => 'processing',
            'started_at' => DB::raw('COALESCE(started_at, CURRENT_TIMESTAMP)'),
            'updated_at' => now(),
        ]);

        foreach ($data['rows'] as $row) {
            if ($row['row_number'] < $resumeRow) {
                continue;
            }
            try {
                $outcome = DB::transaction(fn () => $this->processRow($row, $context, $options));
            } catch (Throwable $exception) {
                DB::table('customer_imports')->where('id', $context['import_id'])->update([
                    'status' => 'failed',
                    'resume_row' => $row['row_number'],
                    'updated_at' => now(),
                ]);
                $this->recordSystemError($context, $row, $exception);
                throw $exception;
            }

            foreach (['valid_rows', 'imported_rows', 'updated_rows', 'skipped_rows', 'duplicate_rows', 'warning_rows', 'error_rows', 'unresolved_countries', 'unresolved_companies'] as $counter) {
                $report[$counter] += (int) ($outcome[$counter] ?? 0);
            }
            $report['rows'][] = $outcome['row'];
            DB::table('customer_imports')->where('id', $context['import_id'])->update([
                'resume_row' => $row['row_number'] + 1,
                'updated_at' => now(),
            ]);
        }

        DB::table('customer_imports')->where('id', $context['import_id'])->update([
            'status' => 'completed',
            'total_rows' => $report['total_rows'],
            'valid_rows' => $report['valid_rows'],
            'imported_rows' => $report['imported_rows'],
            'updated_rows' => $report['updated_rows'],
            'skipped_rows' => $report['skipped_rows'],
            'duplicate_rows' => $report['duplicate_rows'],
            'warning_rows' => $report['warning_rows'],
            'error_rows' => $report['error_rows'],
            'unresolved_countries' => $report['unresolved_countries'],
            'unresolved_companies' => $report['unresolved_companies'],
            'completed_at' => now(),
            'imported_at' => now(),
            'updated_at' => now(),
        ]);

        return $report;
    }

    private function reconcileCounters(array $report, int $importId): array
    {
        $rows = DB::table('customer_import_rows')->where('customer_import_id', $importId);
        $report['valid_rows'] = (clone $rows)->whereIn('action', ['imported', 'linked_existing', 'linked_existing_invoice'])->count();
        $report['imported_rows'] = (clone $rows)->where('action', 'imported')->count();
        $report['updated_rows'] = (clone $rows)->where('action', 'linked_existing')->count();
        $report['duplicate_rows'] = (clone $rows)->where('action', 'linked_existing_invoice')->count();
        $report['skipped_rows'] = (clone $rows)->whereIn('status', ['invalid', 'review_required'])->count();
        $report['warning_rows'] = (clone $rows)->whereJsonLength('validation_warnings', '>', 0)->count();
        $report['error_rows'] = (clone $rows)->whereJsonLength('validation_errors', '>', 0)->count();
        $report['unresolved_countries'] = (clone $rows)->whereNull('resolved_country_iso2')->count();

        return $report;
    }

    private function processRow(array $row, array $context, array $options): array
    {
        $normalized = $this->normalizer->normalize($row['mapped']);
        $countryResolution = $this->countries->resolve(
            $normalized['account_country_name'],
            $normalized['source_country_text'],
            $options['country'] ?? null,
        );
        $validation = $this->normalizer->validate($row['mapped'], $normalized, $countryResolution);
        $provenance = $this->provenance($context, $row, $normalized);
        $rowHash = hash('sha256', $this->json($row['raw']));
        $idempotencyKey = hash('sha256', $context['source_key'].'|'.($normalized['external_invoice_id'] ?: $rowHash));
        $rowId = $this->upsertImportRow($context, $row, $normalized, $countryResolution, $validation, $provenance, $rowHash, $idempotencyKey);
        $this->replaceRowErrors($context, $rowId, $row, $validation, $provenance);

        if ($validation['errors'] !== []) {
            DB::table('customer_import_rows')->where('id', $rowId)->update(['status' => 'invalid', 'action' => 'skipped', 'processed_at' => now(), 'updated_at' => now()]);

            return $this->rowOutcome($row, 'skipped', $validation, [
                'skipped_rows' => 1,
                'error_rows' => 1,
                'warning_rows' => $validation['warnings'] !== [] ? 1 : 0,
                'unresolved_countries' => ! $countryResolution['resolved'] ? 1 : 0,
            ]);
        }
        if ($countryResolution['conflict']) {
            DB::table('customer_import_rows')->where('id', $rowId)->update(['status' => 'review_required', 'action' => 'country_conflict', 'processed_at' => now(), 'updated_at' => now()]);

            return $this->rowOutcome($row, 'country_conflict', $validation, ['skipped_rows' => 1, 'warning_rows' => 1]);
        }

        $country = $this->countries->ensure($countryResolution['resolved']);
        $marketplaceId = $this->resolveMarketplace($options['marketplace'] ?? $context['marketplace_id'] ?? null, $country['id']);
        $duplicateInvoice = DB::table('customer_invoice_references')
            ->where('source_key', $context['source_key'])
            ->where('external_invoice_id', $normalized['external_invoice_id'])
            ->first();
        if ($duplicateInvoice) {
            DB::table('customer_import_rows')->where('id', $rowId)->update([
                'status' => 'duplicate',
                'action' => 'linked_existing_invoice',
                'customer_account_id' => $duplicateInvoice->customer_account_id,
                'customer_contact_id' => $duplicateInvoice->customer_contact_id,
                'customer_profile_id' => $duplicateInvoice->customer_profile_id,
                'customer_invoice_reference_id' => $duplicateInvoice->id,
                'processed_at' => now(),
                'updated_at' => now(),
            ]);

            return $this->rowOutcome($row, 'linked_existing_invoice', $validation, ['valid_rows' => 1, 'duplicate_rows' => 1]);
        }

        $existingEmail = DB::table('contact_email_addresses')->where('normalized_email', $normalized['contact_email'])->first();
        if ($existingEmail) {
            $existingContact = DB::table('customer_contacts')->find($existingEmail->customer_contact_id);
            $existingAccount = $existingContact?->customer_account_id ? DB::table('customer_accounts')->find($existingContact->customer_account_id) : null;
            if ($existingAccount && ($existingAccount->normalized_name !== $normalized['normalized_company_name'] || (int) $existingAccount->country_id !== (int) $country['id'])) {
                $conflict = ['field' => 'contact_email', 'code' => 'email_company_conflict', 'message' => 'Email is already linked to a different company or country. Manual review is required.'];
                $validation['errors'][] = $conflict;
                $this->replaceRowErrors($context, $rowId, $row, $validation, $provenance);
                DB::table('customer_import_rows')->where('id', $rowId)->update(['status' => 'review_required', 'action' => 'email_company_conflict', 'processed_at' => now(), 'updated_at' => now()]);

                return $this->rowOutcome($row, 'email_company_conflict', $validation, ['skipped_rows' => 1, 'error_rows' => 1]);
            }
        }

        [$accountId, $accountCreated] = $this->upsertAccount($normalized, $country, $marketplaceId, $context, $provenance);
        [$profileId, $profileCreated] = $this->upsertProfile($normalized, $country, $marketplaceId, $context, $options);
        [$contactId, $contactCreated] = $this->upsertContact($normalized, $accountId, $profileId, $country, $marketplaceId, $context, $provenance, $existingEmail);
        $this->linkProfile($profileId, $accountId, $contactId, $context);
        $emailAddressId = $this->upsertEmail($normalized, $contactId, $profileId, $context, $provenance);
        $this->upsertPhone($normalized, $contactId, $country, $context, $provenance);
        $this->upsertAddress($normalized, $accountId, $contactId, $profileId, $country, $context, $rowId, $provenance);
        $this->ensureConsentRecords($normalized, $contactId, $profileId, $emailAddressId, $country, $marketplaceId, $context, $rowId);
        $invoiceId = $this->createInvoiceReference($normalized, $accountId, $contactId, $profileId, $context, $rowId, $provenance);
        $this->storeProvenance($context, $rowId, $provenance, [
            'customer_account' => $accountId,
            'customer_contact' => $contactId,
            'customer_profile' => $profileId,
            'contact_email_address' => $emailAddressId,
            'customer_invoice_reference' => $invoiceId,
        ]);

        $action = ($accountCreated || $profileCreated || $contactCreated) ? 'imported' : 'linked_existing';
        DB::table('customer_import_rows')->where('id', $rowId)->update([
            'status' => 'processed',
            'action' => $action,
            'customer_account_id' => $accountId,
            'customer_contact_id' => $contactId,
            'customer_profile_id' => $profileId,
            'customer_invoice_reference_id' => $invoiceId,
            'processed_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->rowOutcome($row, $action, $validation, [
            'valid_rows' => 1,
            'imported_rows' => $action === 'imported' ? 1 : 0,
            'updated_rows' => $action === 'linked_existing' ? 1 : 0,
            'warning_rows' => $validation['warnings'] !== [] ? 1 : 0,
        ], compact('accountId', 'contactId', 'profileId', 'invoiceId'));
    }

    private function upsertAccount(array $normalized, array $country, ?int $marketplaceId, array $context, array $provenance): array
    {
        $account = DB::table('customer_accounts')->where('normalized_name', $normalized['normalized_company_name'])->where('country_id', $country['id'])->first();
        if ($account) {
            DB::table('customer_accounts')->where('id', $account->id)->update(['last_customer_import_id' => $context['import_id'], 'updated_at' => now()]);

            return [$account->id, false];
        }
        $id = DB::table('customer_accounts')->insertGetId([
            'legal_name' => $normalized['company_name'],
            'display_name' => $normalized['company_name'],
            'normalized_name' => $normalized['normalized_company_name'],
            'primary_domain' => $normalized['contact_email_domain'],
            'country_id' => $country['id'],
            'marketplace_id' => $marketplaceId,
            'customer_source_id' => $context['source_id'],
            'last_customer_import_id' => $context['import_id'],
            'customer_type' => 'business',
            'status' => 'active',
        ] + $provenance + ['created_at' => now(), 'updated_at' => now()]);

        return [$id, true];
    }

    private function upsertProfile(array $normalized, array $country, ?int $marketplaceId, array $context, array $options): array
    {
        $profile = DB::table('customer_profiles')->whereRaw('LOWER(email) = ?', [$normalized['contact_email']])->first();
        if ($profile) {
            $updates = ['last_customer_import_id' => $context['import_id'], 'updated_at' => now()];
            if (! $profile->marketplace_id && $marketplaceId) {
                $updates['marketplace_id'] = $marketplaceId;
            }
            if ($options['update_existing'] ?? false) {
                foreach (['first_name', 'last_name'] as $field) {
                    $candidate = $normalized['contact_name_parts'][$field] ?? null;
                    if (blank($profile->{$field} ?? null) && filled($candidate)) {
                        $updates[$field] = $candidate;
                    }
                }
                if (blank($profile->phone ?? null) && filled($normalized['contact_phone'])) {
                    $updates['phone'] = $normalized['contact_phone'];
                }
            }
            DB::table('customer_profiles')->where('id', $profile->id)->update($updates);

            return [$profile->id, false];
        }
        $id = DB::table('customer_profiles')->insertGetId([
            'first_name' => $normalized['contact_name_parts']['first_name'] ?: $normalized['contact_name'],
            'last_name' => $normalized['contact_name_parts']['last_name'],
            'email' => $normalized['contact_email'],
            'phone' => $normalized['contact_phone'] ?: null,
            'country_id' => $country['id'],
            'marketplace_id' => $marketplaceId,
            'preferred_language' => 'en',
            'customer_type' => 'business',
            'lifecycle_stage' => 'customer',
            'source' => $context['source_name'],
            'marketing_opt_in' => false,
            'whatsapp_opt_in' => false,
            'transactional_eligible' => true,
            'marketing_status' => config('customer_import.default_marketing_status', 'unknown'),
            'last_customer_import_id' => $context['import_id'],
            'status' => 'active',
            'metadata' => $this->json(['marketplace_id' => $marketplaceId, 'import_profile' => $context['profile'], 'name_parse' => $normalized['contact_name_parts']]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$id, true];
    }

    private function upsertContact(array $normalized, int $accountId, int $profileId, array $country, ?int $marketplaceId, array $context, array $provenance, ?object $existingEmail): array
    {
        $contact = $existingEmail ? DB::table('customer_contacts')->find($existingEmail->customer_contact_id) : null;
        $contact ??= DB::table('customer_contacts')
            ->where('customer_account_id', $accountId)
            ->where('normalized_name', $normalized['normalized_contact_name'])
            ->first();
        if ($contact) {
            DB::table('customer_contacts')->where('id', $contact->id)->update([
                'customer_profile_id' => $contact->customer_profile_id ?: $profileId,
                'last_customer_import_id' => $context['import_id'],
                'updated_at' => now(),
            ]);

            return [$contact->id, false];
        }
        $parts = $normalized['contact_name_parts'];
        $id = DB::table('customer_contacts')->insertGetId([
            'customer_account_id' => $accountId,
            'customer_profile_id' => $profileId,
            'country_id' => $country['id'],
            'marketplace_id' => $marketplaceId,
            'last_customer_import_id' => $context['import_id'],
            'full_name' => $normalized['contact_name'],
            'original_full_name' => $normalized['original_contact_name'],
            'normalized_name' => $normalized['normalized_contact_name'],
            'first_name' => $parts['first_name'],
            'middle_name' => $parts['middle_name'],
            'last_name' => $parts['last_name'],
            'status' => 'active',
            'transactional_eligible' => true,
            'marketing_status' => config('customer_import.default_marketing_status', 'unknown'),
            'metadata' => $this->json(['name_parse_confidence' => $parts['parse_confidence']]),
        ] + $provenance + ['created_at' => now(), 'updated_at' => now()]);

        return [$id, true];
    }

    private function linkProfile(int $profileId, int $accountId, int $contactId, array $context): void
    {
        DB::table('customer_profiles')->where('id', $profileId)->update([
            'customer_account_id' => $accountId,
            'customer_contact_id' => $contactId,
            'last_customer_import_id' => $context['import_id'],
            'transactional_eligible' => true,
            'updated_at' => now(),
        ]);
    }

    private function upsertEmail(array $normalized, int $contactId, int $profileId, array $context, array $provenance): int
    {
        $existing = DB::table('contact_email_addresses')->where('normalized_email', $normalized['contact_email'])->first();
        if ($existing) {
            DB::table('contact_email_addresses')->where('id', $existing->id)->update(['last_customer_import_id' => $context['import_id'], 'updated_at' => now()]);

            return $existing->id;
        }

        return DB::table('contact_email_addresses')->insertGetId([
            'customer_contact_id' => $contactId,
            'customer_profile_id' => $profileId,
            'last_customer_import_id' => $context['import_id'],
            'email' => $normalized['contact_email'],
            'normalized_email' => $normalized['contact_email'],
            'domain' => $normalized['contact_email_domain'],
            'is_primary' => true,
            'is_valid' => true,
            'is_verified' => false,
            'status' => 'active',
        ] + $provenance + ['created_at' => now(), 'updated_at' => now()]);
    }

    private function upsertPhone(array $normalized, int $contactId, array $country, array $context, array $provenance): void
    {
        if (! $normalized['contact_phone']) {
            return;
        }
        DB::table('contact_phone_numbers')->updateOrInsert(
            ['customer_contact_id' => $contactId, 'normalized_phone' => $normalized['contact_phone']],
            [
                'last_customer_import_id' => $context['import_id'],
                'phone' => $normalized['contact_phone'],
                'country_calling_code' => $country['phone_code'] ?? null,
                'is_primary' => true,
                'is_valid' => true,
                'status' => 'active',
            ] + $provenance + ['updated_at' => now(), 'created_at' => now()],
        );
    }

    private function upsertAddress(array $normalized, int $accountId, int $contactId, int $profileId, array $country, array $context, int $rowId, array $provenance): void
    {
        if (! $normalized['address_line_1']) {
            return;
        }
        DB::table('customer_addresses')->updateOrInsert(
            ['customer_profile_id' => $profileId, 'type' => 'billing', 'address_line1' => $normalized['address_line_1']],
            [
                'customer_account_id' => $accountId,
                'customer_contact_id' => $contactId,
                'customer_import_id' => $context['import_id'],
                'customer_import_row_id' => $rowId,
                'name' => $normalized['company_name'],
                'phone' => $normalized['contact_phone'] ?: null,
                'country_id' => $country['id'],
                'postal_code' => $normalized['postal_code'] ?: null,
                'original_city' => $normalized['city'] ?: null,
                'original_country' => $normalized['account_country_name'] ?: null,
                'original_region' => $normalized['source_region_name'] ?: null,
                'is_default' => true,
                'provenance' => $this->json($provenance),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );
    }

    private function ensureConsentRecords(array $normalized, int $contactId, int $profileId, int $emailAddressId, array $country, ?int $marketplaceId, array $context, int $rowId): void
    {
        $base = [
            'customer_profile_id' => $profileId,
            'customer_contact_id' => $contactId,
            'contact_email_address_id' => $emailAddressId,
            'customer_import_id' => $context['import_id'],
            'customer_import_row_id' => $rowId,
            'email' => $normalized['contact_email'],
            'channel' => 'email',
            'source' => 'customer_invoice_import',
            'jurisdiction' => $country['iso_code_2'],
            'country_policy' => 'admin_review_required',
            'marketplace_id' => $marketplaceId,
            'policy_version' => '2026-07',
            'recorded_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
        $hasMarketing = DB::table('customer_consents')->where('email', $normalized['contact_email'])->where('channel', 'email')->where('purpose', 'marketing')->exists();
        if (! $hasMarketing) {
            DB::table('customer_consents')->insert($base + [
                'purpose' => 'marketing',
                'granted' => false,
                'status' => 'unknown',
                'lawful_basis' => null,
                'evidence' => $this->json(['explicit_consent' => false, 'source_type' => 'invoice_contact']),
            ]);
        }
        $hasTransactional = DB::table('customer_consents')->where('email', $normalized['contact_email'])->where('channel', 'email')->where('purpose', 'transactional')->exists();
        if (! $hasTransactional) {
            DB::table('customer_consents')->insert($base + [
                'purpose' => 'transactional',
                'granted' => true,
                'status' => 'transactional_only',
                'lawful_basis' => 'existing_customer_relationship',
                'evidence' => $this->json(['source_invoice_id' => $normalized['external_invoice_id']]),
                'granted_at' => now(),
                'effective_at' => now(),
            ]);
        }
    }

    private function createInvoiceReference(array $normalized, int $accountId, int $contactId, int $profileId, array $context, int $rowId, array $provenance): int
    {
        return DB::table('customer_invoice_references')->insertGetId([
            'customer_source_id' => $context['source_id'],
            'customer_import_id' => $context['import_id'],
            'customer_import_row_id' => $rowId,
            'customer_account_id' => $accountId,
            'customer_contact_id' => $contactId,
            'customer_profile_id' => $profileId,
            'source_key' => $context['source_key'],
            'external_invoice_id' => $normalized['external_invoice_id'],
            'invoice_or_sales_order_date' => $normalized['invoice_or_sales_order_date'],
            'metadata' => $this->json(['source_region_name' => $normalized['source_region_name']]),
        ] + $provenance + ['created_at' => now(), 'updated_at' => now()]);
    }

    private function upsertImportRow(array $context, array $row, array $normalized, array $country, array $validation, array $provenance, string $rowHash, string $idempotencyKey): int
    {
        $resolved = $country['resolved'];
        $existing = DB::table('customer_import_rows')
            ->where('customer_import_id', $context['import_id'])
            ->where('row_number', $row['row_number'])
            ->first();
        $values = [
            'customer_import_file_id' => $context['file_id'],
            'row_hash' => $rowHash,
            'idempotency_key' => $idempotencyKey,
            'status' => 'validated',
            'resolved_country_id' => $resolved['id'] ?? null,
            'resolved_country_iso2' => $resolved['iso_code_2'] ?? null,
            'resolved_country_iso3' => $resolved['iso_code_3'] ?? null,
            'resolved_country_name' => $resolved['name'] ?? null,
            'country_resolution_confidence' => $country['confidence'],
            'country_conflict' => $country['conflict'],
            'original_values' => $this->json($row['raw']),
            'normalized_values' => $this->json($normalized),
            'validation_errors' => $this->json($validation['errors']),
            'validation_warnings' => $this->json($validation['warnings']),
            'attempts' => ((int) ($existing->attempts ?? 0)) + 1,
        ] + $provenance + ['updated_at' => now()];
        if ($existing) {
            DB::table('customer_import_rows')->where('id', $existing->id)->update($values);
        } else {
            DB::table('customer_import_rows')->insert($values + [
                'customer_import_id' => $context['import_id'],
                'row_number' => $row['row_number'],
                'created_at' => now(),
            ]);
        }

        return (int) DB::table('customer_import_rows')->where('customer_import_id', $context['import_id'])->where('row_number', $row['row_number'])->value('id');
    }

    private function replaceRowErrors(array $context, int $rowId, array $row, array $validation, array $provenance): void
    {
        DB::table('customer_import_errors')->where('customer_import_row_id', $rowId)->delete();
        foreach ([...$validation['errors'], ...$validation['warnings']] as $item) {
            DB::table('customer_import_errors')->insert([
                'customer_import_id' => $context['import_id'],
                'customer_import_row_id' => $rowId,
                'row_number' => $row['row_number'],
                'field' => $item['field'] ?? null,
                'code' => $item['code'],
                'severity' => in_array($item, $validation['errors'], true) ? 'error' : 'warning',
                'message' => $item['message'],
                'context' => $this->json(['source_header' => $item['field'] ?? null]),
            ] + $provenance + ['created_at' => now(), 'updated_at' => now()]);
        }
    }

    private function createImportContext(string $path, array $data, string $profileName, array $options): array
    {
        $profile = config("customer_import.profiles.{$profileName}", []);
        $uuid = (string) Str::uuid();
        $sourceName = (string) ($options['source'] ?? $profile['source_name'] ?? $profileName);
        $sourceKey = Str::slug($sourceName) ?: 'customer-import';
        $source = DB::table('customer_sources')->where('code', $sourceKey)->first();
        if (! $source) {
            $sourceId = DB::table('customer_sources')->insertGetId([
                'name' => $sourceName,
                'code' => $sourceKey,
                'source_url' => $profile['source_url'] ?? null,
                'source_page_url' => $profile['source_page_url'] ?? null,
                'source_file' => basename($path),
                'license_note' => $profile['license_note'] ?? null,
                'confidence_level' => 'source_provided',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $sourceId = $source->id;
        }
        $directory = storage_path("app/private/customer-imports/{$uuid}");
        File::ensureDirectoryExists($directory);
        $storedPath = $directory.'/'.basename($path);
        if (realpath($path) !== realpath($storedPath)) {
            File::copy($path, $storedPath);
        }
        $sha256 = hash_file('sha256', $path);
        $marketplaceId = $this->resolveMarketplace($options['marketplace'] ?? null, null);
        $provenance = [
            'source_name' => $sourceName,
            'source_url' => $profile['source_url'] ?? null,
            'source_file' => basename($path),
            'source_page_url' => $profile['source_page_url'] ?? null,
            'downloaded_at' => date('Y-m-d H:i:s', filemtime($path)),
            'imported_at' => null,
            'data_year' => null,
            'license_note' => $profile['license_note'] ?? null,
            'confidence_level' => 'source_provided',
            'original_raw_value' => null,
            'normalized_value' => null,
        ];
        $importId = DB::table('customer_imports')->insertGetId([
            'uuid' => $uuid,
            'batch_key' => $options['batch'] ?? null,
            'customer_source_id' => $sourceId,
            'marketplace_id' => $marketplaceId,
            'uploaded_by' => $options['uploaded_by'] ?? null,
            'profile_name' => $profileName,
            'worksheet' => $data['worksheet'],
            'file_name' => basename($storedPath),
            'original_file_name' => basename($path),
            'stored_file_path' => $storedPath,
            'file_sha256' => $sha256,
            'status' => 'pending',
            'dry_run' => false,
            'only_valid' => (bool) ($options['only_valid'] ?? false),
            'update_existing' => (bool) ($options['update_existing'] ?? false),
            'no_marketing_consent' => true,
            'column_mapping' => $this->json($data['mapping']),
            'rules' => $this->json($options),
            'resume_row' => 2,
            'total_rows' => count($data['rows']),
            'consent_state' => 'unknown',
        ] + $provenance + ['created_at' => now(), 'updated_at' => now()]);
        $fileId = DB::table('customer_import_files')->insertGetId([
            'customer_import_id' => $importId,
            'original_file_name' => basename($path),
            'stored_file_path' => $storedPath,
            'mime_type' => File::mimeType($path),
            'extension' => strtolower(pathinfo($path, PATHINFO_EXTENSION)),
            'size_bytes' => filesize($path),
            'sha256' => $sha256,
            'worksheet_names' => $this->json($data['worksheets']),
            'selected_worksheet' => $data['worksheet'],
            'is_primary' => true,
        ] + $provenance + ['created_at' => now(), 'updated_at' => now()]);

        return compact('importId', 'fileId') + [
            'import_id' => $importId,
            'file_id' => $fileId,
            'uuid' => $uuid,
            'profile' => $profileName,
            'source_id' => $sourceId,
            'source_name' => $sourceName,
            'source_key' => $sourceKey,
            'source_url' => $profile['source_url'] ?? null,
            'source_page_url' => $profile['source_page_url'] ?? null,
            'source_file' => basename($path),
            'license_note' => $profile['license_note'] ?? null,
            'downloaded_at' => date('Y-m-d H:i:s', filemtime($path)),
            'marketplace_id' => $marketplaceId,
            'resume_row' => 2,
        ];
    }

    private function resumeContext(object $import, string $path, array $data, array $options): array
    {
        $file = DB::table('customer_import_files')->where('customer_import_id', $import->id)->where('is_primary', true)->first();
        $source = $import->customer_source_id ? DB::table('customer_sources')->find($import->customer_source_id) : null;

        return [
            'import_id' => $import->id,
            'file_id' => $file->id ?? null,
            'uuid' => $import->uuid,
            'profile' => $import->profile_name,
            'source_id' => $import->customer_source_id,
            'source_name' => $import->source_name ?: ($source->name ?? 'Customer Invoice Details'),
            'source_key' => $source->code ?? Str::slug($import->source_name ?: 'customer-import'),
            'source_url' => $import->source_url,
            'source_page_url' => $import->source_page_url,
            'source_file' => $import->source_file ?: basename($path),
            'license_note' => $import->license_note,
            'downloaded_at' => $import->downloaded_at,
            'marketplace_id' => $options['marketplace'] ?? $import->marketplace_id,
            'resume_row' => (int) $import->resume_row,
        ];
    }

    private function provenance(array $context, array $row, array $normalized): array
    {
        return [
            'source_name' => $context['source_name'],
            'source_url' => $context['source_url'],
            'source_file' => $context['source_file'],
            'source_page_url' => rtrim((string) $context['source_page_url'], '#').'#row-'.$row['row_number'],
            'downloaded_at' => $context['downloaded_at'],
            'imported_at' => now(),
            'data_year' => $normalized['invoice_or_sales_order_date'] ? (int) substr($normalized['invoice_or_sales_order_date'], 0, 4) : (int) now()->year,
            'license_note' => $context['license_note'],
            'confidence_level' => 'exact_source_row',
            'original_raw_value' => $this->json($row['raw']),
            'normalized_value' => $this->json($normalized),
        ];
    }

    private function storeProvenance(array $context, int $rowId, array $provenance, array $entities): void
    {
        foreach ($entities as $entityType => $entityId) {
            DB::table('customer_data_provenance')->updateOrInsert(
                ['entity_type' => $entityType, 'entity_id' => $entityId, 'customer_import_row_id' => $rowId],
                ['customer_import_id' => $context['import_id']] + $provenance + ['updated_at' => now(), 'created_at' => now()],
            );
        }
    }

    private function resolveMarketplace(mixed $value, ?int $countryId): ?int
    {
        if (is_numeric($value) && DB::table('marketplaces')->where('id', (int) $value)->exists()) {
            return (int) $value;
        }
        if (filled($value)) {
            $id = DB::table('marketplaces')->whereRaw('UPPER(code) = ?', [mb_strtoupper((string) $value)])
                ->orWhereRaw('LOWER(name) = ?', [mb_strtolower((string) $value)])->value('id');
            if ($id) {
                return (int) $id;
            }
        }
        if ($countryId) {
            $id = DB::table('marketplaces')->where('country_id', $countryId)->where('is_active', true)->value('id');
            if ($id) {
                return (int) $id;
            }
        }

        return DB::table('marketplaces')->whereNull('country_id')->where('is_active', true)->value('id') ?: null;
    }

    private function recordSystemError(array $context, array $row, Throwable $exception): void
    {
        DB::table('customer_import_errors')->insert([
            'customer_import_id' => $context['import_id'],
            'row_number' => $row['row_number'],
            'code' => 'system_error',
            'severity' => 'error',
            'message' => Str::limit($exception->getMessage(), 2000),
            'context' => $this->json(['exception' => $exception::class]),
            'source_name' => $context['source_name'],
            'source_url' => $context['source_url'],
            'source_file' => $context['source_file'],
            'source_page_url' => $context['source_page_url'],
            'license_note' => $context['license_note'],
            'confidence_level' => 'system_reported',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function rowOutcome(array $row, string $action, array $validation, array $counters, array $ids = []): array
    {
        return $counters + ['row' => [
            'row_number' => $row['row_number'],
            'action' => $action,
            'errors' => $validation['errors'],
            'warnings' => $validation['warnings'],
        ] + $ids];
    }

    private function findImport(string $identifier): ?object
    {
        return DB::table('customer_imports')->where('uuid', $identifier)
            ->when(is_numeric($identifier), fn ($query) => $query->orWhere('id', (int) $identifier))
            ->orWhere('batch_key', $identifier)
            ->first();
    }

    private function emptyReport(): array
    {
        return [
            'dry_run' => false,
            'import_id' => null,
            'import_uuid' => null,
            'profile' => null,
            'worksheet' => null,
            'total_rows' => 0,
            'valid_rows' => 0,
            'imported_rows' => 0,
            'updated_rows' => 0,
            'skipped_rows' => 0,
            'duplicate_rows' => 0,
            'warning_rows' => 0,
            'error_rows' => 0,
            'unresolved_countries' => 0,
            'unresolved_companies' => 0,
            'consent_state' => 'unknown',
            'rows' => [],
        ];
    }

    private function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
