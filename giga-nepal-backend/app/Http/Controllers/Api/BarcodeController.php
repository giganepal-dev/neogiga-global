<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\ProductBarcode;
use App\Models\Marketplace\ProductBarcodeScanLog;
use App\Models\Marketplace\BarcodeLabelTemplate;
use App\Services\Labels\BarcodeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Barcode Management API Controller
 * 
 * Handles all barcode-related operations including:
 * - Barcode lookup by scan
 * - Barcode creation and management
 * - Label generation
 * - Scan logging
 * - Bulk import
 */
class BarcodeController extends Controller
{
    public function __construct(
        protected BarcodeService $barcodeService
    ) {}

    /**
     * Lookup product by barcode scan
     * 
     * POST /api/barcode/scan
     */
    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'barcode_value' => 'required|string|max:100',
            'pos_terminal_id' => 'nullable|integer|exists:pos_terminals,id',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'marketplace_id' => 'nullable|integer|exists:marketplaces,id',
            'context' => 'nullable|array',
        ]);

        $barcodeValue = $request->input('barcode_value');
        $userId = Auth::id();
        $posTerminalId = $request->input('pos_terminal_id');
        $warehouseId = $request->input('warehouse_id');
        $marketplaceId = $request->input('marketplace_id');
        $context = $request->input('context', []);

        // Find barcode
        $result = $this->barcodeService->findByBarcode($barcodeValue);

        if (!$result) {
            // Log failed scan
            $this->barcodeService->logScan(
                barcodeValue: $barcodeValue,
                wasSuccessful: false,
                userId: $userId,
                posTerminalId: $posTerminalId,
                warehouseId: $warehouseId,
                marketplaceId: $marketplaceId,
                source: ProductBarcodeScanLog::SOURCE_SCANNER,
                failureReason: 'Barcode not found',
                context: $context
            );

            return response()->json([
                'success' => false,
                'message' => 'Barcode not found',
                'barcode_value' => $barcodeValue,
            ], 404);
        }

        // Log successful scan
        $this->barcodeService->logScan(
            barcodeValue: $barcodeValue,
            wasSuccessful: true,
            barcodeId: $result['barcode']->id,
            userId: $userId,
            posTerminalId: $posTerminalId,
            warehouseId: $warehouseId,
            marketplaceId: $marketplaceId,
            source: ProductBarcodeScanLog::SOURCE_SCANNER,
            context: $context
        );

        return response()->json([
            'success' => true,
            'barcode' => [
                'id' => $result['barcode']->id,
                'value' => $result['barcode']->barcode_value,
                'type' => $result['barcode']->barcode_type,
                'is_primary' => $result['barcode']->is_primary,
            ],
            'product' => [
                'id' => $result['product']->id,
                'name' => $result['product']->name,
                'sku' => $result['product']->sku,
                'mpn' => $result['product']->mpn,
                'brand' => $result['product']->brand?->name,
                'image' => $result['product']->images?->first()?->url,
            ],
            'variant' => $result['variant'] ? [
                'id' => $result['variant']->id,
                'name' => $result['variant']->name,
                'sku' => $result['variant']->sku,
            ] : null,
            'warehouse' => $result['warehouse'] ? [
                'id' => $result['warehouse']->id,
                'name' => $result['warehouse']->warehouse?->name,
                'stock' => $result['warehouse']->quantity_available,
            ] : null,
            'response_time_ms' => $result['response_time_ms'],
        ]);
    }

    /**
     * Create a new barcode for a product
     * 
     * POST /api/barcode
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'barcode_value' => 'required|string|max:100',
            'barcode_type' => 'string|in:code128,code39,ean13,ean8,upca,upce,qr,datamatrix',
            'source' => 'string|in:manufacturer,internal,supplier,custom',
            'variant_id' => 'nullable|integer|exists:product_variants,id',
            'warehouse_id' => 'nullable|integer|exists:product_warehouses,id',
            'is_primary' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        try {
            $barcode = $this->barcodeService->createBarcode(
                productId: $request->input('product_id'),
                barcodeValue: $request->input('barcode_value'),
                type: $request->input('barcode_type', ProductBarcode::TYPE_CODE128),
                source: $request->input('source', ProductBarcode::SOURCE_INTERNAL),
                variantId: $request->input('variant_id'),
                warehouseId: $request->input('warehouse_id'),
                isPrimary: $request->input('is_primary', false),
                metadata: $request->input('metadata')
            );

            return response()->json([
                'success' => true,
                'message' => 'Barcode created successfully',
                'barcode' => $barcode,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get barcode details
     * 
     * GET /api/barcode/{id}
     */
    public function show(int $id): JsonResponse
    {
        $barcode = ProductBarcode::with(['product', 'variant', 'productWarehouse', 'verifiedBy', 'createdBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'barcode' => $barcode,
        ]);
    }

    /**
     * Generate barcode SVG
     * 
     * GET /api/barcode/{id}/generate
     */
    public function generate(int $id): JsonResponse
    {
        $barcode = ProductBarcode::findOrFail($id);
        
        $svg = $barcode->generateSvg();

        return response()->json([
            'success' => true,
            'barcode_id' => $id,
            'barcode_value' => $barcode->barcode_value,
            'type' => $barcode->barcode_type,
            'svg' => $svg,
        ]);
    }

    /**
     * Generate label for printing
     * 
     * POST /api/barcode/label/generate
     */
    public function generateLabel(Request $request): JsonResponse
    {
        $request->validate([
            'barcode_id' => 'required|integer|exists:product_barcodes,id',
            'template_id' => 'nullable|integer|exists:barcode_label_templates,id',
            'show_price' => 'boolean',
            'show_currency' => 'boolean',
        ]);

        $barcode = ProductBarcode::with(['product', 'variant'])->findOrFail($request->input('barcode_id'));
        
        $template = BarcodeLabelTemplate::find($request->input('template_id'));
        
        $html = $this->barcodeService->productLabel(
            sku: $barcode->product->sku ?? $barcode->barcode_value,
            name: $barcode->product->name,
            mpn: $barcode->product->mpn ?? '',
            size: $template?->width_mm ? $template->width_mm * 10 : 300
        );

        return response()->json([
            'success' => true,
            'html' => $html,
            'template' => $template?->only(['name', 'width_mm', 'height_mm']),
        ]);
    }

    /**
     * Bulk generate labels
     * 
     * POST /api/barcode/labels/bulk
     */
    public function bulkLabels(Request $request): JsonResponse
    {
        $request->validate([
            'barcode_ids' => 'required|array|min:1',
            'barcode_ids.*' => 'integer|exists:product_barcodes,id',
            'template_id' => 'nullable|integer|exists:barcode_label_templates,id',
        ]);

        $barcodes = ProductBarcode::with(['product', 'variant'])
            ->whereIn('id', $request->input('barcode_ids'))
            ->get();

        $products = $barcodes->map(fn($b) => [
            'sku' => $b->product->sku ?? $b->barcode_value,
            'name' => $b->product->name,
            'mpn' => $b->product->mpn ?? '',
        ])->toArray();

        $template = BarcodeLabelTemplate::find($request->input('template_id'));
        
        $html = $this->barcodeService->bulkLabels(
            products: $products,
            labelWidth: $template?->width_mm ? $template->width_mm * 10 : 250
        );

        return response()->json([
            'success' => true,
            'count' => count($barcodes),
            'html' => $html,
        ]);
    }

    /**
     * Import barcodes from CSV/array
     * 
     * POST /api/barcode/import
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'barcodes' => 'required|array|min:1',
            'barcodes.*.product_id' => 'required|integer|exists:products,id',
            'barcodes.*.barcode_value' => 'required|string|max:100',
            'barcodes.*.barcode_type' => 'string|in:code128,code39,ean13,ean8,upca,upce,qr,datamatrix',
            'barcodes.*.source' => 'string|in:manufacturer,internal,supplier,custom',
        ]);

        $results = $this->barcodeService->importBarcodes(
            barcodes: $request->input('barcodes'),
            triggeredBy: Auth::id()
        );

        $statusCode = $results['failed'] > 0 ? 207 : 200;

        return response()->json([
            'success' => $results['failed'] === 0,
            'results' => $results,
        ], $statusCode);
    }

    /**
     * Get scan logs
     * 
     * GET /api/barcode/scan-logs
     */
    public function scanLogs(Request $request): JsonResponse
    {
        $request->validate([
            'barcode_value' => 'nullable|string|max:100',
            'user_id' => 'nullable|integer|exists:users,id',
            'marketplace_id' => 'nullable|integer|exists:marketplaces,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'successful_only' => 'boolean',
        ]);

        $query = ProductBarcodeScanLog::with(['barcode', 'user', 'marketplace']);

        if ($request->has('barcode_value')) {
            $query->where('barcode_value', $request->input('barcode_value'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('marketplace_id')) {
            $query->where('marketplace_id', $request->input('marketplace_id'));
        }

        if ($request->has('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to'));
        }

        if ($request->boolean('successful_only')) {
            $query->where('was_successful', true);
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 50));

        return response()->json([
            'success' => true,
            'logs' => $logs,
        ]);
    }

    /**
     * Deactivate a barcode
     * 
     * DELETE /api/barcode/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $barcode = ProductBarcode::findOrFail($id);
        
        $barcode->update(['is_active' => false]);

        // Clear primary barcode reference if this was primary
        if ($barcode->is_primary && !$barcode->product_variant_id && !$barcode->product_warehouse_id) {
            DB::table('products')
                ->where('id', $barcode->product_id)
                ->update(['barcode_primary' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Barcode deactivated successfully',
        ]);
    }
}
