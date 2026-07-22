<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosAdminController extends Controller
{
    public function index()
    {
        $registers = DB::table('pos_registers')
            ->leftJoin('warehouses', 'pos_registers.warehouse_id', '=', 'warehouses.id')
            ->select('pos_registers.*', 'warehouses.name as warehouse_name')
            ->orderBy('pos_registers.name')
            ->get();

        $activeShifts = DB::table('pos_shifts')
            ->join('pos_registers', 'pos_shifts.register_id', '=', 'pos_registers.id')
            ->leftJoin('users', 'pos_shifts.user_id', '=', 'users.id')
            ->where('pos_shifts.status', 'open')
            ->select('pos_shifts.*', 'pos_registers.name as register_name', 'users.name as cashier_name')
            ->orderByDesc('pos_shifts.started_at')
            ->get();

        $recentShifts = DB::table('pos_shifts')
            ->join('pos_registers', 'pos_shifts.register_id', '=', 'pos_registers.id')
            ->leftJoin('users', 'pos_shifts.user_id', '=', 'users.id')
            ->where('pos_shifts.status', '!=', 'open')
            ->select('pos_shifts.*', 'pos_registers.name as register_name', 'users.name as cashier_name')
            ->orderByDesc('pos_shifts.ended_at')
            ->limit(20)
            ->get();

        $warehouses = DB::table('warehouses')->orderBy('name')->get();

        return view('admin.pos.index', compact('registers', 'activeShifts', 'recentShifts', 'warehouses'));
    }

    public function storeRegister(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
        ]);

        DB::table('pos_registers')->insert([
            'name' => $validated['name'],
            'warehouse_id' => $validated['warehouse_id'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Register created.');
    }

    public function toggleRegister(int $id)
    {
        $register = DB::table('pos_registers')->find($id);
        DB::table('pos_registers')->where('id', $id)->update([
            'is_active' => ! $register->is_active,
            'updated_at' => now(),
        ]);

        return back()->with('success', $register->is_active ? 'Register deactivated.' : 'Register activated.');
    }

    public function openShift(Request $request)
    {
        $validated = $request->validate([
            'register_id' => 'required|integer|exists:pos_registers,id',
            'opening_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $activeShift = DB::table('pos_shifts')
            ->where('register_id', $validated['register_id'])
            ->where('status', 'open')
            ->first();

        if ($activeShift) {
            return back()->withErrors(['register_id' => 'This register already has an open shift.']);
        }

        DB::table('pos_shifts')->insert([
            'register_id' => $validated['register_id'],
            'user_id' => auth()->id(),
            'opening_cash' => $validated['opening_cash'],
            'status' => 'open',
            'notes' => $validated['notes'] ?? null,
            'started_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Shift opened.');
    }

    public function closeShift(Request $request)
    {
        $validated = $request->validate([
            'shift_id' => 'required|integer|exists:pos_shifts,id',
            'closing_cash' => 'required|numeric|min:0',
            'expected_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $shift = DB::table('pos_shifts')->find($validated['shift_id']);
        if ($shift->status !== 'open') {
            return back()->withErrors(['shift_id' => 'Shift is not open.']);
        }

        DB::table('pos_shifts')->where('id', $validated['shift_id'])->update([
            'closing_cash' => $validated['closing_cash'],
            'expected_cash' => $validated['expected_cash'],
            'status' => 'closed',
            'notes' => $validated['notes'] ?? $shift->notes,
            'ended_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Shift closed. Variance: $' . number_format($validated['closing_cash'] - $validated['expected_cash'], 2));
    }

    /** Summary of today's sales, refunds, and cash movements. */
    public function dailyReport()
    {
        $today = now()->toDateString();

        $sales = DB::table('pos_sales')
            ->whereDate('created_at', $today)
            ->selectRaw('count(*) as count, coalesce(sum(total_amount),0) as total')
            ->first();

        $refunds = DB::table('pos_refunds')
            ->whereDate('created_at', $today)
            ->selectRaw('count(*) as count, coalesce(sum(amount),0) as total')
            ->first();

        $shifts = DB::table('pos_shifts')
            ->whereDate('started_at', $today)
            ->selectRaw("count(*) as total_shifts, count(*) FILTER (WHERE status = 'open') as open_shifts")
            ->first();

        return view('admin.pos.report', compact('sales', 'refunds', 'shifts', 'today'));
    }

    public function registerHistory(): View
    {
        $history = DB::table('pos_register_history')
            ->join('pos_registers', 'pos_register_history.register_id', '=', 'pos_registers.id')
            ->leftJoin('users', 'pos_register_history.user_id', '=', 'users.id')
            ->select('pos_register_history.*', 'pos_registers.name as register_name', 'users.name as user_name')
            ->orderByDesc('pos_register_history.created_at')
            ->limit(200)
            ->get();

        $registers = DB::table('pos_registers')->orderBy('name')->get();

        return view('admin.pos.history', compact('history', 'registers'));
    }

    public function zReports(): View
    {
        $reports = DB::table('pos_z_reports')
            ->join('pos_registers', 'pos_z_reports.register_id', '=', 'pos_registers.id')
            ->leftJoin('users', 'pos_z_reports.closed_by', '=', 'users.id')
            ->select('pos_z_reports.*', 'pos_registers.name as register_name', 'users.name as closed_by_name')
            ->orderByDesc('pos_z_reports.report_date')
            ->limit(100)
            ->get();

        return view('admin.pos.zreports', compact('reports'));
    }

    public function rewards(): View
    {
        $systems = DB::table('pos_reward_systems')->orderBy('name')->get();
        $customerRewards = DB::table('pos_customer_rewards')
            ->join('pos_reward_systems', 'pos_customer_rewards.reward_system_id', '=', 'pos_reward_systems.id')
            ->join('users', 'pos_customer_rewards.customer_id', '=', 'users.id')
            ->select('pos_customer_rewards.*', 'pos_reward_systems.name as system_name', 'users.name as customer_name', 'users.email')
            ->orderByDesc('pos_customer_rewards.current_balance')
            ->limit(100)
            ->get();

        return view('admin.pos.rewards', compact('systems', 'customerRewards'));
    }

    public function instalments(): View
    {
        $instalments = DB::table('pos_order_instalments')
            ->join('orders', 'pos_order_instalments.order_id', '=', 'orders.id')
            ->leftJoin('users', 'orders.user_id', '=', 'users.id')
            ->select('pos_order_instalments.*', 'orders.order_number', 'users.name as customer_name')
            ->orderByDesc('pos_order_instalments.created_at')
            ->limit(100)
            ->get();

        return view('admin.pos.instalments', compact('instalments'));
    }
}
