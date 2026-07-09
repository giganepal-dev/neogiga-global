<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Erp\DocumentNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Session-authed (admin.web) config actions for the adaptation modules.
 * All mutations are server-side and guarded. This controller NEVER enables a
 * live gateway or writes gateway credentials — provider `config` and `is_live`
 * are left untouched; only the on/off (`is_enabled`) sandbox flag is toggled.
 */
class CommerceOpsController extends Controller
{
    // ---- Payments ----------------------------------------------------------

    public function toggleProvider(Request $request, int $provider): RedirectResponse
    {
        $row = DB::table('payment_providers')->where('id', $provider)->first();
        if (! $row) {
            return back()->with('error', 'Provider not found.');
        }

        DB::table('payment_providers')
            ->where('id', $provider)
            ->update(['is_enabled' => ! $row->is_enabled, 'updated_at' => now()]);

        return back()->with('status', "Provider {$row->code} " . ($row->is_enabled ? 'disabled' : 'enabled') . '.');
    }

    public function approvePayout(int $payout): RedirectResponse
    {
        DB::table('vendor_payouts')->where('id', $payout)->where('status', 'pending')
            ->update(['status' => 'approved', 'updated_at' => now()]);

        return back()->with('status', "Payout #{$payout} approved.");
    }

    public function markPayoutPaid(int $payout): RedirectResponse
    {
        DB::table('vendor_payouts')->where('id', $payout)->whereIn('status', ['approved', 'processing'])
            ->update(['status' => 'paid', 'updated_at' => now()]);

        return back()->with('status', "Payout #{$payout} marked paid.");
    }

    // ---- Promotions --------------------------------------------------------

    public function storeCoupon(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'unique:coupons,code'],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'min_order_total' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:0'],
            'ends_at' => ['nullable', 'date'],
        ]);

        DB::table('coupons')->insert([
            'code' => strtoupper($data['code']),
            'type' => $data['type'],
            'value' => $data['value'],
            'currency' => strtoupper($data['currency'] ?? 'USD'),
            'scope' => 'cart',
            'min_order_total' => $data['min_order_total'] ?? 0,
            'usage_limit' => $data['usage_limit'] ?? null,
            'usage_limit_per_user' => null,
            'used_count' => 0,
            'ends_at' => $data['ends_at'] ?? null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', "Coupon {$data['code']} created.");
    }

    public function toggleCoupon(int $coupon): RedirectResponse
    {
        $row = DB::table('coupons')->where('id', $coupon)->first();
        if (! $row) {
            return back()->with('error', 'Coupon not found.');
        }

        DB::table('coupons')->where('id', $coupon)
            ->update(['is_active' => ! $row->is_active, 'updated_at' => now()]);

        return back()->with('status', "Coupon {$row->code} " . ($row->is_active ? 'deactivated' : 'activated') . '.');
    }

    // ---- Affiliate ---------------------------------------------------------

    public function approveAffiliate(int $affiliate): RedirectResponse
    {
        DB::table('affiliates')->where('id', $affiliate)->where('status', 'pending')
            ->update(['status' => 'approved', 'updated_at' => now()]);

        return back()->with('status', "Affiliate #{$affiliate} approved.");
    }

    public function approveCommission(int $commission): RedirectResponse
    {
        DB::table('commission_ledger')->where('id', $commission)->where('status', 'pending')
            ->update(['status' => 'approved', 'approved_at' => now(), 'updated_at' => now()]);

        return back()->with('status', "Commission #{$commission} approved.");
    }

    // ---- Users ----------------------------------------------------------------

    public function sendPasswordReset(int $user): RedirectResponse
    {
        $email = DB::table('users')->where('id', $user)->value('email');
        if (! $email) {
            return back()->with('error', 'User not found.');
        }

        \Illuminate\Support\Facades\Password::sendResetLink(['email' => $email]);

        return back()->with('status', "Password reset link sent to user #{$user}.");
    }

    // ---- Region stock visibility ---------------------------------------------

    public function toggleStockRule(int $rule): RedirectResponse
    {
        $row = DB::table('region_stock_visibilities')->where('id', $rule)->first();
        if (! $row) {
            return back()->with('error', 'Visibility rule not found.');
        }

        DB::table('region_stock_visibilities')->where('id', $rule)
            ->update(['is_visible' => ! $row->is_visible, 'updated_at' => now()]);

        return back()->with('status', "Rule #{$rule} " . ($row->is_visible ? 'hidden' : 'visible') . '.');
    }

    // ---- Onboarding applications --------------------------------------------

    public function updateApplicationStatus(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(in_array($type, ['seller', 'distributor'], true), 404);
        $table = $type . '_applications';

        // Same whitelist as the Onboarding module's ApplicationStatusRequest.
        $data = $request->validate([
            'status' => ['required', 'in:pending,contacted,approved_for_onboarding,rejected,archived'],
            'admin_notes' => ['nullable', 'string', 'max:3000'],
        ]);

        $updated = DB::table($table)->where('id', $id)->update([
            'status' => $data['status'],
            'admin_notes' => $data['admin_notes'] ?? DB::raw('admin_notes'),
            'reviewed_at' => now(),
            'updated_at' => now(),
        ]);

        return $updated
            ? back()->with('status', ucfirst($type) . " application #{$id} → {$data['status']}.")
            : back()->with('error', 'Application not found.');
    }

    // ---- Expenses ----------------------------------------------------------

    public function storeExpense(Request $request, DocumentNumberService $docs): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:64'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'expense_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        DB::table('expenses')->insert([
            'expense_number' => $docs->next('expense', 'EXP-'),
            'category' => $data['category'],
            'amount' => $data['amount'],
            'tax_amount' => 0,
            'currency' => strtoupper($data['currency'] ?? 'USD'),
            'status' => 'recorded',
            'expense_date' => $data['expense_date'],
            'description' => $data['description'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Expense recorded.');
    }
}
