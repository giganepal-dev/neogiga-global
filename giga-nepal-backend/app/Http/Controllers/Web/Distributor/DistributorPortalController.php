<?php

namespace App\Http\Controllers\Web\Distributor;

use App\Http\Controllers\Controller;
use App\Models\Distributor\DistributorSupportTicket;
use App\Models\Distributor\DistributorTerritory;
use App\Models\Distributor\DistributorTerritoryRequest;
use App\Models\Messaging\Conversation;
use App\Services\Distributor\DistributorCommissionService;
use App\Services\Distributor\DistributorContextService;
use App\Services\Distributor\DistributorDashboardService;
use App\Services\Distributor\DistributorTerritoryService;
use App\Services\Distributor\DistributorTerritoryStockService;
use App\Services\Messaging\MessagingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use App\Services\Partner\PartnerCountryService;

class DistributorPortalController extends Controller
{
    public function showLogin(DistributorContextService $c): View|RedirectResponse
    {
        if (Auth::check() && $c->distributorFor(Auth::user())) {
            return redirect('/distributor');
        }

        return view('distributor.login');
    }

    public function login(Request $r, DistributorContextService $c): RedirectResponse
    {
        $r->validate(['email' => 'required|email|max:190', 'password' => 'required|string|max:120']);
        if (! Auth::attempt($r->only('email', 'password'), $r->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }
        $r->session()->regenerate();
        if (! $c->distributorFor(Auth::user())) {
            Auth::logout();

            return back()->withErrors(['email' => 'No distributor account linked.']);
        }

        return redirect()->intended('/distributor');
    }

    public function logout(Request $r): RedirectResponse
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/distributor/login');
    }

    public function dashboard(
        Request $r,
        DistributorDashboardService $dashboard,
        DistributorTerritoryStockService $stock,
        DistributorCommissionService $commissions,
    ): View {
        $distributor = $r->attributes->get('distributor');
        $overview = $dashboard->overview($distributor);
        $stockSummary = $stock->stockSummary($distributor);
        $commissionSummary = $commissions->summary($distributor);
        $downlineStats = $commissions->downlineStats($distributor);
        $recentOrders = Schema::hasTable('distributor_orders')
            ? DB::table('distributor_orders')->where('distributor_id', $distributor->id)->latest('created_at')->limit(5)->get()
            : collect();
        $recentLeads = Schema::hasTable('distributor_leads')
            ? DB::table('distributor_leads')->where('distributor_id', $distributor->id)->latest('created_at')->limit(5)->get()
            : collect();
        $openTickets = Schema::hasTable('distributor_support_tickets')
            ? DB::table('distributor_support_tickets')->where('distributor_id', $distributor->id)->whereIn('status', ['open', 'pending', 'in_progress'])->count()
            : 0;

        return view('distributor.dashboard', compact(
            'distributor',
            'overview',
            'stockSummary',
            'commissionSummary',
            'downlineStats',
            'recentOrders',
            'recentLeads',
            'openTickets',
        ));
    }

    public function profile(Request $r, PartnerCountryService $countries): View
    {
        return view('distributor.profile', ['distributor' => $r->attributes->get('distributor'), 'countries' => $countries->activeCountries()]);
    }

