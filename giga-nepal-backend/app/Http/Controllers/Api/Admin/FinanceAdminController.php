<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Erp\Expense;
use App\Services\Erp\DocumentNumberService;
use App\Services\Erp\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ERP finance admin (admin.token gated): expense tracking + back-office reports.
 * Reports are read-only aggregations.
 */
class FinanceAdminController extends Controller
{
    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly ReportService $reports,
    ) {
    }

    // ---- Expenses -----------------------------------------------------------

    public function expenses(Request $request): JsonResponse
    {
        $q = Expense::query();
        if ($category = $request->query('category')) {
            $q->where('category', $category);
        }
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        return response()->json(['success' => true, 'data' => $q->orderByDesc('expense_date')->orderByDesc('id')->paginate(30)]);
    }

    public function storeExpense(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:60'],
            'supplier_id' => ['nullable', 'integer'],
            'marketplace_id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', 'in:recorded,approved,paid'],
            'expense_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $data['expense_number'] = $this->numbers->next('EXP', 'EXP-');
        $data['currency'] = $data['currency'] ?? 'USD';
        $data['status'] = $data['status'] ?? 'recorded';
        $data['tax_amount'] = $data['tax_amount'] ?? 0;
        $data['created_by'] = optional($request->user())->id;

        return response()->json(['success' => true, 'data' => Expense::create($data)], 201);
    }

    public function updateExpense(Request $request, Expense $expense): JsonResponse
    {
        $data = $request->validate([
            'status' => ['sometimes', 'in:recorded,approved,paid'],
            'category' => ['sometimes', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $expense->update($data);

        return response()->json(['success' => true, 'data' => $expense->fresh()]);
    }

    // ---- Reports (read-only) ------------------------------------------------

    public function reportProcurement(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->reports->procurement()]);
    }

    public function reportSupplierSpend(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->reports->supplierSpend((int) $request->query('limit', 10))]);
    }

    public function reportQuotations(): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->reports->quotations()]);
    }

    public function reportExpenses(Request $request): JsonResponse
    {
        $v = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        return response()->json(['success' => true, 'data' => $this->reports->expenses($v['from'] ?? null, $v['to'] ?? null)]);
    }
}
