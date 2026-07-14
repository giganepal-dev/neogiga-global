<?php

namespace App\Http\Controllers\Api\Admin\Marketing;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\CustomerImport\CustomerImportNormalizer;
use App\Services\Marketing\CustomerSegmentationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CrmController extends Controller
{
    use ApiResponses;

    public function customers(Request $request): JsonResponse
    {
        $query = DB::table('customer_profiles as p')
            ->leftJoin('customer_contacts as ct', 'ct.id', '=', 'p.customer_contact_id')
            ->leftJoin('customer_accounts as a', 'a.id', '=', 'p.customer_account_id')
            ->leftJoin('countries as c', 'c.id', '=', 'p.country_id')
            ->select([
                'p.id', 'p.marketplace_id', 'p.first_name', 'p.last_name', 'p.email', 'p.phone', 'p.customer_type', 'p.lifecycle_stage',
                'p.total_orders', 'p.total_spent', 'p.last_order_at', 'p.status', 'p.transactional_eligible', 'p.marketing_status',
                'a.id as customer_account_id', 'a.legal_name as company_name', 'a.primary_domain',
                'ct.id as customer_contact_id', 'ct.full_name as contact_name',
                'c.id as country_id', 'c.name as country_name', 'c.iso_code_2', 'c.region as country_region',
            ])
            ->when($request->filled('marketplace'), fn ($q) => $q->where('p.marketplace_id', $request->integer('marketplace')))
            ->when($request->filled('country'), fn ($q) => $q->where(function ($country) use ($request) {
                $value = $request->string('country')->toString();
                $country->where('c.id', is_numeric($value) ? (int) $value : -1)
                    ->orWhereRaw('LOWER(c.name) = ?', [mb_strtolower($value)])
                    ->orWhereRaw('UPPER(c.iso_code_2) = ?', [mb_strtoupper($value)]);
            }))
            ->when($request->filled('company'), fn ($q) => $q->where('a.legal_name', 'like', '%'.$request->string('company')->toString().'%'))
            ->when($request->filled('contact'), fn ($q) => $q->where('ct.full_name', 'like', '%'.$request->string('contact')->toString().'%'))
            ->when($request->filled('domain'), fn ($q) => $q->where('a.primary_domain', mb_strtolower($request->string('domain')->toString())))
            ->when($request->filled('consent_state'), fn ($q) => $q->where('p.marketing_status', $request->string('consent_state')->toString()))
            ->when($request->filled('status'), fn ($q) => $q->where('p.status', $request->string('status')->toString()))
            ->orderByDesc('p.id');

        return $this->success($query->paginate(max(1, min(100, (int) $request->query('per_page', 25)))));
    }

    public function customer(int $id): JsonResponse
    {
        $profile = DB::table('customer_profiles')->find($id);
        if (! $profile) {
            return $this->error('Customer profile not found.', 404);
        }
        $contact = $profile->customer_contact_id ? DB::table('customer_contacts')->find($profile->customer_contact_id) : null;
        $account = $profile->customer_account_id ? DB::table('customer_accounts')->find($profile->customer_account_id) : null;

        return $this->success([
            'profile' => $profile,
            'contact' => $contact,
            'account' => $account,
            'emails' => $contact ? DB::table('contact_email_addresses')->where('customer_contact_id', $contact->id)->get() : [],
            'phones' => $contact ? DB::table('contact_phone_numbers')->where('customer_contact_id', $contact->id)->get() : [],
            'addresses' => DB::table('customer_addresses')->where('customer_profile_id', $id)->get(),
            'invoice_references' => DB::table('customer_invoice_references')->where('customer_profile_id', $id)->orderByDesc('invoice_or_sales_order_date')->get(),
            'consents' => DB::table('customer_consents')->where('customer_profile_id', $id)->orderByDesc('id')->get(),
            'communication_history' => DB::table('communication_logs')->where('customer_contact_id', $contact->id ?? 0)->orderByDesc('id')->limit(100)->get(),
        ]);
    }

    public function accounts(Request $request): JsonResponse
    {
        return $this->success(DB::table('customer_accounts as a')
            ->leftJoin('countries as c', 'c.id', '=', 'a.country_id')
            ->select('a.*', 'c.name as country_name', 'c.iso_code_2')
            ->when($request->filled('marketplace'), fn ($q) => $q->where('a.marketplace_id', $request->integer('marketplace')))
            ->when($request->filled('country'), fn ($q) => $q->where('a.country_id', $request->integer('country')))
            ->when($request->filled('q'), fn ($q) => $q->where('a.legal_name', 'like', '%'.$request->string('q')->toString().'%'))
            ->orderBy('a.legal_name')
            ->paginate(max(1, min(100, (int) $request->query('per_page', 25)))));
    }

    public function contacts(Request $request): JsonResponse
    {
        return $this->success(DB::table('customer_contacts as ct')
            ->leftJoin('customer_accounts as a', 'a.id', '=', 'ct.customer_account_id')
            ->leftJoin('contact_email_addresses as e', function ($join) {
                $join->on('e.customer_contact_id', '=', 'ct.id')->where('e.is_primary', true);
            })
            ->leftJoin('countries as c', 'c.id', '=', 'ct.country_id')
            ->select('ct.*', 'a.legal_name as company_name', 'e.email', 'e.domain', 'c.name as country_name', 'c.iso_code_2')
            ->when($request->filled('marketplace'), fn ($q) => $q->where('ct.marketplace_id', $request->integer('marketplace')))
            ->when($request->filled('country'), fn ($q) => $q->where('c.iso_code_2', mb_strtoupper($request->string('country')->toString())))
            ->when($request->filled('q'), fn ($q) => $q->where(function ($search) use ($request) {
                $term = '%'.$request->string('q')->toString().'%';
                $search->where('ct.full_name', 'like', $term)->orWhere('a.legal_name', 'like', $term)->orWhere('e.email', 'like', $term);
            }))
            ->orderBy('ct.full_name')
            ->paginate(max(1, min(100, (int) $request->query('per_page', 25)))));
    }

    public function countrySummary(Request $request): JsonResponse
    {
        $rows = DB::table('countries as c')
            ->leftJoin('customer_accounts as a', 'a.country_id', '=', 'c.id')
            ->leftJoin('customer_contacts as ct', 'ct.customer_account_id', '=', 'a.id')
            ->leftJoin('contact_email_addresses as e', 'e.customer_contact_id', '=', 'ct.id')
            ->leftJoin('customer_invoice_references as i', 'i.customer_account_id', '=', 'a.id')
            ->when($request->filled('marketplace'), fn ($q) => $q->where('a.marketplace_id', $request->integer('marketplace')))
            ->selectRaw('c.id, c.name, c.iso_code_2, c.iso_code_3, c.region, count(distinct a.id) as total_companies, count(distinct ct.id) as total_contacts, count(distinct case when e.is_valid = 1 then e.id end) as valid_emails, count(distinct case when ct.marketing_status = ? then ct.id end) as marketable_contacts, count(distinct case when ct.marketing_status in (?, ?) then ct.id end) as transactional_or_review_contacts, count(distinct i.id) as invoice_references, max(i.invoice_or_sales_order_date) as last_invoice_date', ['opted_in', 'unknown', 'transactional_only'])
            ->groupBy('c.id', 'c.name', 'c.iso_code_2', 'c.iso_code_3', 'c.region')
            ->havingRaw('count(distinct a.id) > 0')
            ->orderBy('c.name')
            ->get();

        return $this->success($rows);
    }

    public function consents(Request $request): JsonResponse
    {
        return $this->success(DB::table('customer_consents')
            ->when($request->filled('marketplace'), fn ($q) => $q->where('marketplace_id', $request->integer('marketplace')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('purpose'), fn ($q) => $q->where('purpose', $request->string('purpose')->toString()))
            ->orderByDesc('id')
            ->paginate(max(1, min(100, (int) $request->query('per_page', 25)))));
    }

    public function suppressions(Request $request): JsonResponse
    {
        return $this->success(DB::table('suppression_lists')
            ->when($request->filled('reason'), fn ($q) => $q->where('reason_code', $request->string('reason')->toString()))
            ->where('is_active', true)
            ->orderByDesc('id')
            ->paginate(max(1, min(100, (int) $request->query('per_page', 25)))));
    }

    public function export(Request $request, CustomerImportNormalizer $normalizer): StreamedResponse
    {
        $allowed = ['company', 'contact', 'email', 'phone', 'country', 'consent_status'];
        $fields = array_values(array_intersect($allowed, array_filter(explode(',', (string) $request->query('fields', implode(',', $allowed))))));
        abort_if($fields === [], 422, 'At least one authorized export field is required.');

        return response()->streamDownload(function () use ($fields, $normalizer) {
            $stream = fopen('php://output', 'wb');
            fputcsv($stream, $fields);
            DB::table('customer_contacts as ct')
                ->leftJoin('customer_accounts as a', 'a.id', '=', 'ct.customer_account_id')
                ->leftJoin('contact_email_addresses as e', function ($join) {
                    $join->on('e.customer_contact_id', '=', 'ct.id')->where('e.is_primary', true);
                })
                ->leftJoin('contact_phone_numbers as p', function ($join) {
                    $join->on('p.customer_contact_id', '=', 'ct.id')->where('p.is_primary', true);
                })
                ->leftJoin('countries as c', 'c.id', '=', 'ct.country_id')
                ->select('ct.id', 'ct.full_name', 'ct.marketing_status', 'a.legal_name', 'e.email', 'p.phone', 'c.name as country_name')
                ->orderBy('ct.id')
                ->chunkById(500, function ($contacts) use ($stream, $fields, $normalizer) {
                    foreach ($contacts as $contact) {
                        $row = [
                            'company' => $contact->legal_name,
                            'contact' => $contact->full_name,
                            'email' => $contact->email,
                            'phone' => $contact->phone,
                            'country' => $contact->country_name,
                            'consent_status' => $contact->marketing_status,
                        ];
                        fputcsv($stream, array_map([$normalizer, 'escapeForSpreadsheetExport'], array_intersect_key($row, array_flip($fields))));
                    }
                }, 'ct.id', 'id');
            fclose($stream);
        }, 'neogiga-authorized-customer-export.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function segments(): JsonResponse
    {
        return $this->success(DB::table('customer_segments')->orderBy('name')->get());
    }

    public function storeSegment(Request $request, CustomerSegmentationService $segments): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string'],
            'rules' => ['array'],
            'type' => ['nullable', 'string', 'max:40'],
            'is_active' => ['boolean'],
        ]);

        return $this->success(['id' => $segments->create($data)], 201);
    }

    public function refreshSegment(int $id, CustomerSegmentationService $segments): JsonResponse
    {
        return $this->success(['matched' => $segments->refresh($id)]);
    }

    public function contactLists(): JsonResponse
    {
        return $this->success(DB::table('contact_lists')->orderBy('name')->get());
    }

    public function storeContactList(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => 'required|string|max:190', 'description' => 'nullable|string', 'channel' => 'nullable|string|max:40']);
        $id = DB::table('contact_lists')->insertGetId([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'channel' => $data['channel'] ?? 'email',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->success(['id' => $id], 201);
    }

    public function addMembers(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'members' => 'required|array',
            'members.*.email' => 'nullable|email',
            'members.*.phone' => 'nullable|string|max:60',
            'members.*.customer_profile_id' => 'nullable|integer',
        ]);
        foreach ($data['members'] as $member) {
            DB::table('contact_list_members')->insert([
                'contact_list_id' => $id,
                'customer_profile_id' => $member['customer_profile_id'] ?? null,
                'email' => isset($member['email']) ? mb_strtolower($member['email']) : null,
                'phone' => $member['phone'] ?? null,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->success(['message' => 'Members added.']);
    }
}
