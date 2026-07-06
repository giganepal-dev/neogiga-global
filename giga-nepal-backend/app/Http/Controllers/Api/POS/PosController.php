<?php

namespace App\Http\Controllers\Api\POS;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\POS\PosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    use ApiResponses;

    public function openSession(Request $request, PosService $pos): JsonResponse
    {
        $data = $request->validate([
            'pos_terminal_id' => ['nullable', 'integer'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'marketplace_id' => ['nullable', 'integer'],
            'vendor_id' => ['nullable', 'integer'],
            'opening_cash' => ['nullable', 'numeric', 'min:0'],
            'terminal_name' => ['nullable', 'string', 'max:190'],
            'location' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['user_id'] = $request->user()?->id;

        return $this->success($pos->openSession($data), 201);
    }

    public function closeSession(Request $request): JsonResponse
    {
        $data = $request->validate([
            'pos_session_id' => ['required', 'integer', 'exists:pos_sessions,id'],
            'closing_cash' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        return $this->success(app(PosService::class)->closeSession((int) $data['pos_session_id'], $data));
    }

    public function searchProducts(Request $request, PosService $pos): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return $this->success($pos->searchProducts($data));
    }

    public function createSale(Request $request, PosService $pos): JsonResponse
    {
        $data = $request->validate([
            'pos_session_id' => ['required', 'integer', 'exists:pos_sessions,id'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:190'],
            'customer_email' => ['nullable', 'email', 'max:190'],
            'customer_phone' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.product_variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            return $this->success($pos->createSale($data), 201);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function showSale(int $sale, PosService $pos): JsonResponse
    {
        $row = $pos->sale($sale);

        return $row ? $this->success($row) : $this->error('Sale not found.', 404);
    }

    public function processPayment(Request $request, int $sale, PosService $pos): JsonResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'payment_method' => ['nullable', 'string', 'max:80'],
            'payment_reference' => ['nullable', 'string', 'max:190'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            return $this->success($pos->payment($sale, $data), 201);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function processRefund(int $sale): JsonResponse
    {
        return $this->error('POS refunds require the ERP/payment phase and are not enabled yet.', 501);
    }
}
