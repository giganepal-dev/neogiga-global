<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CustomerImport\CustomerImportNormalizer;
use App\Services\Marketing\MarketingAuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerDataController extends Controller
{
    public function export(Request $request, CustomerImportNormalizer $normalizer, MarketingAuditLogger $audit): StreamedResponse
    {
        $allowed = ['company', 'contact', 'email', 'phone', 'country', 'consent_status'];
        $requested = array_filter(explode(',', (string) $request->query('fields', implode(',', $allowed))));
        $fields = array_values(array_intersect($allowed, $requested));
        abort_if($fields === [], 422, 'At least one authorized field is required.');
        $audit->record($request, 'customers.exported', 'customer_profiles', null, ['fields' => $fields]);

        return response()->streamDownload(function () use ($fields, $normalizer): void {
            $stream = fopen('php://output', 'wb');
            fputcsv($stream, $fields);
            DB::table('customer_contacts as ct')->leftJoin('customer_accounts as a', 'a.id', '=', 'ct.customer_account_id')
                ->leftJoin('contact_email_addresses as e', function ($join) {
                    $join->on('e.customer_contact_id', '=', 'ct.id')->where('e.is_primary', true);
                })
                ->leftJoin('contact_phone_numbers as p', function ($join) {
                    $join->on('p.customer_contact_id', '=', 'ct.id')->where('p.is_primary', true);
                })
                ->leftJoin('countries as c', 'c.id', '=', 'ct.country_id')
                ->select('ct.id', 'ct.full_name', 'ct.marketing_status', 'a.legal_name', 'e.email', 'p.phone', 'c.name as country_name')
                ->orderBy('ct.id')->chunkById(500, function ($rows) use ($stream, $fields, $normalizer): void {
                    foreach ($rows as $row) {
                        $values = ['company' => $row->legal_name, 'contact' => $row->full_name, 'email' => $row->email, 'phone' => $row->phone, 'country' => $row->country_name, 'consent_status' => $row->marketing_status];
                        fputcsv($stream, array_map([$normalizer, 'escapeForSpreadsheetExport'], array_intersect_key($values, array_flip($fields))));
                    }
                }, 'ct.id', 'id');
            fclose($stream);
        }, 'neogiga-authorized-customer-export-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
