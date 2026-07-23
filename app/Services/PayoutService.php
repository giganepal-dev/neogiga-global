<?php

namespace App\Services;

use App\Models\VendorPayout;
use App\Models\VendorOrder;
use App\Models\VendorPayoutStatement;
use App\Models\SellerOffer;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    /**
     * Calculate earnings for a specific order
     */
    public function calculateOrderEarnings(VendorOrder $order, int $vendorId): array
    {
        $sellerItems = $order->items()->whereHas('offer', function ($q) use ($vendorId) {
            $q->where('seller_id', $vendorId);
        })->get();

        if ($sellerItems->isEmpty()) {
            return [
                'gross_sale' => 0,
                'marketplace_commission' => 0,
                'tax_deduction' => 0,
                'refund_deduction' => 0,
                'shipping_deduction' => 0,
                'adjustments' => 0,
                'net_earnings' => 0,
            ];
        }

        $grossSale = 0;
        $commission = 0;
        $taxDeduction = 0;
        $shippingDeduction = 0;

        foreach ($sellerItems as $item) {
            $itemTotal = $item->unit_price * $item->quantity;
            $grossSale += $itemTotal;

            // Get commission rate from offer or marketplace default
            $commissionRate = $item->offer->commission_rate ?? 0.10; // Default 10%
            $commission += $itemTotal * $commissionRate;

            // Tax calculation (simplified - would vary by region/product)
            $taxRate = $item->offer->tax_rate ?? 0;
            $taxDeduction += $itemTotal * $taxRate;

            // Shipping deduction if seller pays
            if ($order->shipping_terms === 'seller_pays') {
                $shippingDeduction += ($order->shipping_cost ?? 0) * ($item->quantity / $order->items->sum('quantity'));
            }
        }

        $netEarnings = $grossSale - $commission - $taxDeduction - $shippingDeduction;

        return [
            'gross_sale' => round($grossSale, 2),
            'marketplace_commission' => round($commission, 2),
            'tax_deduction' => round($taxDeduction, 2),
            'refund_deduction' => 0,
            'shipping_deduction' => round($shippingDeduction, 2),
            'adjustments' => 0,
            'net_earnings' => round($netEarnings, 2),
            'currency' => $order->currency,
        ];
    }

    /**
     * Create payout statement for a period
     */
    public function createStatement(int $vendorId, string $startDate, string $endDate): VendorPayoutStatement
    {
        return DB::transaction(function () use ($vendorId, $startDate, $endDate) {
            // Get all delivered orders in period
            $orders = VendorOrder::whereHas('items.offer', function ($q) use ($vendorId) {
                $q->where('seller_id', $vendorId);
            })
            ->where('status', 'delivered')
            ->whereBetween('delivered_at', [$startDate, $endDate])
            ->get();

            $totalGross = 0;
            $totalCommission = 0;
            $totalTax = 0;
            $totalShipping = 0;
            $totalRefunds = 0;
            $totalAdjustments = 0;

            foreach ($orders as $order) {
                $earnings = $this->calculateOrderEarnings($order, $vendorId);
                $totalGross += $earnings['gross_sale'];
                $totalCommission += $earnings['marketplace_commission'];
                $totalTax += $earnings['tax_deduction'];
                $totalShipping += $earnings['shipping_deduction'];
            }

            // Get refunds in period
            $refunds = \App\Models\ReturnRequest::whereHas('orderItem.offer', function ($q) use ($vendorId) {
                $q->where('seller_id', $vendorId);
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'approved')
            ->sum('refund_amount');

            $totalRefunds = $refunds;

            $netEarnings = $totalGross - $totalCommission - $totalTax - $totalShipping - $totalRefunds - $totalAdjustments;

            $statement = VendorPayoutStatement::create([
                'vendor_id' => $vendorId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'gross_sales' => round($totalGross, 2),
                'total_commissions' => round($totalCommission, 2),
                'total_tax' => round($totalTax, 2),
                'total_refunds' => round($totalRefunds, 2),
                'total_shipping' => round($totalShipping, 2),
                'adjustments' => round($totalAdjustments, 2),
                'net_earnings' => round($netEarnings, 2),
                'status' => 'pending_review',
                'order_count' => $orders->count(),
            ]);

            return $statement;
        });
    }

    /**
     * Get payable balance for seller
     */
    public function getPayableBalance(int $vendorId): array
    {
        // Get all delivered orders not yet paid out
        $unpaidOrders = VendorOrder::whereHas('items.offer', function ($q) use ($vendorId) {
            $q->where('seller_id', $vendorId);
        })
        ->where('status', 'delivered')
        ->whereDoesntHave('payouts', function ($q) {
            $q->where('status', 'paid');
        })
        ->get();

        $payableBalance = 0;
        $holdBalance = 0;

        foreach ($unpaidOrders as $order) {
            $earnings = $this->calculateOrderEarnings($order, $vendorId);
            
            // Check if order is within hold period (e.g., 7 days after delivery for returns)
            $holdPeriodDays = config('neogiga.payout_hold_days', 7);
            if ($order->delivered_at && $order->delivered_at->diffInDays(now()) < $holdPeriodDays) {
                $holdBalance += $earnings['net_earnings'];
            } else {
                $payableBalance += $earnings['net_earnings'];
            }
        }

        return [
            'payable_balance' => round($payableBalance, 2),
            'hold_balance' => round($holdBalance, 2),
            'total_pending' => round($payableBalance + $holdBalance, 2),
        ];
    }

    /**
     * Create payout request
     */
    public function requestPayout(int $vendorId, ?float $amount = null): VendorPayout
    {
        return DB::transaction(function () use ($vendorId, $amount) {
            $balance = $this->getPayableBalance($vendorId);

            if ($balance['payable_balance'] <= 0) {
                throw new \Exception('No payable balance available.');
            }

            $payoutAmount = $amount ?? $balance['payable_balance'];

            if ($payoutAmount > $balance['payable_balance']) {
                throw new \Exception('Requested amount exceeds payable balance.');
            }

            $vendor = \App\Models\Vendor::findOrFail($vendorId);

            if (!$vendor->bank_account_verified) {
                throw new \Exception('Bank account must be verified before requesting payout.');
            }

            $payout = VendorPayout::create([
                'vendor_id' => $vendorId,
                'amount' => $payoutAmount,
                'currency' => $vendor->default_currency ?? 'USD',
                'status' => 'pending',
                'requested_at' => now(),
                'payment_method' => 'bank_transfer',
                'bank_account_id' => $vendor->bank_account_id,
            ]);

            event(new \App\Events\PayoutRequested($payout));

            return $payout;
        });
    }

    /**
     * Approve payout (Admin only)
     */
    public function approvePayout(VendorPayout $payout, int $adminId): VendorPayout
    {
        return DB::transaction(function () use ($payout, $adminId) {
            if ($payout->status !== 'pending') {
                throw new \Exception('Only pending payouts can be approved.');
            }

            $payout->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $adminId,
            ]);

            event(new \App\Events\PayoutApproved($payout));

            return $payout;
        });
    }

    /**
     * Process payout (mark as processing)
     */
    public function processPayout(VendorPayout $payout): VendorPayout
    {
        return DB::transaction(function () use ($payout) {
            if ($payout->status !== 'approved') {
                throw new \Exception('Only approved payouts can be processed.');
            }

            $payout->update([
                'status' => 'processing',
                'processing_started_at' => now(),
            ]);

            // In real implementation, this would trigger payment gateway API call
            event(new \App\Events\PayoutProcessing($payout));

            return $payout;
        });
    }

    /**
     * Mark payout as paid
     */
    public function markAsPaid(VendorPayout $payout, string $transactionId): VendorPayout
    {
        return DB::transaction(function () use ($payout, $transactionId) {
            if ($payout->status !== 'processing') {
                throw new \Exception('Only processing payouts can be marked as paid.');
            }

            $payout->update([
                'status' => 'paid',
                'paid_at' => now(),
                'transaction_id' => $transactionId,
            ]);

            event(new \App\Events\PayoutPaid($payout));

            return $payout;
        });
    }

    /**
     * Fail payout
     */
    public function failPayout(VendorPayout $payout, string $reason): VendorPayout
    {
        return DB::transaction(function () use ($payout, $reason) {
            $payout->update([
                'status' => 'failed',
                'failure_reason' => $reason,
                'failed_at' => now(),
            ]);

            event(new \App\Events\PayoutFailed($payout));

            return $payout;
        });
    }

    /**
     * Get payout history for seller
     */
    public function getPayoutHistory(int $vendorId, array $filters = [])
    {
        $query = VendorPayout::where('vendor_id', $vendorId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    /**
     * Get earnings summary for dashboard
     */
    public function getEarningsSummary(int $vendorId): array
    {
        $today = now();
        
        // This month
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();
        
        // Last month
        $lastMonthStart = $today->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $today->copy()->subMonth()->endOfMonth();

        $thisMonthOrders = VendorOrder::whereHas('items.offer', function ($q) use ($vendorId) {
            $q->where('seller_id', $vendorId);
        })
        ->where('status', 'delivered')
        ->whereBetween('delivered_at', [$monthStart, $monthEnd])
        ->get();

        $lastMonthOrders = VendorOrder::whereHas('items.offer', function ($q) use ($vendorId) {
            $q->where('seller_id', $vendorId);
        })
        ->where('status', 'delivered')
        ->whereBetween('delivered_at', [$lastMonthStart, $lastMonthEnd])
        ->get();

        $thisMonthEarnings = 0;
        foreach ($thisMonthOrders as $order) {
            $earnings = $this->calculateOrderEarnings($order, $vendorId);
            $thisMonthEarnings += $earnings['net_earnings'];
        }

        $lastMonthEarnings = 0;
        foreach ($lastMonthOrders as $order) {
            $earnings = $this->calculateOrderEarnings($order, $vendorId);
            $lastMonthEarnings += $earnings['net_earnings'];
        }

        $growth = $lastMonthEarnings > 0 
            ? (($thisMonthEarnings - $lastMonthEarnings) / $lastMonthEarnings) * 100 
            : 0;

        $balance = $this->getPayableBalance($vendorId);

        return [
            'this_month_earnings' => round($thisMonthEarnings, 2),
            'last_month_earnings' => round($lastMonthEarnings, 2),
            'growth_percentage' => round($growth, 2),
            'payable_balance' => $balance['payable_balance'],
            'hold_balance' => $balance['hold_balance'],
            'pending_payouts' => VendorPayout::where('vendor_id', $vendorId)
                ->whereIn('status', ['pending', 'approved', 'processing'])
                ->sum('amount'),
        ];
    }
}
