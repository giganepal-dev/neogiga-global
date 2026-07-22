<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Account\AccountHubService;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class CustomerDashboardController extends Controller
{
    public function __construct(
        private readonly AccountHubService $hub,
        private readonly GlobalMarketplaceContextService $marketplaces,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $orders = $this->hub->owned('orders', $user->id, 5);
        $rfqs = $this->hub->owned('rfq_requests', $user->id, 5);
        $quotations = $this->hub->owned('quotations', $user->id, 5);
        $applications = $this->hub->owned('account_applications', $user->id, 5);

        $stats = [
            ['label' => 'Orders', 'value' => $this->ownedCount('orders', $user->id), 'url' => '/account/orders'],
            ['label' => 'Open RFQs', 'value' => $this->ownedCount('rfq_requests', $user->id, ['status' => ['open', 'pending', 'submitted', 'quoted']]), 'url' => '/account/rfqs'],
            ['label' => 'Quotations', 'value' => $this->ownedCount('quotations', $user->id), 'url' => '/account/quotations'],
            ['label' => 'Saved parts', 'value' => $this->ownedCount('saved_products', $user->id), 'url' => '/account/saved'],
        ];

        return view('frontend.account.dashboard', $this->shell($request) + compact(
            'user', 'orders', 'rfqs', 'quotations', 'applications', 'stats'
        ));
    }

    public function orders(Request $request): View
    {
        return $this->tablePage($request, 'Orders', 'Track order, payment and fulfilment progress.',
            $this->hub->owned('orders', $request->user()->id), [
                'order_number' => 'Order', 'status' => 'Status', 'payment_status' => 'Payment',
                'grand_total' => 'Total', 'currency_code' => 'Currency', 'created_at' => 'Placed',
            ], '/en/products', 'Start shopping');
    }

    public function rfqs(Request $request): View
    {
        return $this->tablePage($request, 'RFQ requests', 'Your sourcing requests and quotation progress.',
            $this->hub->owned('rfq_requests', $request->user()->id), [
                'rfq_number' => 'RFQ', 'company_name' => 'Company', 'status' => 'Status',
                'currency' => 'Currency', 'created_at' => 'Created',
            ], '/en/rfq', 'Create RFQ');
    }

    public function quotations(Request $request): View
    {
        return $this->tablePage($request, 'Quotations', 'Commercial offers issued for your account and RFQs.',
            $this->hub->owned('quotations', $request->user()->id), [
                'quote_number' => 'Quote', 'status' => 'Status', 'grand_total' => 'Total',
                'currency' => 'Currency', 'valid_until' => 'Valid until', 'created_at' => 'Issued',
            ], '/account/rfqs', 'View RFQs');
    }

    public function bom(Request $request): View
    {
        $rows = $this->hub->owned('bom_projects', $request->user()->id);
        if ($rows->isEmpty()) {
            $rows = $this->hub->owned('bom_imports', $request->user()->id);
        }

        return $this->tablePage($request, 'BOM projects', 'Saved bills of materials, matching runs and sourcing progress.', $rows, [
            'name' => 'Project', 'title' => 'Title', 'status' => 'Status', 'source_file_name' => 'Source file',
            'matched_rows' => 'Matched', 'created_at' => 'Created',
        ], '/en/bom', 'Open BOM tool');
    }

    public function saved(Request $request): View
    {
        $saved = collect();
        if (Schema::hasTable('saved_products') && Schema::hasTable('products')) {
            $productColumns = Schema::getColumnListing('products');
            $selects = ['products.id', 'products.name', 'products.slug'];
            foreach (['sku', 'mpn', 'list_price'] as $column) {
                $selects[] = in_array($column, $productColumns, true)
                    ? 'products.'.$column
                    : DB::raw('NULL as '.$column);
            }
            $selects[] = 'saved_products.created_at as saved_at';
            $selects[] = 'saved_products.list_name';
            $saved = DB::table('saved_products')
                ->join('products', 'saved_products.product_id', '=', 'products.id')
                ->where('saved_products.user_id', $request->user()->id)
                ->select($selects)
                ->orderByDesc('saved_products.created_at')->get();
        }

        return view('frontend.account.saved', $this->shell($request) + compact('saved'));
    }

    public function notifications(Request $request): View
    {
        return $this->tablePage($request, 'Notifications', 'Transactional, account and sourcing updates.',
            $this->hub->owned('notification_delivery_logs', $request->user()->id), [
                'title' => 'Notification', 'body' => 'Details', 'channel' => 'Channel',
                'status' => 'Status', 'created_at' => 'Received',
            ], '/account/profile', 'Manage preferences');
    }

    public function support(Request $request): View
    {
        return $this->tablePage($request, 'Support tickets', 'Product, order, RFQ and account support in one place.',
            $this->hub->owned('support_tickets', $request->user()->id), [
                'ticket_number' => 'Ticket', 'subject' => 'Subject', 'category' => 'Category',
                'priority' => 'Priority', 'status' => 'Status', 'updated_at' => 'Updated',
            ], '/contact', 'Contact support');
    }

    public function payments(Request $request): View
    {
        $payments = collect();
        if (Schema::hasTable('payment_transactions') && Schema::hasTable('orders')) {
            $payments = DB::table('payment_transactions')
                ->join('orders', 'payment_transactions.order_id', '=', 'orders.id')
                ->where('orders.user_id', $request->user()->id)
                ->select('payment_transactions.*', 'orders.order_number')
                ->orderByDesc('payment_transactions.created_at')->limit(50)->get();
        }

        return $this->tablePage($request, 'Payments & invoices', 'Payment records remain scoped to orders owned by this account.', $payments, [
            'transaction_id' => 'Transaction', 'order_number' => 'Order', 'gateway' => 'Method',
            'status' => 'Status', 'amount' => 'Amount', 'currency' => 'Currency', 'created_at' => 'Date',
        ], '/account/orders', 'View orders');
    }

    public function profile(Request $request): View
    {
        return view('frontend.account.profile', $this->shell($request) + [
            'user' => $request->user(),
            'profile' => $this->hub->customerProfile($request->user()),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'first_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:40'],
            'company_name' => ['nullable', 'string', 'max:190'],
            'preferred_language' => ['nullable', 'string', 'max:12'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            $request->user()->forceFill(['name' => $data['name']])->save();
            if (! Schema::hasTable('customer_profiles')) {
                return;
            }
            $columns = Schema::getColumnListing('customer_profiles');
            $profile = array_intersect_key([
                'user_id' => $request->user()->id,
                'email' => $request->user()->email,
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'company_name' => $data['company_name'] ?? null,
                'preferred_language' => $data['preferred_language'] ?? 'en',
                'updated_at' => now(),
            ], array_flip($columns));
            $existing = DB::table('customer_profiles')->where('user_id', $request->user()->id)->first();
            if ($existing) {
                DB::table('customer_profiles')->where('id', $existing->id)->update($profile);
            } else {
                $profile['created_at'] = now();
                DB::table('customer_profiles')->insert($profile);
            }
        });

        $this->hub->audit($request->user()->id, 'account_profile_updated', [], $request);

        return back()->with('success', 'Profile updated.');
    }

    public function security(Request $request): View
    {
        return view('frontend.account.security', $this->shell($request) + ['user' => $request->user()]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()],
        ]);
        $request->user()->forceFill(['password' => Hash::make($data['password'])])->save();
        Auth::logoutOtherDevices($data['password']);
        $this->hub->audit($request->user()->id, 'account_password_changed', [], $request);

        return back()->with('success', 'Password changed and other sessions signed out.');
    }

    public function addresses(Request $request): View
    {
        $profile = $this->hub->customerProfile($request->user());
        $addresses = collect();
        if ($profile && Schema::hasTable('customer_addresses')) {
            $addresses = DB::table('customer_addresses')->where('customer_profile_id', $profile->id)->orderByDesc('is_default')->get();
        }

        return view('frontend.account.addresses', $this->shell($request) + compact('profile', 'addresses'));
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:shipping,billing,office,warehouse'], 'name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'], 'address_line1' => ['required', 'string', 'max:500'],
            'address_line2' => ['nullable', 'string', 'max:500'], 'postal_code' => ['nullable', 'string', 'max:30'],
            'original_city' => ['nullable', 'string', 'max:120'], 'original_region' => ['nullable', 'string', 'max:120'],
            'original_country' => ['nullable', 'string', 'max:120'], 'is_default' => ['nullable', 'boolean'],
        ]);
        $profile = $this->hub->customerProfile($request->user());
        abort_unless($profile && Schema::hasTable('customer_addresses'), 422, 'Complete your profile before adding an address.');
        $columns = Schema::getColumnListing('customer_addresses');
        $row = array_intersect_key($data + [
            'customer_profile_id' => $profile->id, 'is_default' => $request->boolean('is_default'),
            'created_at' => now(), 'updated_at' => now(),
        ], array_flip($columns));
        DB::transaction(function () use ($profile, $row, $request): void {
            if ($request->boolean('is_default')) {
                DB::table('customer_addresses')->where('customer_profile_id', $profile->id)->where('type', $row['type'])->update(['is_default' => false]);
            }
            DB::table('customer_addresses')->insert($row);
        });

        return back()->with('success', 'Address saved.');
    }

    public function deleteAddress(Request $request, int $address): RedirectResponse
    {
        $profile = $this->hub->customerProfile($request->user());
        abort_unless($profile, 404);
        DB::table('customer_addresses')->where('id', $address)->where('customer_profile_id', $profile->id)->delete();

        return back()->with('success', 'Address removed.');
    }

    public function applications(Request $request): View
    {
        return view('frontend.account.applications', $this->shell($request) + [
            'catalog' => $this->hub->roleCatalog(),
            'applications' => $this->hub->owned('account_applications', $request->user()->id),
        ]);
    }

    public function storeApplication(Request $request): RedirectResponse
    {
        abort_unless(Schema::hasTable('account_applications'), 503, 'Account applications are being upgraded.');
        $data = $request->validate([
            'role_key' => ['required', 'in:'.implode(',', array_keys($this->hub->roleCatalog()))],
            'company_name' => ['required', 'string', 'max:190'], 'legal_name' => ['nullable', 'string', 'max:190'],
            'registration_number' => ['nullable', 'string', 'max:100'], 'tax_number' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['required', 'string', 'max:40'], 'website' => ['nullable', 'url', 'max:255'],
            'territory' => ['nullable', 'string', 'max:190'], 'business_description' => ['required', 'string', 'max:5000'],
            'documents.*' => ['file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:10240'],
        ]);
        $duplicate = DB::table('account_applications')->where('user_id', $request->user()->id)
            ->where('role_key', $data['role_key'])->whereIn('status', ['draft', 'submitted', 'under_review', 'needs_information', 'approved'])->exists();
        if ($duplicate) {
            return back()->withErrors(['role_key' => 'An active application already exists for this role.'])->withInput();
        }

        DB::transaction(function () use ($request, $data): void {
            $id = DB::table('account_applications')->insertGetId([
                'application_number' => 'NG-'.strtoupper(Str::substr($data['role_key'], 0, 4)).'-'.now()->format('ymd').'-'.strtoupper(Str::random(6)),
                'user_id' => $request->user()->id, 'role_key' => $data['role_key'], 'status' => 'submitted',
                'company_name' => $data['company_name'], 'legal_name' => $data['legal_name'] ?? null,
                'registration_number' => $data['registration_number'] ?? null, 'tax_number' => $data['tax_number'] ?? null,
                'contact_phone' => $data['contact_phone'], 'website' => $data['website'] ?? null,
                'territory' => $data['territory'] ?? null, 'business_description' => $data['business_description'],
                'marketplace_id' => $request->user()->home_marketplace_id, 'submitted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($request->file('documents', []) as $file) {
                $path = $file->store('account-applications/'.$id, 'local');
                DB::table('account_application_documents')->insert([
                    'account_application_id' => $id, 'user_id' => $request->user()->id,
                    'document_type' => 'supporting_document', 'original_name' => $file->getClientOriginalName(),
                    'storage_disk' => 'local', 'storage_path' => $path, 'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath()),
                    'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('account_application_events')->insert([
                'account_application_id' => $id, 'actor_user_id' => $request->user()->id,
                'event_type' => 'submitted', 'to_status' => 'submitted', 'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500), 'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Application submitted for compliance review.');
    }

    public function switchRole(Request $request): RedirectResponse
    {
        $data = $request->validate(['role_key' => ['required', 'string', 'max:50']]);

        return redirect()->to($this->hub->switchRole($request->user(), $request, $data['role_key']));
    }

    private function tablePage(Request $request, string $title, string $intro, $rows, array $columns, string $actionUrl, string $actionLabel): View
    {
        return view('frontend.account.table', $this->shell($request) + compact('title', 'intro', 'rows', 'columns', 'actionUrl', 'actionLabel'));
    }

    private function shell(Request $request): array
    {
        return [
            'accountRoles' => $this->hub->roles($request->user(), $request),
            'marketplaceContext' => $this->marketplaces->context($request),
        ];
    }

    private function ownedCount(string $table, int $userId, array $filters = []): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id')) {
            return 0;
        }
        $query = DB::table($table)->where('user_id', $userId);
        foreach ($filters as $column => $values) {
            if (Schema::hasColumn($table, $column)) {
                $query->whereIn($column, $values);
            }
        }

        return $query->count();
    }
}