    public function updateProfile(Request $r, PartnerCountryService $countries): RedirectResponse
    {
        $distributor = $r->attributes->get('distributor');
        $data = $r->validate([
            'name' => ['required', 'string', 'max:190'], 'phone' => ['nullable', 'string', 'max:40'],
            'country_id' => ['nullable', 'integer'], 'operating_scope' => ['required', 'in:country,global'],
            'website' => ['nullable', 'url', 'max:255'], 'description' => ['nullable', 'string', 'max:3000'],
        ]);
        $data['country_id'] = ! empty($data['country_id'])
            ? $countries->assertActiveCountryId($data['country_id'])
            : ($distributor->status === 'pending' ? $countries->assertActiveCountryId(null) : null);
        $data['operating_scope'] = $countries->normalizeScope($data['operating_scope']);
        $scopeChangeRestricted = ! in_array($distributor->status, ['pending'], true)
            && ($data['operating_scope'] !== ($distributor->operating_scope ?? 'country') || $data['country_id'] !== (int) $distributor->country_id);
        if ($scopeChangeRestricted) {
            $data['operating_scope'] = $distributor->operating_scope ?? 'country';
            $data['country_id'] = (int) $distributor->country_id;
        }
        $meta = is_array($distributor->metadata)
            ? $distributor->metadata
            : (json_decode($distributor->metadata ?? '{}', true) ?: []);
        $meta['website'] = $data['website'] ?? '';
        $meta['description'] = $data['description'] ?? '';
        DB::table('distributors')->where('id', $distributor->id)->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'country_id' => $data['country_id'],
            'operating_scope' => $data['operating_scope'],
            'metadata' => json_encode($meta),
            'updated_at' => now(),
        ]);

        return back()->with('status', $scopeChangeRestricted
            ? 'Profile updated. Open a support ticket to request a country or global-scope change.'
            : 'Profile and operating scope updated. Territory changes remain subject to NeoGiga approval.');
    }

    public function products(Request $r): View
    {
        $distributor = $r->attributes->get('distributor');
        $products = DB::table('products')->leftJoin('product_categories as c', 'c.id', '=', 'products.category_id')
            ->select('products.*', 'c.name as category_name')->where('distributor_id', $distributor->id)
            ->orderByDesc('id')->paginate(20);

        return view('distributor.products', compact('distributor', 'products'));
    }

    public function orders(Request $r): View
    {
        $distributor = $r->attributes->get('distributor');
        if (Schema::hasTable('distributor_orders')) {
            $orders = DB::table('distributor_orders')
                ->where('distributor_id', $distributor->id)
                ->orderByDesc('created_at')
                ->paginate(20);
        } else {
            $orders = DB::table('orders')
                ->where('distributor_id', $distributor->id)
                ->orderByDesc('created_at')
                ->paginate(20);
        }

        return view('distributor.orders', compact('distributor', 'orders'));
    }

    public function territoryStock(Request $r, DistributorTerritoryStockService $stock): View
    {
        $distributor = $r->attributes->get('distributor');

        return view('distributor.territory-stock', [
            'distributor' => $distributor,
            'summary' => $stock->stockSummary($distributor),
            'products' => $stock->products($distributor),
            'vendors' => $stock->vendors($distributor),
        ]);
    }

    public function territories(Request $r): View
    {
        $distributor = $r->attributes->get('distributor');
        $territories = DistributorTerritory::where('distributor_id', $distributor->id)->orderBy('territory_name')->get();
        $requests = Schema::hasTable('distributor_territory_requests')
            ? DistributorTerritoryRequest::where('distributor_id', $distributor->id)->latest()->limit(10)->get()
            : collect();

        return view('distributor.territories', compact('distributor', 'territories', 'requests'));
    }

    public function requestTerritory(Request $r, DistributorTerritoryService $territoryService): RedirectResponse
    {
        $distributor = $r->attributes->get('distributor');
        $data = $r->validate([
            'territory_name' => ['required', 'string', 'max:190'],
            'country_id' => ['nullable', 'integer'],
            'region_id' => ['nullable', 'integer'],
            'city_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'document_company_reg' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'document_distributor_agreement' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'document_tax_certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $territoryService->requestExpansion($distributor, $data, $r);

        return back()->with('status', 'Territory expansion request submitted. Admin will review your documents.');
    }

    public function commissions(Request $r, DistributorCommissionService $commissions): View
    {
        $distributor = $r->attributes->get('distributor');

        return view('distributor.commissions', [
            'distributor' => $distributor,
            'summary' => $commissions->summary($distributor),
            'commissions' => $commissions->paginateCommissions($distributor),
        ]);
    }

    public function payouts(Request $r, DistributorCommissionService $commissions): View
    {
        $distributor = $r->attributes->get('distributor');

        return view('distributor.payouts', [
            'distributor' => $distributor,
            'payouts' => $commissions->paginatePayouts($distributor),
        ]);
    }

    public function downlines(Request $r, DistributorCommissionService $commissions): View
    {
        $distributor = $r->attributes->get('distributor');

        return view('distributor.downlines', [
            'distributor' => $distributor,
            'downlines' => $commissions->downlines($distributor),
            'stats' => $commissions->downlineStats($distributor),
        ]);
    }

    public function leads(Request $r, DistributorTerritoryStockService $stock): View
    {
        $distributor = $r->attributes->get('distributor');
        $leads = Schema::hasTable('distributor_leads')
            ? DB::table('distributor_leads')->where('distributor_id', $distributor->id)->orderByDesc('id')->paginate(20)
            : new LengthAwarePaginator([], 0, 20);

        return view('distributor.leads', [
            'distributor' => $distributor,
            'leads' => $leads,
            'leadSummary' => $stock->leadsSummary($distributor),
        ]);
    }

    public function support(Request $r): View
    {
        $distributor = $r->attributes->get('distributor');
        $tickets = Schema::hasTable('distributor_support_tickets')
            ? DistributorSupportTicket::where('distributor_id', $distributor->id)->latest()->paginate(20)
            : new LengthAwarePaginator([], 0, 20);

        return view('distributor.support', compact('distributor', 'tickets'));
    }

    public function storeSupport(Request $r): RedirectResponse
    {
        $distributor = $r->attributes->get('distributor');
        $data = $r->validate([
            'subject' => ['required', 'string', 'max:190'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        DistributorSupportTicket::create([
            ...$data,
            'distributor_id' => $distributor->id,
            'user_id' => Auth::id(),
            'ticket_number' => 'DST-'.now()->format('YmdHis').'-'.$distributor->id,
            'status' => 'open',
        ]);

        return back()->with('status', 'Support ticket opened.');
    }

    public function messages(Request $r, MessagingService $messaging): View
    {
        $distributor = $r->attributes->get('distributor');
        $conversations = $messaging->listFor($distributor, 30);

        return view('distributor.messages', compact('distributor', 'conversations'));
    }

    public function showMessage(Request $r, int $conversation, MessagingService $messaging): View
    {
        $distributor = $r->attributes->get('distributor');
        $messages = $messaging->messagesFor(Conversation::findOrFail($conversation), $distributor);

        return view('distributor.message-show', compact('distributor', 'conversation', 'messages'));
    }

    public function replyMessage(Request $r, int $conversation, MessagingService $messaging): RedirectResponse
    {
        $distributor = $r->attributes->get('distributor');
        $data = $r->validate(['body' => ['required', 'string', 'max:5000']]);
        $messaging->send(Conversation::findOrFail($conversation), $distributor, $data['body']);

        return back()->with('status', 'Message sent.');
    }
}
