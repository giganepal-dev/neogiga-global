<?php

namespace App\Http\Controllers\Web\B2B;

use App\Http\Controllers\Controller;
use App\Http\Requests\B2B\B2BApplyRequest;
use App\Models\B2B\B2BQuotation;
use App\Models\B2B\B2BQuoteRequest;
use App\Services\B2B\B2BAccountService;
use App\Services\B2B\B2BContextService;
use App\Services\B2B\B2BQuotationWorkflowService;
use App\Services\B2B\B2BQuoteService;
use App\Services\Marketplace\UserMarketplaceScopeService;
use App\Services\Payments\PaymentMethodPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class B2BPortalController extends Controller
{
    public function showApply(B2BContextService $context): View|RedirectResponse
    {
        if (Auth::check() && $context->accountFor(Auth::user())) {
            return redirect('/b2b');
        }

        return view('b2b.apply', [
            'accountTypes' => config('b2b_institutional.account_types', []),
        ]);
    }

    public function storeApply(
        B2BApplyRequest $request,
        B2BAccountService $service,
        UserMarketplaceScopeService $marketplaceScope,
    ): RedirectResponse {
        $data = $request->validated();
        if (empty($data['marketplace_id'])) {
            $data['marketplace_id'] = $marketplaceScope->homeMarketplaceIdForRegistration($request);
        }

        $service->apply($data, $request, Auth::user());

        return redirect('/b2b/login')->with('status', 'Institutional application submitted. Our team will review your documents and activate your account.');
    }

    public function showLogin(B2BContextService $c): View|RedirectResponse
    {
        if (Auth::check() && $c->accountFor(Auth::user())) {
            return redirect('/b2b');
        }

        return view('b2b.login');
    }

    public function login(Request $r, B2BContextService $c): RedirectResponse
    {
        $r->validate(['email' => 'required|email|max:190', 'password' => 'required|string|max:120']);
        if (! Auth::attempt($r->only('email', 'password'), $r->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }
        $r->session()->regenerate();
        if (! $c->accountFor(Auth::user())) {
            Auth::logout();

            return back()->withErrors(['email' => 'No business account linked.']);
        }

        return redirect()->intended('/b2b');
    }

    public function logout(Request $r): RedirectResponse
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/b2b/login');
    }

    public function dashboard(Request $r): View
    {
        $account = $r->attributes->get('b2b_account');
        $stats = [
            'order_count' => DB::table('orders')->where('b2b_account_id', $account->id)->count(),
            'rfq_count' => B2BQuoteRequest::where('b2b_account_id', $account->id)->count(),
            'quotation_count' => B2BQuotation::where('b2b_account_id', $account->id)->count(),
            'user_count' => DB::table('b2b_account_users')->where('b2b_account_id', $account->id)->where('is_active', true)->count(),
        ];

        return view('b2b.dashboard', compact('account', 'stats'));
    }

    public function orders(Request $r): View
    {
        $account = $r->attributes->get('b2b_account');
        $orders = DB::table('orders')->where('b2b_account_id', $account->id)->orderByDesc('created_at')->paginate(20);

        return view('b2b.orders', compact('account', 'orders'));
    }

    public function products(Request $r): View
    {
        $account = $r->attributes->get('b2b_account');
        $products = DB::table('b2b_price_list_items as i')
            ->join('b2b_price_lists as l', 'l.id', '=', 'i.b2b_price_list_id')
            ->join('products as p', 'p.id', '=', 'i.product_id')
            ->where('l.b2b_account_id', $account->id)->where('l.is_active', true)
            ->select('p.*', 'i.unit_price', 'i.min_quantity')
            ->orderByDesc('p.id')->paginate(20);

        return view('b2b.products', compact('account', 'products'));
    }

    public function rfqs(Request $r): View
    {
        $account = $r->attributes->get('b2b_account');
        $rfqs = B2BQuoteRequest::query()
            ->where('b2b_account_id', $account->id)
            ->withCount('items')
            ->latest()
            ->paginate(20);

        return view('b2b.rfqs', compact('account', 'rfqs'));
    }

    public function createRfq(Request $r): View
    {
        $account = $r->attributes->get('b2b_account');

        return view('b2b.rfq-create', compact('account'));
    }

    public function storeRfq(Request $r, B2BQuoteService $service): RedirectResponse
    {
        $account = $r->attributes->get('b2b_account');

        $data = $r->validate([
            'notes' => ['nullable', 'string', 'max:3000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:190'],
            'items.*.sku' => ['nullable', 'string', 'max:120'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.target_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $service->create([
            'contact_name' => $account->name,
            'contact_email' => $account->email,
            'currency_code' => data_get($account->metadata, 'currency_code', 'USD'),
            'notes' => $data['notes'] ?? null,
            'items' => $data['items'],
        ], $account->id);

        return redirect('/b2b/rfqs')->with('status', 'Quote request submitted. Our team will send an official quotation shortly.');
    }

    public function quotations(Request $r): View
    {
        $account = $r->attributes->get('b2b_account');
        $quotations = B2BQuotation::query()
            ->where('b2b_account_id', $account->id)
            ->withCount('items')
            ->latest()
            ->paginate(20);

        return view('b2b.quotations', compact('account', 'quotations'));
    }

    public function showQuotation(Request $r, int $quotation, PaymentMethodPolicyService $payments): View
    {
        $account = $r->attributes->get('b2b_account');
        $quote = B2BQuotation::query()
            ->where('b2b_account_id', $account->id)
            ->with('items')
            ->findOrFail($quotation);

        $paymentMethods = $quote->payment_status === 'unlocked'
            ? $payments->allowedMethods($account->marketplace_id, $quote->currency_code)
            : collect();

        return view('b2b.quotation-show', compact('account', 'quote', 'paymentMethods'));
    }

    public function acceptQuotation(Request $r, int $quotation, B2BQuotationWorkflowService $workflow): RedirectResponse
    {
        $account = $r->attributes->get('b2b_account');
        $quote = B2BQuotation::where('b2b_account_id', $account->id)->findOrFail($quotation);
        $workflow->accept($quote, $account);

        return redirect('/b2b/quotations/'.$quotation)->with('status', 'Quotation accepted. You can now proceed to payment.');
    }

    public function payQuotation(Request $r, int $quotation, B2BQuotationWorkflowService $workflow): RedirectResponse
    {
        $account = $r->attributes->get('b2b_account');
        $data = $r->validate(['payment_method' => ['required', 'string', 'max:80']]);
        $quote = B2BQuotation::where('b2b_account_id', $account->id)->findOrFail($quotation);
        $order = $workflow->pay($quote, $account, Auth::user(), $data['payment_method']);

        return redirect('/b2b/orders')->with('status', 'Order '.$order->order_number.' created. Payment is pending confirmation.');
    }
}
