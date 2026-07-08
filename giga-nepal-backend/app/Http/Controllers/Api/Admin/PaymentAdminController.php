<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payments\PaymentProvider;
use App\Models\Payments\VendorPayout;
use App\Services\Payments\PaymentProviderManager;
use App\Services\Payments\VendorPayoutService;
use App\Services\Payments\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Payments administration (admin.token gated): provider registry, transaction
 * event audit, store-credit adjustments, and vendor payouts. No provider
 * secrets are accepted or stored here (credentials belong in .env).
 */
class PaymentAdminController extends Controller
{
    public function __construct(
        private readonly PaymentProviderManager $providers,
        private readonly WalletService $wallets,
        private readonly VendorPayoutService $payouts,
    ) {
    }

    // ---- Providers ----------------------------------------------------------

    public function providers(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->providers->all()]);
    }

    public function updateProvider(Request $request, PaymentProvider $provider): JsonResponse
    {
        $data = $request->validate([
            'is_enabled' => ['sometimes', 'boolean'],
            'is_live' => ['sometimes', 'boolean'],
            'name' => ['sometimes', 'string', 'max:120'],
            'supported_currencies' => ['nullable', 'array'],
            'config' => ['nullable', 'array'],          // PUBLIC settings only
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        // Defensive: strip any secret-looking keys before persisting config.
        if (isset($data['config'])) {
            $data['config'] = collect($data['config'])->reject(
                fn ($v, $k) => (bool) preg_match('/secret|token|key|password|signature/i', (string) $k)
            )->all();
        }

        $provider->update($data);

        return response()->json(['success' => true, 'data' => $provider->fresh()]);
    }

    // ---- Transaction events (audit over the existing payments table) --------

    public function events(Request $request): JsonResponse
    {
        $q = DB::table('payment_transaction_events');
        if ($orderId = $request->query('order_id')) {
            $q->where('order_id', $orderId);
        }
        if ($paymentId = $request->query('payment_id')) {
            $q->where('payment_id', $paymentId);
        }

        return response()->json(['success' => true, 'data' => $q->orderByDesc('id')->paginate(50)]);
    }

    // ---- Store credit (wallet) ---------------------------------------------

    public function adjustWallet(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'direction' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        if ($data['direction'] === 'credit') {
            $wallet = $this->wallets->credit($data['user_id'], (float) $data['amount'], 'adjust', null, 'admin', $data['note'] ?? 'Admin credit');

            return response()->json(['success' => true, 'data' => ['balance' => (float) $wallet->balance]]);
        }

        $res = $this->wallets->debit($data['user_id'], (float) $data['amount'], null, 'admin', $data['note'] ?? 'Admin debit');

        return response()->json(['success' => true, 'data' => $res]);
    }

    // ---- Vendor payouts -----------------------------------------------------

    public function vendorPayouts(Request $request): JsonResponse
    {
        $q = VendorPayout::query();
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        if ($vendorId = $request->query('vendor_id')) {
            $q->where('vendor_id', $vendorId);
        }

        return response()->json(['success' => true, 'data' => $q->orderByDesc('id')->paginate(30)]);
    }

    public function storeVendorPayout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'vendor_id' => ['required', 'integer'],
            'currency' => ['nullable', 'string', 'size:3'],
            'method' => ['nullable', 'string', 'max:60'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.order_id' => ['nullable', 'integer'],
            'items.*.description' => ['nullable', 'string', 'max:190'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
        ]);

        $data['created_by'] = optional($request->user())->id;

        return response()->json(['success' => true, 'data' => $this->payouts->create($data)->load('items')], 201);
    }

    public function approveVendorPayout(VendorPayout $vendorPayout): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->payouts->approve($vendorPayout)]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function markVendorPayoutPaid(VendorPayout $vendorPayout): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->payouts->markPaid($vendorPayout)]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
