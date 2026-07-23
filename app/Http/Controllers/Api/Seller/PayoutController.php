<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Services\PayoutService;
use App\Models\VendorPayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayoutController extends Controller
{
    protected $payoutService;

    public function __construct(PayoutService $payoutService)
    {
        $this->payoutService = $payoutService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Get payout history
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'from_date', 'to_date']);
        $payouts = $this->payoutService->getPayoutHistory(Auth::id(), $filters);

        return response()->json([
            'success' => true,
            'data' => $payouts,
        ]);
    }

    /**
     * Get single payout
     */
    public function show(VendorPayout $payout)
    {
        if ($payout->vendor_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $payout->load(['statements']),
        ]);
    }

    /**
     * Request new payout
     */
    public function requestPayout(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:1',
        ]);

        try {
            $payout = $this->payoutService->requestPayout(Auth::id(), $validated['amount'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Payout requested successfully.',
                'data' => $payout,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get earnings summary
     */
    public function earningsSummary()
    {
        $summary = $this->payoutService->getEarningsSummary(Auth::id());

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get payable balance
     */
    public function balance()
    {
        $balance = $this->payoutService->getPayableBalance(Auth::id());

        return response()->json([
            'success' => true,
            'data' => $balance,
        ]);
    }

    /**
     * Generate statement for period
     */
    public function generateStatement(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        try {
            $statement = $this->payoutService->createStatement(
                Auth::id(),
                $validated['start_date'],
                $validated['end_date']
            );

            return response()->json([
                'success' => true,
                'message' => 'Statement generated successfully.',
                'data' => $statement,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download statement as PDF/CSV
     */
    public function downloadStatement(VendorPayout $payout)
    {
        if ($payout->vendor_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // In real implementation, generate PDF or CSV
        return response()->json([
            'success' => true,
            'download_url' => '/api/seller/payouts/' . $payout->id . '/download.pdf',
        ]);
    }
}
