<?php

namespace App\Services\Marketing;

use App\Models\EmailMarketing\EmailSubscriber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CampaignContactImportService
{
    public function __construct(
        private CampaignContactNormalizer $normalizer,
        private CampaignContactCountryResolver $countryResolver,
    ) {}

    /**
     * Import campaign contacts from parsed data.
     */
    public function import(array $rows, array $options = []): array
    {
        $report = $this->emptyReport();
        $context = $this->createImportContext($options);

        DB::transaction(function () use ($rows, $options, $context, &$report) {
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 1;
                $normalized = $this->normalizer->normalize($row);
                $validation = $this->normalizer->validate($normalized);

                if (!empty($validation['errors'])) {
                    $report['error_rows']++;
                    $this->logError($context['import_id'], $rowNumber, $validation['errors']);
                    continue;
                }

                $country = $this->countryResolver->resolve(
                    $normalized['country_code'] ?? null,
                    $normalized['country'] ?? null,
                    $options['country'] ?? null,
                );

                $result = $this->upsertSubscriber($normalized, $country, $options, $context);

                if ($result['action'] === 'created') {
                    $report['created']++;
                } elseif ($result['action'] === 'updated') {
                    $report['updated']++;
                } elseif ($result['action'] === 'skipped') {
                    $report['skipped']++;
                    if (($result['reason'] ?? '') === 'suppressed') {
                        $report['suppressed']++;
                    }
                }

                if (!empty($result['group_ids'])) {
                    $report['group_assigned']++;
                }
            }
        });

        $report['total_rows'] = count($rows);
        $report['processed'] = $report['created'] + $report['updated'] + $report['linked'] + $report['skipped'];

        return $report;
    }

    private function createImportContext(array $options): array
    {
        $importId = DB::table('campaign_contact_imports')->insertGetId([
            'name' => $options['name'] ?? 'Campaign Contact Import',
            'source' => $options['source'] ?? 'csv',
            'status' => 'processing',
            'uploaded_by' => $options['uploaded_by'] ?? null,
            'batch' => $options['batch'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'import_id' => $importId,
            'uuid' => Str::uuid(),
        ];
    }

    private function upsertSubscriber(array $normalized, array $country, array $options, array $context): array
    {
        $email = $normalized['email'] ?? null;
        if (empty($email)) {
            return ['action' => 'skipped', 'reason' => 'no_email'];
        }

        $normalizedEmail = mb_strtolower(trim($email));

        // Suppression check — never import unsubscribed/bounced/complained emails
        if ($this->isSuppressed($normalizedEmail)) {
            return ['action' => 'skipped', 'reason' => 'suppressed'];
        }

        // Check for existing subscriber
        $existing = EmailSubscriber::where('normalized_email', $normalizedEmail)->first();

        if ($existing) {
            // Update existing subscriber — never auto-link user_id
            $existing->update([
                'first_name' => $normalized['first_name'] ?? $existing->first_name,
                'last_name' => $normalized['last_name'] ?? $existing->last_name,
                'full_name' => $normalized['full_name'] ?? $existing->full_name,
                'company_name' => $normalized['company_name'] ?? $existing->company_name,
                'phone' => $normalized['phone'] ?? $existing->phone,
                'job_title' => $normalized['job_title'] ?? $existing->job_title,
                'country_code' => $country['code'] ?? $existing->country_code,
                'country_id' => $country['id'] ?? $existing->country_id,
                'metadata' => array_merge($existing->metadata ?? [], [
                    'last_import_id' => $context['import_id'],
                    'last_import_at' => now()->toISOString(),
                ]),
            ]);

            // Assign to groups if specified
            $groupIds = $this->assignGroups($existing, $options);

            return [
                'action' => 'updated',
                'subscriber_id' => $existing->id,
                'group_ids' => $groupIds,
            ];
        }

        // Create new subscriber — NO auto-linking to user accounts
        // Campaign contacts are marketing-only; conversion to customer is explicit
        $subscriber = EmailSubscriber::create([
            'email' => $normalizedEmail,
            'first_name' => $normalized['first_name'] ?? null,
            'last_name' => $normalized['last_name'] ?? null,
            'full_name' => $normalized['full_name'] ?? null,
            'company_name' => $normalized['company_name'] ?? null,
            'phone' => $normalized['phone'] ?? null,
            'job_title' => $normalized['job_title'] ?? null,
            'subscriber_type' => $normalized['subscriber_type'] ?? 'lead',
            'source' => $options['source'] ?? 'import',
            'source_reference' => $options['source_reference'] ?? null,
            'marketplace_id' => $options['marketplace_id'] ?? null,
            'country_id' => $country['id'] ?? null,
            'country_code' => $country['code'] ?? null,
            'preferred_language' => $normalized['language'] ?? 'en',
            'status' => 'pending',
            'metadata' => [
                'import_id' => $context['import_id'],
                'imported_at' => now()->toISOString(),
                'double_opt_in_required' => true,
            ],
        ]);

        // Assign to groups
        $groupIds = $this->assignGroups($subscriber, $options);

        return [
            'action' => 'created',
            'subscriber_id' => $subscriber->id,
            'group_ids' => $groupIds,
        ];
    }

    private function assignGroups(EmailSubscriber $subscriber, array $options): array
    {
        $groupIds = [];

        // Assign to country group if specified
        if (!empty($options['country_group_id'])) {
            $subscriber->groups()->attach($options['country_group_id'], [
                'assignment_source' => 'import',
                'assigned_at' => now(),
            ]);
            $groupIds[] = $options['country_group_id'];
        }

        // Assign to custom groups if specified
        if (!empty($options['group_ids']) && is_array($options['group_ids'])) {
            foreach ($options['group_ids'] as $groupId) {
                $subscriber->groups()->attach($groupId, [
                    'assignment_source' => 'import',
                    'assigned_at' => now(),
                ]);
                $groupIds[] = $groupId;
            }
        }

        return $groupIds;
    }

    /**
     * Check if an email is suppressed — unsubscribed, hard-bounced, complained,
     * or on any suppression list. Returns true if it should NOT be imported.
     */
    private function isSuppressed(string $email): bool
    {
        if ($email === '') {
            return false;
        }

        // Check unsubscribes
        if (DB::table('unsubscribes')->whereRaw('lower(email) = ?', [$email])->exists()) {
            return true;
        }

        // Check email suppressions (hard bounces, complaints, manual suppression)
        if (DB::table('email_suppressions')->whereRaw('lower(email) = ?', [$email])->exists()) {
            return true;
        }

        // Check bounces (permanent/hard only)
        if (DB::table('email_bounces')->whereRaw('lower(email) = ?', [$email])->where('type', 'hard')->exists()) {
            return true;
        }

        // Check suppression lists (domain-wide or pattern-based)
        $domain = substr(strrchr($email, '@') ?: '', 1);
        if ($domain !== '' && DB::table('suppression_lists')->where('domain', $domain)->exists()) {
            return true;
        }

        return false;
    }

    private function logError(int $importId, int $rowNumber, array $errors): void
    {
        foreach ($errors as $error) {
            DB::table('campaign_contact_import_errors')->insert([
                'campaign_contact_import_id' => $importId,
                'row_number' => $rowNumber,
                'field' => $error['field'] ?? 'general',
                'code' => $error['code'] ?? 'validation_error',
                'severity' => $error['severity'] ?? 'error',
                'message' => $error['message'] ?? 'Unknown error',
                'created_at' => now(),
            ]);
        }
    }

    private function emptyReport(): array
    {
        return [
            'total_rows' => 0,
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'linked' => 0,
            'skipped' => 0,
            'suppressed' => 0,
            'error_rows' => 0,
            'group_assigned' => 0,
        ];
    }
}
