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

    public function profile(Request $r): View
    {
        return view('distributor.profile', ['distributor' => $r->attributes->get('distributor')]);
    }

    public function updateProfile(Request $r): RedirectResponse
    {
        $distributor = $r->attributes->get('distributor');
        $meta = json_decode($distributor->metadata ?? '{}', true) ?: [];
        $meta['website'] = $r->input('website', $meta['website'] ?? '');
        $meta['description'] = $r->input('description', $meta['description'] ?? '');
        DB::table('distributors')->where('id', $distributor->id)->update([
            'name' => $r->input('name'),
            'phone' => $r->input('phone'),
            'country_id' => $r->input('country_id') ?: null,
            'metadata' => json_encode($meta),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Profile updated.');
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
