<?php

namespace App\Http\Controllers\Web\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\Product;
use App\Models\Messaging\Conversation;
use App\Models\ResellerRfqAssignment;
use App\Models\ResellerSupportTicket;
use App\Models\ResellerTerritory;
use App\Models\ResellerTerritoryRequest;
use App\Services\Marketplace\UserMarketplaceScopeService;
use App\Services\Messaging\MessagingService;
use App\Services\Reseller\ResellerApplicationService;
use App\Services\Reseller\ResellerContextService;
use App\Services\Reseller\ResellerProductService;
use App\Services\Reseller\ResellerRfqBidService;
use App\Services\Reseller\ResellerTerritoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ResellerPortalController extends Controller
{
    public function showApply(): View|RedirectResponse
    {
        if (Auth::check() && app(ResellerContextService::class)->resellerFor(Auth::user())) {
            return redirect('/reseller');
        }

        return view('reseller.apply', [
            'marketplaces' => Marketplace::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function storeApply(
        Request $request,
        ResellerApplicationService $service,
        UserMarketplaceScopeService $marketplaceScope,
    ): RedirectResponse {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:190'],
            'contact_person' => ['required', 'string', 'max:140'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'marketplace_id' => ['nullable', 'integer', 'exists:marketplaces,id'],
            'registration_number' => ['nullable', 'string', 'max:120'],
            'tax_number' => ['nullable', 'string', 'max:120'],
            'territory_notes' => ['nullable', 'string', 'max:2000'],
            'document_company_reg' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'document_reseller_certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'document_tax_certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'document_gst_info' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if (empty($data['marketplace_id'])) {
            $data['marketplace_id'] = $marketplaceScope->homeMarketplaceIdForRegistration($request);
        }

        $service->apply($data, $request, Auth::user());

        return redirect('/reseller/login')->with('status', 'Reseller application submitted. We will review your documents and activate your regional account.');
    }

    public function showLogin(ResellerContextService $c): View|RedirectResponse
    {
        if (Auth::check() && $c->resellerFor(Auth::user())) {
            return redirect('/reseller');
        }

        return view('reseller.login');
    }

    public function login(Request $r, ResellerContextService $c): RedirectResponse
    {
        $r->validate(['email' => 'required|email|max:190', 'password' => 'required|string|max:120']);
        if (! Auth::attempt($r->only('email', 'password'), $r->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }
        $r->session()->regenerate();
        if (! $c->resellerFor(Auth::user())) {
            Auth::logout();

            return back()->withErrors(['email' => 'No reseller account linked.']);
        }

        return redirect()->intended('/reseller');
    }

    public function logout(Request $r): RedirectResponse
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/reseller/login');
    }

    public function dashboard(Request $r): View
    {
        $reseller = $r->attributes->get('reseller');
        $stats = [
            'product_count' => Product::where('reseller_id', $reseller->id)->count(),
            'order_count' => DB::table('orders')->where('reseller_id', $reseller->id)->count(),
            'rfq_count' => ResellerRfqAssignment::where('reseller_id', $reseller->id)->count(),
            'territory_count' => ResellerTerritory::where('reseller_id', $reseller->id)->where('is_active', true)->count(),
        ];

        return view('reseller.dashboard', compact('reseller', 'stats'));
    }

    public function profile(Request $r): View
    {
        $reseller = $r->attributes->get('reseller');

        return view('reseller.profile', compact('reseller'));
    }

    public function updateProfile(Request $r): RedirectResponse
    {
        $reseller = $r->attributes->get('reseller');
        $reseller->update($r->validate([
            'company_name' => ['required', 'string', 'max:190'],
            'trading_name' => ['nullable', 'string', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'url', 'max:255'],
            'business_address' => ['nullable', 'string', 'max:1000'],
        ]));

        return back()->with('status', 'Profile updated.');
    }

    public function products(Request $r): View
    {
        $reseller = $r->attributes->get('reseller');
        $products = Product::query()
            ->where('reseller_id', $reseller->id)
            ->latest()
            ->paginate(20);

        return view('reseller.products', compact('reseller', 'products'));
    }

    public function createProduct(Request $r): View
    {
        return view('reseller.product-create', ['reseller' => $r->attributes->get('reseller')]);
    }

    public function storeProduct(Request $r, ResellerProductService $products): RedirectResponse
    {
        $reseller = $r->attributes->get('reseller');
        $data = $r->validate([
            'name' => ['required', 'string', 'max:190'],
            'mpn' => ['nullable', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:120'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'link_existing' => ['nullable', 'boolean'],
        ]);

        $products->createListing($reseller, $data);

        return redirect('/reseller/products')->with('status', 'Product listing saved.');
    }

    public function importProducts(Request $r, ResellerProductService $products): RedirectResponse
    {
        $reseller = $r->attributes->get('reseller');
        $data = $r->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:10240']]);
        $result = $products->importCsv($reseller, $data['file']);

        return redirect('/reseller/products')->with('status', "Import complete: {$result['created']} created, {$result['linked']} linked to catalog.");
    }

    public function orders(Request $r): View
    {
        $reseller = $r->attributes->get('reseller');
        $orders = DB::table('orders')->where('reseller_id', $reseller->id)->orderByDesc('created_at')->paginate(20);

        return view('reseller.orders', compact('reseller', 'orders'));
    }

    public function rfqs(Request $r): View
    {
        $reseller = $r->attributes->get('reseller');
        $assignments = ResellerRfqAssignment::query()
            ->where('reseller_id', $reseller->id)
            ->with(['rfq.items'])
            ->latest()
            ->paginate(20);

        return view('reseller.rfqs', compact('reseller', 'assignments'));
    }

    public function bidRfq(Request $r, int $assignment, ResellerRfqBidService $bids): RedirectResponse
    {
        $reseller = $r->attributes->get('reseller');
        $record = ResellerRfqAssignment::where('reseller_id', $reseller->id)->findOrFail($assignment);

        $data = $r->validate([
            'cover_note' => ['nullable', 'string', 'max:3000'],
            'currency' => ['nullable', 'string', 'size:3'],
            'lead_time_days' => ['nullable', 'integer', 'min:1'],
            'valid_until' => ['nullable', 'date'],
            'items' => ['required', 'array'],
            'items.*.rfq_item_id' => ['required', 'integer'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.001'],
        ]);

        $bids->submitBid($record, $reseller, $data);

        return redirect('/reseller/rfqs')->with('status', 'RFQ bid submitted.');
    }

    public function territories(Request $r): View
    {
        $reseller = $r->attributes->get('reseller');
        $territories = ResellerTerritory::where('reseller_id', $reseller->id)->get();
        $requests = ResellerTerritoryRequest::where('reseller_id', $reseller->id)->latest()->get();
        $marketplaces = Marketplace::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('reseller.territories', compact('reseller', 'territories', 'requests', 'marketplaces'));
    }

    public function requestTerritory(Request $r, ResellerTerritoryService $territories): RedirectResponse
    {
        $reseller = $r->attributes->get('reseller');
        $data = $r->validate([
            'marketplace_id' => ['required', 'integer', 'exists:marketplaces,id'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'document_company_reg' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'document_reseller_certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'document_tax_certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $territories->requestExpansion($reseller, $data, $r);

        return back()->with('status', 'Territory expansion request submitted for admin review.');
    }

    public function support(Request $r): View
    {
        $reseller = $r->attributes->get('reseller');
        $tickets = ResellerSupportTicket::where('reseller_id', $reseller->id)->latest()->paginate(20);

        return view('reseller.support', compact('reseller', 'tickets'));
    }

    public function storeSupport(Request $r): RedirectResponse
    {
        $reseller = $r->attributes->get('reseller');
        $data = $r->validate([
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        ResellerSupportTicket::create([
            ...$data,
            'reseller_id' => $reseller->id,
            'user_id' => Auth::id(),
            'ticket_number' => 'RST-'.now()->format('YmdHis').'-'.$reseller->id,
            'status' => 'open',
        ]);

        return back()->with('status', 'Support ticket opened.');
    }

    public function messages(Request $r, MessagingService $messaging): View
    {
        $reseller = $r->attributes->get('reseller');
        $conversations = $messaging->listFor($reseller, 30);

        return view('reseller.messages', compact('reseller', 'conversations'));
    }

    public function showMessage(Request $r, int $conversation, MessagingService $messaging): View
    {
        $reseller = $r->attributes->get('reseller');
        $messages = $messaging->messagesFor(
            Conversation::findOrFail($conversation),
            $reseller
        );

        return view('reseller.message-show', compact('reseller', 'conversation', 'messages'));
    }

    public function replyMessage(Request $r, int $conversation, MessagingService $messaging): RedirectResponse
    {
        $reseller = $r->attributes->get('reseller');
        $data = $r->validate(['body' => ['required', 'string', 'max:5000']]);
        $conv = Conversation::findOrFail($conversation);
        $messaging->send($conv, $reseller, $data['body']);

        return back()->with('status', 'Message sent.');
    }
}
