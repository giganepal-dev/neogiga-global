<?php

namespace App\Services\Email\Import;

use App\Models\EmailImport;
use App\Models\EmailImportRow;
use App\Models\EmailSubscriber;
use App\Models\EmailGroup;
use App\Models\Country;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Csv\Reader as CsvReader;
use League\Csv\Writer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class SubscriberImportService
{
    protected array $supportedColumns = [
        'email',
        'first_name',
        'last_name',
        'full_name',
        'company_name',
        'phone',
        'country',
        'country_code',
        'city',
        'region',
        'state_or_province',
        'language',
        'preferred_language',
        'subscriber_type',
        'customer_type',
        'group',
        'tags',
        'consent',
        'source',
        'job_title',
    ];

    protected array $countryCodeMap = [
        'NP' => 'np',
        'IN' => 'in',
        'BT' => 'bt',
        'BD' => 'bd',
        'LK' => 'lk',
        'AU' => 'au',
        'US' => 'us',
        'GB' => 'gb',
    ];

    public function parseFile(EmailImport $import): int
    {
        $filePath = storage_path('app/' . $import->file_path);
        
        if (!file_exists($filePath)) {
            throw new \Exception("Import file not found: {$filePath}");
        }

        $totalRows = 0;

        if ($import->file_type === 'csv') {
            $totalRows = $this->parseCsv($import, $filePath);
        } elseif (in_array($import->file_type, ['xlsx', 'xls'])) {
            $totalRows = $this->parseExcel($import, $filePath);
        } else {
            throw new \Exception("Unsupported file type: {$import->file_type}");
        }

        $import->update(['total_rows' => $totalRows]);

        return $totalRows;
    }

    protected function parseCsv(EmailImport $import, string $filePath): int
    {
        $csv = CsvReader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);
        
        $records = iterator_to_array($csv->getRecords());
        $rowCount = 0;

        DB::transaction(function () use ($import, $records, &$rowCount) {
            foreach ($records as $index => $record) {
                $rowCount++;
                
                EmailImportRow::create([
                    'import_id' => $import->id,
                    'row_number' => $index + 2, // Account for header and 1-based indexing
                    'raw_data' => $record,
                    'status' => EmailImportRow::STATUS_PENDING,
                ]);
            }
        });

        return $rowCount;
    }

    protected function parseExcel(EmailImport $import, string $filePath): int
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        
        if (empty($rows)) {
            return 0;
        }

        $headers = array_shift($rows);
        $rowCount = 0;

        DB::transaction(function () use ($import, $rows, $headers, &$rowCount) {
            foreach ($rows as $index => $row) {
                if (empty(array_filter($row))) {
                    continue;
                }

                $rowCount++;
                $record = array_combine($headers, $row);

                EmailImportRow::create([
                    'import_id' => $import->id,
                    'row_number' => $index + 2,
                    'raw_data' => $record,
                    'status' => EmailImportRow::STATUS_PENDING,
                ]);
            }
        });

        return $rowCount;
    }

    public function validateAndMapRows(EmailImport $import): void
    {
        $import->update(['status' => EmailImport::STATUS_VALIDATING]);

        $rows = $import->rows()->where('status', EmailImportRow::STATUS_PENDING)->cursor();
        
        foreach ($rows as $row) {
            $this->validateAndMapRow($import, $row);
        }

        $this->updateImportStats($import);
    }

    protected function validateAndMapRow(EmailImport $import, EmailImportRow $row): void
    {
        $mappedData = [];
        $errors = [];

        // Extract email
        $email = $this->extractEmail($row->raw_data);
        
        if (empty($email)) {
            $errors[] = 'Missing email address';
            $row->markAsInvalid($errors);
            return;
        }

        // Normalize email
        $normalizedEmail = $this->normalizeEmail($email);
        
        if (!$this->isValidEmail($normalizedEmail)) {
            $errors[] = 'Invalid email format';
            $row->markAsInvalid($errors);
            return;
        }

        $mappedData['email'] = $normalizedEmail;
        $mappedData['normalized_email'] = $normalizedEmail;

        // Map other fields
        $mappedData = array_merge($mappedData, $this->mapFields($row->raw_data, $import));

        // Check for suppression
        if ($import->skip_suppressed && $this->isSuppressed($normalizedEmail)) {
            $row->markAsDuplicate();
            return;
        }

        // Check for unsubscribed
        if ($import->skip_unsubscribed && $this->isUnsubscribed($normalizedEmail)) {
            $row->markAsDuplicate();
            return;
        }

        // Check for existing subscriber
        $existingSubscriber = EmailSubscriber::where('email', $normalizedEmail)->first();
        
        if ($existingSubscriber) {
            if ($import->duplicate_handling === EmailImport::DUPLICATE_SKIP) {
                $row->markAsDuplicate();
                return;
            }
        }

        $row->update([
            'mapped_data' => $mappedData,
            'email' => $normalizedEmail,
            'normalized_email' => $normalizedEmail,
            'country_code' => $mappedData['country_code'] ?? null,
        ]);

        $row->markAsValid();
    }

    protected function extractEmail(array $data): ?string
    {
        $emailKeys = ['email', 'Email', 'EMAIL', 'e-mail', 'E-mail'];
        
        foreach ($emailKeys as $key) {
            if (isset($data[$key]) && !empty(trim($data[$key]))) {
                return trim($data[$key]);
            }
        }

        return null;
    }

    protected function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    protected function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function mapFields(array $rawData, EmailImport $import): array
    {
        $mapped = [];

        $fieldMappings = [
            'first_name' => ['first_name', 'firstName', 'First Name', 'fname'],
            'last_name' => ['last_name', 'lastName', 'Last Name', 'lname'],
            'full_name' => ['full_name', 'fullName', 'Full Name', 'name'],
            'company_name' => ['company_name', 'companyName', 'Company', 'organization'],
            'phone' => ['phone', 'Phone', 'mobile', 'telephone'],
            'country' => ['country', 'Country', 'nation'],
            'country_code' => ['country_code', 'countryCode', 'Country Code', 'iso_code'],
            'city' => ['city', 'City', 'town'],
            'state_or_province' => ['state', 'province', 'region', 'State', 'Province'],
            'preferred_language' => ['language', 'preferred_language', 'lang'],
            'subscriber_type' => ['subscriber_type', 'type', 'contact_type'],
            'customer_type' => ['customer_type', 'cust_type'],
            'job_title' => ['job_title', 'title', 'position', 'designation'],
        ];

        foreach ($fieldMappings as $field => $possibleKeys) {
            foreach ($possibleKeys as $key) {
                if (isset($rawData[$key]) && !empty(trim($rawData[$key]))) {
                    $mapped[$field] = trim($rawData[$key]);
                    break;
                }
            }
        }

        // Set defaults from import settings
        if (empty($mapped['subscriber_type']) && $import->default_subscriber_type) {
            $mapped['subscriber_type'] = $import->default_subscriber_type;
        }

        if (empty($mapped['source']) && $import->default_source) {
            $mapped['source'] = $import->default_source;
        }

        // Normalize country code
        if (!empty($mapped['country'])) {
            $mapped['country_code'] = $this->normalizeCountryCode($mapped['country']);
        }

        if (!empty($mapped['country_code'])) {
            $mapped['country_code'] = strtolower($mapped['country_code']);
        }

        return $mapped;
    }

    protected function normalizeCountryCode(string $country): string
    {
        $country = strtoupper(trim($country));
        
        // Direct ISO code match
        if (isset($this->countryCodeMap[$country])) {
            return $this->countryCodeMap[$country];
        }

        // Country name to code mapping
        $countryNames = [
            'NEPAL' => 'np',
            'INDIA' => 'in',
            'BHUTAN' => 'bt',
            'BANGLADESH' => 'bd',
            'SRI LANKA' => 'lk',
            'AUSTRALIA' => 'au',
            'UNITED STATES' => 'us',
            'USA' => 'us',
            'UNITED KINGDOM' => 'gb',
            'UK' => 'gb',
        ];

        return $countryNames[$country] ?? 'global';
    }

    protected function isSuppressed(string $email): bool
    {
        return DB::table('email_suppressions')
            ->where('email', $email)
            ->where('status', 'active')
            ->exists();
    }

    protected function isUnsubscribed(string $email): bool
    {
        return EmailSubscriber::where('email', $email)
            ->where('status', EmailSubscriber::STATUS_UNSUBSCRIBED)
            ->exists();
    }

    protected function updateImportStats(EmailImport $import): void
    {
        $stats = $import->rows()
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "valid" THEN 1 ELSE 0 END) as valid,
                SUM(CASE WHEN status = "invalid" THEN 1 ELSE 0 END) as invalid,
                SUM(CASE WHEN status = "duplicate" THEN 1 ELSE 0 END) as duplicate
            ')
            ->first();

        $import->update([
            'valid_rows' => $stats->valid ?? 0,
            'invalid_email_rows' => $stats->invalid ?? 0,
            'duplicate_rows' => $stats->duplicate ?? 0,
        ]);
    }

    public function processImport(EmailImport $import): void
    {
        $import->update(['status' => EmailImport::STATUS_IMPORTING]);

        $validRows = $import->rows()
            ->where('status', EmailImportRow::STATUS_VALID)
            ->cursor();

        foreach ($validRows as $row) {
            try {
                $this->processRow($import, $row);
            } catch (Throwable $e) {
                Log::error("Failed to process import row {$row->id}: " . $e->getMessage());
                $row->markAsFailed($e->getMessage());
            }

            // Update progress periodically
            if ($import->rows()->where('status', EmailImportRow::STATUS_IMPORTED)->count() % 100 === 0) {
                $this->updateImportStats($import);
            }
        }

        $this->updateImportStats($import);
        $import->markAsCompleted();
    }

    protected function processRow(EmailImport $import, EmailImportRow $row): void
    {
        $data = $row->mapped_data;
        
        if (empty($data['email'])) {
            throw new \Exception('No email in mapped data');
        }

        $existingSubscriber = EmailSubscriber::where('email', $data['email'])->first();

        if ($existingSubscriber) {
            if ($import->duplicate_handling === EmailImport::DUPLICATE_UPDATE) {
                $this->updateSubscriber($existingSubscriber, $data, $import);
                $row->markAsUpdated($existingSubscriber->id);
            } else {
                $row->markAsDuplicate();
            }
            return;
        }

        // Create new subscriber
        $subscriber = $this->createSubscriber($data, $import);
        $row->markAsImported($subscriber->id);
    }

    protected function createSubscriber(array $data, EmailImport $import): EmailSubscriber
    {
        return DB::transaction(function () use ($data, $import) {
            $subscriber = EmailSubscriber::create([
                'uuid' => Str::uuid(),
                'email' => $data['email'],
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'full_name' => $data['full_name'] ?? null,
                'company_name' => $data['company_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'job_title' => $data['job_title'] ?? null,
                'subscriber_type' => $data['subscriber_type'] ?? $import->default_subscriber_type ?? 'newsletter_subscriber',
                'customer_type' => $data['customer_type'] ?? null,
                'source' => $data['source'] ?? $import->default_source ?? 'bulk_import',
                'country_code' => $data['country_code'] ?? null,
                'city' => $data['city'] ?? null,
                'state_or_province' => $data['state_or_province'] ?? null,
                'preferred_language' => $data['preferred_language'] ?? 'en',
                'status' => EmailSubscriber::STATUS_SUBSCRIBED,
                'subscribed_at' => now(),
            ]);

            // Assign to group
            if ($import->target_group_id) {
                $subscriber->groups()->attach($import->target_group_id, [
                    'assignment_source' => 'bulk_import',
                    'is_primary' => true,
                    'assigned_at' => now(),
                    'assigned_by' => $import->imported_by,
                ]);
            }

            return $subscriber;
        });
    }

    protected function updateSubscriber(EmailSubscriber $subscriber, array $data, EmailImport $import): void
    {
        DB::transaction(function () use ($subscriber, $data, $import) {
            $updateData = [];

            // Only update non-empty fields based on import settings
            foreach ($data as $key => $value) {
                if (!empty($value) && in_array($key, ['first_name', 'last_name', 'full_name', 'company_name', 'phone', 'job_title', 'country_code', 'city', 'state_or_province', 'preferred_language'])) {
                    $updateData[$key] = $value;
                }
            }

            if (!empty($updateData)) {
                $subscriber->update($updateData);
            }

            // Attach to group if specified
            if ($import->target_group_id) {
                if (!$subscriber->groups()->where('email_group_id', $import->target_group_id)->exists()) {
                    $subscriber->groups()->attach($import->target_group_id, [
                        'assignment_source' => 'bulk_import',
                        'is_primary' => false,
                        'assigned_at' => now(),
                        'assigned_by' => $import->imported_by,
                    ]);
                }
            }
        });
    }

    public function generateErrorReport(EmailImport $import): string
    {
        $failedRows = $import->rows()
            ->where('status', EmailImportRow::STATUS_FAILED)
            ->orWhere('status', EmailImportRow::STATUS_INVALID)
            ->get();

        $filePath = $import->getErrorReportPath();
        $directory = dirname($filePath);
        
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $csv = Writer::createFromFileObject(new \SplTempFileObject());
        $csv->insertOne(['Row Number', 'Email', 'Status', 'Error Message']);

        foreach ($failedRows as $row) {
            $csv->insertOne([
                $row->row_number,
                $row->email ?? 'N/A',
                $row->status,
                $row->error_message ?? json_encode($row->validation_errors),
            ]);
        }

        $csv->output(basename($filePath));
        
        return $filePath;
    }
}
