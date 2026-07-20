<?php

namespace App\Http\Controllers\Web\POS;

use App\Http\Controllers\Controller;
use App\Services\POS\PosCustomerAccountService;
use App\Services\POS\PosService;
use App\Services\POS\PosTerminalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PosCashierController extends Controller
{
    public function show(): View
    {
        return view('pos.cashier');
    }

    public function terminals(Request $request, PosTerminalService $terminals): JsonResponse
    {
        return response()->json([
            'data' => $terminals->listForMarketplace($request->integer('marketplace_id') ?: null),
        ]);
    }

    public function openSession(Request $request, PosService $pos): JsonResponse
    {
        $data = $request->validate([
            'pos_terminal_id' => ['nullable', 'integer'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'marketplace_id' => ['nullable', 'integer'],
            'opening_cash' => ['nullable', 'numeric', 'min:0'],
        ]);
        $data['user_id'] = $request->user()?->id;

        return response()->json(['data' => $pos->openSession($data)], 201);
    }

    public function searchCustomers(Request $request, PosCustomerAccountService $customers): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'marketplace_id' => ['nullable', 'integer'],
        ]);

        return response()->json(['data' => $customers->search($data['q'] ?? null, $data['marketplace_id'] ?? null)]);
    }

    public function createSale(Request $request, PosService $pos): JsonResponse
    {
        $data = $request->validate([
            'pos_session_id' => ['required', 'integer', 'exists:pos_sessions,id'],
            'pos_customer_account_id' => ['nullable', 'integer'],
            'customer_name' => ['nullable', 'string', 'max:190'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $sale = $pos->createSale($data);
            $payment = $pos->payment($sale['id'], [
                'amount' => $sale['total_amount'],
                'payment_method' => $request->input('payment_method', 'cash'),
            ]);

            return response()->json(['data' => array_merge($sale, ['payment' => $payment])], 201);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function refund(Request $request, int $sale, PosService $pos): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'refund_method' => ['required', 'string', 'max:80'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);
        $data['idempotency_key'] = $request->header('Idempotency-Key') ?: ('web-refund-'.$sale.'-'.Str::uuid());

        try {
            return response()->json(['data' => $pos->refund($sale, $data, $request->user()?->id)]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
