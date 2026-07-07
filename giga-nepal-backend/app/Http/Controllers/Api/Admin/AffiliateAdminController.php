<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate\Affiliate;
use App\Models\Affiliate\AffiliatePayoutRequest;
use App\Models\Affiliate\CommissionLedgerEntry;
use App\Models\Affiliate\CommissionRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Admin surface for the affiliate program (admin.token gated).
 * All commission/payout state changes are status transitions only — the
 * monetary columns (`commission_amount`, `order_total_snapshot`, `amount`)
 * are never mutated after creation.
 */
class AffiliateAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Affiliate::query()->withCount('codes');
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        return response()->json(['success' => true, 'data' => $q->orderByDesc('id')->paginate(30)]);
    }

    public function show(Affiliate $affiliate): JsonResponse
    {
        $affiliate->load('codes');

        return response()->json([
            'success' => true,
            'data' => [
                'affiliate' => $affiliate,
                'earnings' => [
                    'pending' => (float) $affiliate->commissions()->where('status', 'pending')->sum('commission_amount'),
                    'approved' => (float) $affiliate->commissions()->where('status', 'approved')->sum('commission_amount'),
                    'paid' => (float) $affiliate->commissions()->where('status', 'paid')->sum('commission_amount'),
                ],
            ],
        ]);
    }

    public function approve(Affiliate $affiliate): JsonResponse
    {
        $affiliate->update(['status' => 'approved', 'approved_at' => now()]);

        return response()->json(['success' => true, 'data' => ['status' => $affiliate->status]]);
    }

    public function suspend(Affiliate $affiliate): JsonResponse
    {
        $affiliate->update(['status' => 'suspended']);

        return response()->json(['success' => true, 'data' => ['status' => $affiliate->status]]);
    }

    public function commissions(Request $request): JsonResponse
    {
        $q = CommissionLedgerEntry::query()->with('affiliate:id,display_name');
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        if ($affiliateId = $request->query('affiliate_id')) {
            $q->where('affiliate_id', $affiliateId);
        }

        return response()->json(['success' => true, 'data' => $q->orderByDesc('id')->paginate(50)]);
    }

    /**
     * Approve a pending commission. Guarded: only allowed once the linked order
     * is actually paid (amount_due <= 0). Never approves before payment.
     */
    public function approveCommission(CommissionLedgerEntry $entry): JsonResponse
    {
        if ($entry->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Only pending commissions can be approved.'], 422);
        }

        $orderPaid = $entry->order_id
            ? DB::table('orders')->where('id', $entry->order_id)
                ->where(fn ($q) => $q->where('amount_due', '<=', 0)->orWhere('status', 'paid')->orWhere('status', 'delivered'))
                ->exists()
            : false;

        if (!$orderPaid) {
            return response()->json(['success' => false, 'message' => 'Order is not yet paid/delivered.'], 422);
        }

        $entry->update(['status' => 'approved', 'approved_at' => now()]);

        return response()->json(['success' => true, 'data' => ['status' => $entry->status]]);
    }

    public function reverseCommission(Request $request, CommissionLedgerEntry $entry): JsonResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        if (in_array($entry->status, ['paid', 'reversed'], true)) {
            return response()->json(['success' => false, 'message' => 'Cannot reverse a paid/already-reversed entry.'], 422);
        }

        $entry->update(['status' => 'reversed', 'reversed_at' => now(), 'reason' => $data['reason'] ?? 'admin reversal']);

        return response()->json(['success' => true, 'data' => ['status' => $entry->status]]);
    }

    public function payouts(Request $request): JsonResponse
    {
        $q = AffiliatePayoutRequest::query()->with('affiliate:id,display_name');
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        return response()->json(['success' => true, 'data' => $q->orderByDesc('id')->paginate(30)]);
    }

    /**
     * Mark a payout request paid AND settle its approved commissions.
     * Wrapped in a transaction; affiliate.total_paid incremented from the
     * server-side sum of the settled commission amounts (never client input).
     */
    public function markPayoutPaid(Request $request, AffiliatePayoutRequest $payout): JsonResponse
    {
        if ($payout->status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Payout already paid.'], 422);
        }

        DB::transaction(function () use ($payout) {
            $settled = CommissionLedgerEntry::where('affiliate_id', $payout->affiliate_id)
                ->where('status', 'approved')
                ->whereNull('paid_at');

            $amount = (float) $settled->sum('commission_amount');
            $settled->update(['status' => 'paid', 'paid_at' => now(), 'payout_request_id' => $payout->id]);

            $payout->update(['status' => 'paid', 'paid_at' => now()]);

            if ($affiliate = Affiliate::find($payout->affiliate_id)) {
                $affiliate->increment('total_paid', $amount);
            }
        });

        return response()->json(['success' => true, 'data' => ['status' => 'paid']]);
    }

    public function rules(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => CommissionRule::orderBy('priority')->orderByDesc('id')->get()]);
    }

    public function storeRule(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'scope' => ['required', 'in:global,affiliate,category,product,marketplace'],
            'scope_id' => ['nullable', 'integer'],
            'type' => ['required', 'in:percentage,fixed'],
            'rate' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'min_order_total' => ['nullable', 'numeric', 'min:0'],
            'max_commission' => ['nullable', 'numeric', 'min:0'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        if ($data['type'] === 'percentage' && $data['rate'] > 100) {
            return response()->json(['success' => false, 'message' => 'Percentage rate cannot exceed 100.'], 422);
        }

        $rule = CommissionRule::create($data);

        return response()->json(['success' => true, 'data' => $rule], 201);
    }
}
