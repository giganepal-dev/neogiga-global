<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Pcb\PcbProject;
use App\Services\Account\AccountHubService;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use App\Services\Partner\PartnerCountryService;
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
    private const SUPPORT_CATEGORIES = ['support', 'product_qa', 'seller', 'general'];

    private const NOTIFICATION_TYPES = [
        'order_updates' => 'Order and delivery updates',
        'rfq_updates' => 'RFQ and quotation updates',
        'support_updates' => 'Support ticket updates',
        'price_alerts' => 'Price alerts',
        'back_in_stock' => 'Back-in-stock alerts',
        'promotions' => 'NeoGiga offers and product news',
        'security' => 'Security and account alerts',
    ];

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
        $pcbProjects = PcbProject::query()->visibleTo($user)->latest()->limit(4)->get();

        $stats = [
            ['label' => 'Orders', 'value' => $this->ownedCount('orders', $user->id), 'url' => '/account/orders'],
            ['label' => 'Open RFQs', 'value' => $this->ownedCount('rfq_requests', $user->id, ['status' => ['open', 'pending', 'submitted', 'quoted']]), 'url' => '/account/rfqs'],
            ['label' => 'Quotations', 'value' => $this->ownedCount('quotations', $user->id), 'url' => '/account/quotations'],
            ['label' => 'Saved parts', 'value' => $this->ownedCount('saved_products', $user->id), 'url' => '/account/saved'],
            ['label' => 'PCB projects', 'value' => PcbProject::query()->visibleTo($user)->count(), 'url' => '/account/pcb'],
        ];

        return view('frontend.account.dashboard', $this->shell($request) + compact(
            'user', 'orders', 'rfqs', 'quotations', 'applications', 'pcbProjects', 'stats'
        ));
    }

    public function pcb(Request $request): View
    {
        $user = $request->user();
        $visibleProjects = fn () => PcbProject::query()->visibleTo($user);
        $projects = $visibleProjects()
            ->withCount(['files', 'quoteConfigurations'])
            ->with(['members' => fn ($query) => $query
                ->where('user_id', $user->id)
                ->where(fn ($expiry) => $expiry
                    ->whereNull('access_expires_at')
                    ->orWhere('access_expires_at', '>', now()))])
            ->latest()
            ->paginate(12);

        $projects->getCollection()->each(function (PcbProject $project) use ($user): void {
            $project->account_access_role = match (true) {
                (int) $project->user_id === (int) $user->id => 'Owner',
                $project->members->isNotEmpty() => ucfirst((string) $project->members->first()->role),
                (bool) (($user->organization_id ?? null) && (int) $project->organization_id === (int) $user->organization_id) => 'Organization',
                default => 'Member',
            };
        });

        $summary = [
            'total' => $visibleProjects()->count(),
            'active' => $visibleProjects()->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'quotes' => $visibleProjects()->whereIn('status', ['quote_pending', 'quoted', 'awaiting_approval'])->count(),
            'production' => $visibleProjects()->whereIn('status', ['ordered', 'manufacturing', 'inspection', 'shipped'])->count(),
        ];
        $pcbBaseUrl = 'https://'.config('pcb.domain', 'pcb.neogiga.com').'/en';

        return view('frontend.account.pcb', $this->shell($request) + compact('projects', 'summary', 'pcbBaseUrl'));
    }

    public function orders(Request $request): View
    {
        return $this->tablePage($request, 'Orders', 'Track order, payment and fulfilment progress.',
            $this->hub->owned('orders', $request->user()->id), [
                'order_number' => 'Order', 'status' => 'Status', 'payment_status' => 'Payment',
                'grand_total' => 'Total', 'currency_code' => 'Currency', 'created_at' => 'Placed',
            ], '/en/products', 'Start shopping', '/account/orders');
    }

    public function showOrder(Request $request, int $order): View
    {
        return $this->recordPage($request, 'orders', $order, 'Order details', [
            'order_items' => ['foreign' => 'order_id', 'title' => 'Items', 'columns' => ['product_name' => 'Product', 'product_sku' => 'SKU', 'quantity' => 'Quantity', 'unit_price' => 'Unit price', 'total_price' => 'Line total']],
            'payments' => ['foreign' => 'order_id', 'title' => 'Payments', 'columns' => ['payment_number' => 'Payment', 'payment_method' => 'Method', 'amount' => 'Amount', 'currency_code' => 'Currency', 'status' => 'Status']],
            'shipments' => ['foreign' => 'order_id', 'title' => 'Shipments', 'columns' => ['tracking_number' => 'Tracking', 'carrier' => 'Carrier', 'shipping_method' => 'Method', 'status' => 'Status', 'shipped_at' => 'Shipped']],
        ], '/account/orders');
    }

    public function rfqs(Request $request): View
    {
        return $this->tablePage($request, 'RFQ requests', 'Your sourcing requests and quotation progress.',
            $this->hub->owned('rfq_requests', $request->user()->id), [
                'rfq_number' => 'RFQ', 'company_name' => 'Company', 'status' => 'Status',
                'currency' => 'Currency', 'created_at' => 'Created',
            ], '/en/rfq', 'Create RFQ', '/account/rfqs');
    }

    public function showRfq(Request $request, int $rfq): View
    {
        return $this->recordPage($request, 'rfq_requests', $rfq, 'RFQ details', [
            'rfq_items' => ['foreign' => 'rfq_request_id', 'title' => 'Requested items', 'columns' => ['name' => 'Item', 'sku' => 'SKU', 'quantity' => 'Quantity', 'target_price' => 'Target price', 'notes' => 'Notes']],
            'quotations' => ['foreign' => 'rfq_request_id', 'title' => 'Quotations', 'columns' => ['quote_number' => 'Quote', 'status' => 'Status', 'grand_total' => 'Total', 'currency' => 'Currency', 'valid_until' => 'Valid until']],
        ], '/account/rfqs');
    }

    public function quotations(Request $request): View
    {
        return $this->tablePage($request, 'Quotations', 'Commercial offers issued for your account and RFQs.',
            $this->hub->owned('quotations', $request->user()->id), [
                'quote_number' => 'Quote', 'status' => 'Status', 'grand_total' => 'Total',
                'currency' => 'Currency', 'valid_until' => 'Valid until', 'created_at' => 'Issued',
            ], '/account/rfqs', 'View RFQs', '/account/quotations');
    }

    public function showQuotation(Request $request, int $quotation): View
    {
        return $this->recordPage($request, 'quotations', $quotation, 'Quotation details', [
            'quotation_items' => ['foreign' => 'quotation_id', 'title' => 'Quoted items', 'columns' => ['name' => 'Item', 'sku' => 'SKU', 'quantity' => 'Quantity', 'unit_price' => 'Unit price', 'tax_amount' => 'Tax', 'line_total' => 'Line total']],
        ], '/account/quotations');
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
        $notifications = $this->hub->owned('notification_delivery_logs', $request->user()->id);
        $stored = Schema::hasTable('notification_preferences')
            ? DB::table('notification_preferences')->where('user_id', $request->user()->id)->get()->keyBy('notification_type')
            : collect();
        $preferences = collect(self::NOTIFICATION_TYPES)->map(function (string $label, string $type) use ($stored) {
            $row = $stored->get($type);

            return (object) [
                'type' => $type, 'label' => $label,
                'email_enabled' => $row ? (bool) $row->email_enabled : true,
                'push_enabled' => $row ? (bool) $row->push_enabled : true,
                'sms_enabled' => $row ? (bool) $row->sms_enabled : false,
                'whatsapp_enabled' => $row ? (bool) $row->whatsapp_enabled : false,
                'is_mandatory' => $type === 'security' || (bool) ($row->is_mandatory ?? false),
            ];
        });

        return view('frontend.account.notifications', $this->shell($request) + compact('notifications', 'preferences'));
    }

    public function updateNotificationPreferences(Request $request): RedirectResponse
    {
        abort_unless(Schema::hasTable('notification_preferences'), 503, 'Notification preferences are being upgraded.');
        $data = $request->validate(['preferences' => ['nullable', 'array']]);
        $submitted = $data['preferences'] ?? [];
        $marketplace = $this->marketplaces->context($request)['current'] ?? null;

        DB::transaction(function () use ($request, $submitted, $marketplace): void {
            foreach (self::NOTIFICATION_TYPES as $type => $label) {
                $values = is_array($submitted[$type] ?? null) ? $submitted[$type] : [];
                $mandatory = $type === 'security';
                DB::table('notification_preferences')->updateOrInsert([
                    'user_id' => $request->user()->id,
                    'marketplace_id' => $marketplace?->id,
                    'notification_type' => $type,
                ], [
                    'email_enabled' => $mandatory || isset($values['email']),
                    'push_enabled' => $mandatory || isset($values['push']),
                    'sms_enabled' => isset($values['sms']),
                    'whatsapp_enabled' => isset($values['whatsapp']),
                    'is_mandatory' => $mandatory,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }
        });

        $this->hub->audit($request->user()->id, 'notification_preferences_updated', [], $request);

        return back()->with('success', 'Notification preferences updated.');
    }

    public function support(Request $request): View
    {
        return view('frontend.account.support', $this->shell($request) + [
            'tickets' => $this->hub->owned('support_tickets', $request->user()->id),
            'categories' => self::SUPPORT_CATEGORIES,
        ]);
    }

    public function storeSupport(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:190'], 'message' => ['required', 'string', 'max:5000'],
            'category' => ['required', 'in:'.implode(',', self::SUPPORT_CATEGORIES)], 'priority' => ['required', 'in:low,medium,high'],
        ]);
        $ticketId = DB::transaction(function () use ($request, $data): int {
            $id = DB::table('support_tickets')->insertGetId([
                'ticket_number' => 'CST-'.now()->format('YmdHis').'-'.random_int(100, 999),
                'user_id' => $request->user()->id, 'subject' => $data['subject'], 'description' => $data['message'],
                'priority' => $data['priority'], 'status' => 'open', 'category' => $data['category'],
                'metadata' => json_encode(['channel' => 'customer_account']), 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('support_ticket_messages')->insert([
                'support_ticket_id' => $id, 'user_id' => $request->user()->id, 'sender_type' => 'customer',
                'message' => $data['message'], 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $id;
        });

        return redirect('/account/support/'.$ticketId)->with('success', 'Support ticket created.');
    }

    public function showSupport(Request $request, int $ticket): View
    {
        $ticketRow = DB::table('support_tickets')->where('id', $ticket)->where('user_id', $request->user()->id)->first();
        abort_unless($ticketRow, 404);
        $messages = DB::table('support_ticket_messages')->where('support_ticket_id', $ticket)->orderBy('id')->get();

        return view('frontend.account.support-show', $this->shell($request) + ['ticket' => $ticketRow, 'messages' => $messages]);
    }

    public function replySupport(Request $request, int $ticket): RedirectResponse
    {
        $ticketRow = DB::table('support_tickets')->where('id', $ticket)->where('user_id', $request->user()->id)->first();
        abort_unless($ticketRow, 404);
        $data = $request->validate(['message' => ['required', 'string', 'max:5000']]);
        DB::transaction(function () use ($request, $ticketRow, $data): void {
            DB::table('support_ticket_messages')->insert([
                'support_ticket_id' => $ticketRow->id, 'user_id' => $request->user()->id, 'sender_type' => 'customer',
                'message' => $data['message'], 'created_at' => now(), 'updated_at' => now(),
            ]);
            $status = match ($ticketRow->status) {
                'resolved', 'closed' => 'open', 'waiting_customer' => 'in_progress', default => $ticketRow->status
            };
            DB::table('support_tickets')->where('id', $ticketRow->id)->update(['status' => $status, 'updated_at' => now()]);
        });

        return back()->with('success', 'Reply sent.');
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

    public function applications(Request $request, PartnerCountryService $partnerCountries): View
    {
        $countryOptions = $partnerCountries->options($request);

        return view('frontend.account.applications', $this->shell($request) + [
            'catalog' => $this->hub->roleCatalog(),
            'applications' => $this->hub->owned('account_applications', $request->user()->id),
            'partnerCountries' => $countryOptions['countries'],
            'detectedPartnerCountry' => $countryOptions['detected_country'],
        ]);
    }

    public function storeApplication(Request $request, PartnerCountryService $partnerCountries): RedirectResponse
    {
        abort_unless(Schema::hasTable('account_applications'), 503, 'Account applications are being upgraded.');
        $data = $request->validate([
            'role_key' => ['required', 'in:'.implode(',', array_keys($this->hub->roleCatalog()))],
            'company_name' => ['required', 'string', 'max:190'], 'legal_name' => ['nullable', 'string', 'max:190'],
            'registration_number' => ['nullable', 'string', 'max:100'], 'tax_number' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['required', 'string', 'max:40'], 'website' => ['nullable', 'url', 'max:255'],
            'territory' => ['nullable', 'string', 'max:190'], 'business_description' => ['required', 'string', 'max:5000'],
            'country_id' => ['nullable', 'integer', 'min:1'], 'operating_scope' => ['nullable', 'in:country,global'],
            'documents.*' => ['file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:10240'],
        ]);
        if (in_array($data['role_key'], ['seller', 'regional_distributor', 'global_distributor'], true)) {
            $data['country_id'] = $partnerCountries->resolveSignupCountry($request, $data['country_id'] ?? null);
            $data['operating_scope'] = match ($data['role_key']) {
                'global_distributor' => 'global',
                'regional_distributor' => 'country',
                default => $partnerCountries->normalizeScope($data['operating_scope'] ?? null),
            };
            $data['marketplace_id'] = $partnerCountries->marketplaceIdForCountry($data['country_id']);
        } else {
            $data['country_id'] = ! empty($data['country_id']) ? $partnerCountries->assertActiveCountryId($data['country_id']) : null;
            $data['operating_scope'] = 'country';
            $data['marketplace_id'] = $request->user()->home_marketplace_id;
        }
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
                'marketplace_id' => $data['marketplace_id'], 'country_id' => $data['country_id'],
                'operating_scope' => $data['operating_scope'], 'submitted_at' => now(), 'created_at' => now(), 'updated_at' => now(),
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

    public function resubmitApplication(Request $request, int $application): RedirectResponse
    {
        $data = $request->validate([
            'applicant_notes' => ['required', 'string', 'max:5000'],
            'business_description' => ['nullable', 'string', 'max:5000'],
            'documents.*' => ['file', 'mimes:pdf,png,jpg,jpeg,webp', 'max:10240'],
        ]);

        DB::transaction(function () use ($request, $application, $data): void {
            $row = DB::table('account_applications')->where('id', $application)
                ->where('user_id', $request->user()->id)->lockForUpdate()->first();
            abort_unless($row, 404);
            abort_unless($row->status === 'needs_information', 409, 'Only applications awaiting information can be resubmitted.');

            $updates = [
                'status' => 'submitted', 'applicant_notes' => $data['applicant_notes'],
                'submitted_at' => now(), 'reviewed_at' => null, 'reviewed_by' => null, 'updated_at' => now(),
            ];
            if (! empty($data['business_description'])) {
                $updates['business_description'] = $data['business_description'];
            }
            DB::table('account_applications')->where('id', $row->id)->update($updates);

            foreach ($request->file('documents', []) as $file) {
                $path = $file->store('account-applications/'.$row->id, 'local');
                DB::table('account_application_documents')->insert([
                    'account_application_id' => $row->id, 'user_id' => $request->user()->id,
                    'document_type' => 'supporting_document', 'original_name' => $file->getClientOriginalName(),
                    'storage_disk' => 'local', 'storage_path' => $path, 'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(), 'sha256' => hash_file('sha256', $file->getRealPath()),
                    'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('account_application_events')->insert([
                'account_application_id' => $row->id, 'actor_user_id' => $request->user()->id,
                'event_type' => 'resubmitted', 'from_status' => 'needs_information', 'to_status' => 'submitted',
                'notes' => $data['applicant_notes'], 'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500), 'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Application resubmitted for review.');
    }

    public function switchRole(Request $request): RedirectResponse
    {
        $data = $request->validate(['role_key' => ['required', 'string', 'max:50']]);

        return redirect()->to($this->hub->switchRole($request->user(), $request, $data['role_key']));
    }

    private function tablePage(Request $request, string $title, string $intro, $rows, array $columns, string $actionUrl, string $actionLabel, ?string $detailBase = null): View
    {
        return view('frontend.account.table', $this->shell($request) + compact('title', 'intro', 'rows', 'columns', 'actionUrl', 'actionLabel', 'detailBase'));
    }

    private function recordPage(Request $request, string $table, int $id, string $title, array $relationDefinitions, string $backUrl): View
    {
        abort_unless(Schema::hasTable($table) && Schema::hasColumn($table, 'user_id'), 404);
        $record = DB::table($table)->where('id', $id)->where('user_id', $request->user()->id)->first();
        abort_unless($record, 404);

        $relations = collect($relationDefinitions)->map(function (array $definition, string $relationTable) use ($id) {
            if (! Schema::hasTable($relationTable) || ! Schema::hasColumn($relationTable, $definition['foreign'])) {
                return ['title' => $definition['title'], 'columns' => $definition['columns'], 'rows' => collect()];
            }

            return [
                'title' => $definition['title'], 'columns' => $definition['columns'],
                'rows' => DB::table($relationTable)->where($definition['foreign'], $id)->orderBy('id')->get(),
            ];
        })->values();

        return view('frontend.account.record', $this->shell($request) + compact('record', 'title', 'relations', 'backUrl'));
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
