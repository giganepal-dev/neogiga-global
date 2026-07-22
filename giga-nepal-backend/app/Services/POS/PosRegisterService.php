<?php

namespace App\Services\Pos;

use App\Models\PosCustomerReward;
use App\Models\PosRegister;
use App\Models\PosRegisterHistory;
use App\Models\PosRewardSystem;
use App\Models\PosShift;
use App\Models\PosZReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PosRegisterService
{
    /** Record a register history entry and update balance */
    public function recordAction(
        PosRegister $register,
        string $action,
        float $amount,
        ?PosShift $shift = null,
        ?User $user = null,
        array $opts = []
    ): PosRegisterHistory {
        $currentBalance = $this->currentBalance($register);

        return PosRegisterHistory::create([
            'register_id' => $register->id,
            'shift_id' => $shift?->id,
            'user_id' => $user?->id,
            'action' => $action,
            'payment_type' => $opts['payment_type'] ?? null,
            'amount' => $amount,
            'balance_before' => $currentBalance,
            'balance_after' => $currentBalance + $amount,
            'description' => $opts['description'] ?? null,
            'reference_type' => $opts['reference_type'] ?? null,
            'reference_id' => $opts['reference_id'] ?? null,
            'metadata' => $opts['metadata'] ?? null,
        ]);
    }

    /** Open a cash register with initial float */
    public function openRegister(PosRegister $register, float $openingAmount, ?string $description = null, ?User $user = null): PosRegisterHistory
    {
        $shift = PosShift::where('register_id', $register->id)->where('status', 'open')->first();
        return $this->recordAction($register, 'open', $openingAmount, $shift, $user, ['description' => $description ?: 'Register opened']);
    }

    /** Close register and generate Z-report */
    public function closeRegister(PosRegister $register, float $closingAmount, ?string $description = null, ?User $user = null): PosZReport
    {
        $shift = PosShift::where('register_id', $register->id)->where('status', 'open')->first();
        $this->recordAction($register, 'close', -$closingAmount, $shift, $user, ['description' => $description ?: 'Register closed']);

        // Generate Z-report
        $reportData = $this->buildZReportData($register, $shift, $closingAmount, $user);
        return PosZReport::create($reportData);
    }

    /** Cash in (add float during shift) */
    public function cashIn(PosRegister $register, float $amount, ?string $desc = null, ?User $user = null): PosRegisterHistory
    {
        $shift = PosShift::where('register_id', $register->id)->where('status', 'open')->first();
        return $this->recordAction($register, 'cash_in', $amount, $shift, $user, ['description' => $desc, 'payment_type' => 'cash']);
    }

    /** Cash out (remove float) */
    public function cashOut(PosRegister $register, float $amount, ?string $desc = null, ?User $user = null): PosRegisterHistory
    {
        $shift = PosShift::where('register_id', $register->id)->where('status', 'open')->first();
        return $this->recordAction($register, 'cash_out', -$amount, $shift, $user, ['description' => $desc, 'payment_type' => 'cash']);
    }

    /** Current register balance computed from history */
    public function currentBalance(PosRegister $register): float
    {
        return (float) PosRegisterHistory::where('register_id', $register->id)->sum('amount');
    }

    /** Build Z-report data */
    private function buildZReportData(PosRegister $register, ?PosShift $shift, float $closingAmount, ?User $user): array
    {
        $sales = DB::table('pos_sales')
            ->where('pos_register_id', $register->id)
            ->whereBetween('created_at', [$shift?->started_at ?? now()->subDay(), now()])
            ->get();

        $totalSales = $sales->sum('total_amount');
        $saleCount = $sales->count();
        $cashSales = $sales->where('payment_method', 'cash')->sum('total_amount');
        $cardSales = $sales->where('payment_method', 'card')->sum('total_amount');
        $walletSales = $sales->where('payment_method', 'wallet')->sum('total_amount');

        $refunds = DB::table('pos_refunds')
            ->where('pos_register_id', $register->id)
            ->whereBetween('created_at', [$shift?->started_at ?? now()->subDay(), now()])
            ->get();

        $totalRefunds = $refunds->sum('amount');
        $refundCount = $refunds->count();

        $cashMovements = PosRegisterHistory::where('register_id', $register->id)
            ->whereBetween('created_at', [$shift?->started_at ?? now()->subDay(), now()])
            ->get();

        $cashIn = $cashMovements->where('action', 'cash_in')->sum('amount');
        $cashOut = abs($cashMovements->where('action', 'cash_out')->sum('amount'));

        $openingBalance = $shift ? (float) PosRegisterHistory::where('register_id', $register->id)->where('action', 'open')->where('shift_id', $shift->id)->sum('amount') : 0;
        $expectedBalance = $openingBalance + $cashSales + $cashIn - $cashOut - $totalRefunds;

        return [
            'register_id' => $register->id,
            'shift_id' => $shift?->id,
            'closed_by' => $user?->id,
            'report_date' => now(),
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingAmount,
            'expected_balance' => $expectedBalance,
            'cash_sales' => $cashSales,
            'card_sales' => $cardSales,
            'wallet_sales' => $walletSales,
            'total_sales' => $totalSales,
            'total_refunds' => $totalRefunds,
            'total_expenses' => 0,
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'difference' => $closingAmount - $expectedBalance,
            'sale_count' => $saleCount,
            'refund_count' => $refundCount,
            'payment_breakdown' => [
                'cash' => $cashSales, 'card' => $cardSales, 'wallet' => $walletSales,
                'refunds' => $totalRefunds, 'cash_movements_in' => $cashIn, 'cash_movements_out' => $cashOut,
            ],
        ];
    }

    // === Reward System ===

    /** Compute reward points for an order */
    public function computeReward(int $customerId, float $orderTotal): ?PosCustomerReward
    {
        $rewardSystem = PosRewardSystem::where('is_active', true)
            ->where('type', 'points')
            ->where(function ($q) use ($orderTotal) {
                $q->whereNull('min_order')->orWhere('min_order', '<=', $orderTotal);
            })
            ->first();

        if (!$rewardSystem || $rewardSystem->target <= 0) return null;

        $points = floor($orderTotal / $rewardSystem->target) * $rewardSystem->reward_value;

        if ($points <= 0) return null;

        $customerReward = PosCustomerReward::firstOrCreate(
            ['customer_id' => $customerId, 'reward_system_id' => $rewardSystem->id],
            ['points_earned' => 0, 'points_redeemed' => 0, 'current_balance' => 0]
        );

        $customerReward->points_earned += $points;
        $customerReward->current_balance += $points;
        $customerReward->last_earned_at = now();
        $customerReward->save();

        return $customerReward;
    }

    /** Redeem reward points */
    public function redeemReward(int $customerId, float $points): ?PosCustomerReward
    {
        $reward = PosCustomerReward::where('customer_id', $customerId)
            ->where('current_balance', '>=', $points)
            ->first();

        if (!$reward) return null;

        $reward->points_redeemed += $points;
        $reward->current_balance -= $points;
        $reward->save();

        return $reward;
    }
}
