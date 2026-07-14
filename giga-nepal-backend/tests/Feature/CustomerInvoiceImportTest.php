<?php

namespace Tests\Feature;

use App\Services\CustomerImport\CustomerImportNormalizer;
use App\Services\CustomerImport\CustomerImportService;
use App\Services\CustomerImport\SpreadsheetCustomerReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class CustomerInvoiceImportTest extends TestCase
{
    use RefreshDatabase;

    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        parent::tearDown();
    }

    public function test_reference_headers_map_and_sri_lanka_row_previews_without_writes(): void
    {
        $path = $this->workbook([$this->noratelRow()]);
        $reader = app(SpreadsheetCustomerReader::class);
        $preview = $reader->preview($path, 'Customer Invoice Details');
        $this->assertSame('Customer Invoice Details', $preview['worksheet']);
        $this->assertCount(12, $preview['headers']);
        $this->assertSame('Customer Contact Name .', $preview['mapping']['contact_name']);

        $before = DB::table('customer_imports')->count();
        $report = app(CustomerImportService::class)->run($path, ['profile' => 'Customer Invoice Details', 'dry_run' => true, 'no_marketing_consent' => true]);
        $this->assertTrue($report['dry_run']);
        $this->assertSame(1, $report['valid_rows']);
        $this->assertSame('LK', $report['rows'][0]['country']['resolved']['iso_code_2']);
        $this->assertSame($before, DB::table('customer_imports')->count());
    }

    public function test_import_is_idempotent_preserves_provenance_and_never_grants_marketing_consent(): void
    {
        $path = $this->workbook([$this->noratelRow()]);
        $imports = app(CustomerImportService::class);
        $first = $imports->run($path, ['profile' => 'Customer Invoice Details', 'no_marketing_consent' => true]);
        $second = $imports->run($path, ['profile' => 'Customer Invoice Details', 'no_marketing_consent' => true]);

        $this->assertSame(1, $first['imported_rows']);
        $this->assertSame(1, $second['duplicate_rows']);
        $this->assertSame(1, DB::table('customer_accounts')->count());
        $this->assertSame(1, DB::table('customer_contacts')->count());
        $this->assertSame(1, DB::table('contact_email_addresses')->count());
        $this->assertSame(1, DB::table('customer_invoice_references')->count());
        $this->assertDatabaseHas('customer_accounts', ['legal_name' => 'NORATEL INTERNATIONAL PVT LTD']);
        $this->assertDatabaseHas('customer_contacts', ['original_full_name' => 'ACHALA GUNASIRI']);
        $this->assertDatabaseHas('contact_email_addresses', ['normalized_email' => 'achala@noratel.lk']);
        $this->assertDatabaseHas('contact_phone_numbers', ['normalized_phone' => '+94112250760']);
        $this->assertDatabaseHas('customer_invoice_references', ['external_invoice_id' => '117493066']);
        $this->assertDatabaseHas('customer_consents', ['email' => 'achala@noratel.lk', 'purpose' => 'marketing', 'granted' => false, 'status' => 'unknown']);
        $this->assertDatabaseHas('customer_consents', ['email' => 'achala@noratel.lk', 'purpose' => 'transactional', 'granted' => true, 'status' => 'transactional_only']);
        $this->assertGreaterThanOrEqual(5, DB::table('customer_data_provenance')->count());
        $this->assertNotNull(DB::table('customer_accounts')->value('original_raw_value'));
        $this->assertNotNull(DB::table('customer_accounts')->value('normalized_value'));
    }

    public function test_invalid_email_and_country_conflict_are_reported_and_existing_values_are_not_overwritten(): void
    {
        DB::table('countries')->insert(['name' => 'India', 'iso_code_2' => 'IN', 'iso_code_3' => 'IND', 'currency_code' => 'INR', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('customer_profiles')->insert([
            'first_name' => 'Manually Corrected', 'email' => 'achala@noratel.lk', 'status' => 'active', 'marketing_opt_in' => false,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $invalid = $this->noratelRow();
        $invalid[8] = 'not-an-email';
        $conflict = $this->noratelRow();
        $conflict[0] = '117493067';
        $conflict[11] = 'India';
        $path = $this->workbook([$invalid, $conflict]);
        $report = app(CustomerImportService::class)->run($path, ['profile' => 'Customer Invoice Details']);

        $this->assertSame(2, $report['skipped_rows']);
        $this->assertDatabaseHas('customer_import_errors', ['code' => 'invalid_email', 'severity' => 'error']);
        $this->assertDatabaseHas('customer_import_errors', ['code' => 'country_conflict', 'severity' => 'warning']);
        $this->assertSame('Manually Corrected', DB::table('customer_profiles')->where('email', 'achala@noratel.lk')->value('first_name'));
    }

    public function test_resume_keeps_prior_counts_and_spreadsheet_exports_escape_formula_prefixes(): void
    {
        $path = $this->workbook([$this->noratelRow()]);
        $imports = app(CustomerImportService::class);
        $first = $imports->run($path, ['profile' => 'Customer Invoice Details']);
        DB::table('customer_imports')->where('id', $first['import_id'])->update(['status' => 'failed']);
        $resumed = $imports->run(null, ['resume' => $first['import_uuid']]);

        $this->assertSame(1, $resumed['imported_rows']);
        $this->assertSame(1, $resumed['valid_rows']);
        $normalizer = app(CustomerImportNormalizer::class);
        foreach (['=SUM(A1:A2)', '+44123', '-10', '@cmd'] as $value) {
            $this->assertSame("'".$value, $normalizer->escapeForSpreadsheetExport($value));
        }
        $this->assertSame('safe', $normalizer->escapeForSpreadsheetExport('safe'));
    }

    private function workbook(array $rows): string
    {
        $headers = ['Invoice ID', 'Salesorder Filed Date', 'Customer Account Address', 'Customer Account City', 'Customer Account Country', 'Customer Account Name', 'Customer Account Postal Code .', 'Customer Account Region', 'Customer Contact Email', 'Customer Contact Name .', 'Customer Contact Telephone Nbr', 'Customer Country Text'];
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customer Invoice Details');
        $sheet->fromArray($headers, null, 'A1');
        foreach ($rows as $index => $row) {
            $sheet->fromArray($row, null, 'A'.($index + 2));
        }
        $path = storage_path('framework/testing/customer-import-'.bin2hex(random_bytes(6)).'.xlsx');
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
        $this->files[] = $path;

        return $path;
    }

    private function noratelRow(): array
    {
        return ['117493066', '2025-10-27', 'GQ11450', 'Katunayake', 'SRI LANKA', 'NORATEL INTERNATIONAL PVT LTD', 'GQ11450', 'Asia Pacific', 'achala@noratel.lk', 'ACHALA GUNASIRI', '+94 112250760', 'Sri Lanka'];
    }
}
