<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Services\BulkImportService;
use Illuminate\Http\Request;

class BulkImportController extends Controller
{
    protected $importService;

    public function __construct(BulkImportService $importService)
    {
        $this->importService = $importService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Preview product import
     */
    public function previewProducts(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,xls,xlsx|max:10240',
            'marketplace' => 'nullable|string|max:50',
            'warehouse_id' => 'nullable|exists:vendor_warehouses,id',
        ]);

        $vendorId = auth()->id();
        $options = $request->only(['marketplace', 'warehouse_id']);

        try {
            $preview = $this->importService->previewImport(
                $request->file('file'),
                'products',
                $vendorId,
                $options
            );

            return response()->json([
                'success' => true,
                'data' => $preview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Import products from CSV/XLSX
     */
    public function importProducts(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,xls,xlsx|max:10240',
            'marketplace' => 'nullable|string|max:50',
            'warehouse_id' => 'nullable|exists:vendor_warehouses,id',
        ]);

        $vendorId = auth()->id();
        $options = $request->only(['marketplace', 'warehouse_id']);

        try {
            $results = $this->importService->processProductImport(
                $request->file('file'),
                $vendorId,
                $options
            );

            // Generate report
            $reportPath = $this->importService->generateReport($results);

            return response()->json([
                'success' => true,
                'message' => 'Product import completed.',
                'data' => [
                    'stats' => $results['stats'],
                    'report_url' => '/api/seller/imports/reports/' . basename($reportPath),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Preview offers import
     */
    public function previewOffers(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,xls,xlsx|max:10240',
            'marketplace' => 'nullable|string|max:50',
            'warehouse_id' => 'nullable|exists:vendor_warehouses,id',
        ]);

        $vendorId = auth()->id();
        $options = $request->only(['marketplace', 'warehouse_id']);

        try {
            $preview = $this->importService->previewImport(
                $request->file('file'),
                'offers',
                $vendorId,
                $options
            );

            return response()->json([
                'success' => true,
                'data' => $preview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Import offers from CSV/XLSX
     */
    public function importOffers(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,xls,xlsx|max:10240',
            'marketplace' => 'nullable|string|max:50',
            'warehouse_id' => 'nullable|exists:vendor_warehouses,id',
        ]);

        $vendorId = auth()->id();
        $options = $request->only(['marketplace', 'warehouse_id']);

        try {
            $results = $this->importService->processOffersImport(
                $request->file('file'),
                $vendorId,
                $options
            );

            $reportPath = $this->importService->generateReport($results);

            return response()->json([
                'success' => true,
                'message' => 'Offers import completed.',
                'data' => [
                    'stats' => $results['stats'],
                    'report_url' => '/api/seller/imports/reports/' . basename($reportPath),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Preview stock import
     */
    public function previewStock(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,xls,xlsx|max:10240',
            'warehouse_id' => 'required|exists:vendor_warehouses,id',
        ]);

        $vendorId = auth()->id();
        $options = ['warehouse_id' => $validated['warehouse_id']];

        try {
            $preview = $this->importService->previewImport(
                $request->file('file'),
                'stock',
                $vendorId,
                $options
            );

            return response()->json([
                'success' => true,
                'data' => $preview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Import stock from CSV/XLSX
     */
    public function importStock(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:csv,xls,xlsx|max:10240',
            'warehouse_id' => 'required|exists:vendor_warehouses,id',
        ]);

        $vendorId = auth()->id();
        $options = ['warehouse_id' => $validated['warehouse_id']];

        try {
            $results = $this->importService->processStockImport(
                $request->file('file'),
                $vendorId,
                $options
            );

            $reportPath = $this->importService->generateReport($results);

            return response()->json([
                'success' => true,
                'message' => 'Stock import completed.',
                'data' => [
                    'stats' => $results['stats'],
                    'report_url' => '/api/seller/imports/reports/' . basename($reportPath),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Download import report
     */
    public function downloadReport($filename)
    {
        $path = 'imports/reports/' . $filename;
        
        if (!\Storage::exists($path)) {
            return response()->json([
                'success' => false,
                'message' => 'Report not found.',
            ], 404);
        }

        return \Storage::download($path);
    }
}
