<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Erp\PurchaseOrder;
use App\Models\Erp\Supplier;
use App\Services\Erp\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * ERP procurement admin (admin.token gated by routes). All PO totals are
 * computed server-side in PurchaseOrderService.
 */
class ProcurementAdminController extends Controller
{
    public function __construct(private readonly PurchaseOrderService $purchaseOrders)
    {
    }

    // ---- Suppliers ----------------------------------------------------------

    public function suppliers(Request $request): JsonResponse
    {
        $q = Supplier::query();
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        if ($term = $request->query('q')) {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('code', 'like', "%{$term}%"));
        }

        return response()->json(['success' => true, 'data' => $q->orderBy('name')->paginate(30)]);
    }

    public function storeSupplier(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:40', 'unique:suppliers,code'],
            'name' => ['required', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'country_id' => ['nullable', 'integer'],
            'currency' => ['nullable', 'string', 'size:3'],
            'tax_number' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'array'],
            'payment_terms' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['code'] = $data['code'] ?? 'SUP-' . strtoupper(Str::random(6));
        $data['currency'] = $data['currency'] ?? 'USD';
        $data['status'] = $data['status'] ?? 'active';

        return response()->json(['success' => true, 'data' => Supplier::create($data)], 201);
    }

    public function updateSupplier(Request $request, Supplier $supplier): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'currency' => ['nullable', 'string', 'size:3'],
            'payment_terms' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'array'],
        ]);

        $supplier->update($data);

        return response()->json(['success' => true, 'data' => $supplier->fresh()]);
    }

    // ---- Purchase orders ----------------------------------------------------

    public function purchaseOrders(Request $request): JsonResponse
    {
        $q = PurchaseOrder::query()->with('supplier:id,name,code');
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        if ($supplierId = $request->query('supplier_id')) {
            $q->where('supplier_id', $supplierId);
        }

        return response()->json(['success' => true, 'data' => $q->orderByDesc('id')->paginate(30)]);
    }

    public function showPurchaseOrder(PurchaseOrder $order): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $order->load(['items', 'supplier'])]);
    }

    public function storePurchaseOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'currency' => ['nullable', 'string', 'size:3'],
            'warehouse_id' => ['nullable', 'integer'],
            'marketplace_id' => ['nullable', 'integer'],
            'expected_at' => ['nullable', 'date'],
            'shipping_total' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:190'],
            'items.*.sku' => ['nullable', 'string', 'max:80'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.product_variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['created_by'] = optional($request->user())->id;
        $po = $this->purchaseOrders->create($data);

        return response()->json(['success' => true, 'data' => $po->load(['items', 'supplier'])], 201);
    }

    public function placePurchaseOrder(PurchaseOrder $order): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->purchaseOrders->place($order)]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function receivePurchaseOrder(Request $request, PurchaseOrder $order): JsonResponse
    {
        $data = $request->validate([
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            return response()->json(['success' => true, 'data' => $this->purchaseOrders->receive($order, $data['lines'])]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function cancelPurchaseOrder(PurchaseOrder $order): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => $this->purchaseOrders->cancel($order)]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
