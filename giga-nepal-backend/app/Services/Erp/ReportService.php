<?php

namespace App\Services\Erp;

use Illuminate\Support\Facades\DB;

/**
 * Read-only ERP reporting aggregations (procurement, quotations, expenses,
 * supplier spend). Complements the marketing analytics module — this covers
 * the back-office/finance side. No writes.
 */
class ReportService
{
    public function procurement(): array
    {
        $byStatus = DB::table('purchase_orders')
            ->select('status', DB::raw('count(*) as count'), DB::raw('coalesce(sum(grand_total),0) as value'))
            ->groupBy('status')->get();

        return [
            'orders_total' => (int) DB::table('purchase_orders')->count(),
            'value_total' => (float) DB::table('purchase_orders')->sum('grand_total'),
            'value_open' => (float) DB::table('purchase_orders')->whereIn('status', ['ordered', 'partially_received'])->sum('grand_total'),
            'by_status' => $byStatus,
        ];
    }

    public function supplierSpend(int $limit = 10): array
    {
        $rows = DB::table('purchase_orders as po')
            ->leftJoin('suppliers as s', 's.id', '=', 'po.supplier_id')
            ->select('s.id', 's.name', 's.code', DB::raw('count(po.id) as orders'), DB::raw('coalesce(sum(po.grand_total),0) as spend'))
            ->groupBy('s.id', 's.name', 's.code')
            ->orderByDesc('spend')
            ->limit($limit)
            ->get();

        return ['suppliers' => (int) DB::table('suppliers')->count(), 'top' => $rows];
    }

    public function quotations(): array
    {
        return [
            'rfq_by_status' => DB::table('rfq_requests')->select('status', DB::raw('count(*) as count'))->groupBy('status')->get(),
            'quotes_by_status' => DB::table('quotations')
                ->select('status', DB::raw('count(*) as count'), DB::raw('coalesce(sum(grand_total),0) as value'))
                ->groupBy('status')->get(),
            'accepted_value' => (float) DB::table('quotations')->where('status', 'accepted')->sum('grand_total'),
        ];
    }

    public function expenses(?string $from = null, ?string $to = null): array
    {
        $base = DB::table('expenses');
        if ($from) {
            $base->where('expense_date', '>=', $from);
        }
        if ($to) {
            $base->where('expense_date', '<=', $to);
        }

        return [
            'total' => (float) (clone $base)->sum('amount'),
            'tax_total' => (float) (clone $base)->sum('tax_amount'),
            'by_category' => (clone $base)
                ->select('category', DB::raw('count(*) as count'), DB::raw('coalesce(sum(amount),0) as amount'))
                ->groupBy('category')->orderByDesc('amount')->get(),
            'by_status' => (clone $base)
                ->select('status', DB::raw('count(*) as count'), DB::raw('coalesce(sum(amount),0) as amount'))
                ->groupBy('status')->get(),
        ];
    }
}
