<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BatchInventoryController extends Controller
{
    public function index(Request $request): View
    {
        $query = DB::table('inventory_batches')
            ->leftJoin('products', 'inventory_batches.product_id', '=', 'products.id')
            ->leftJoin('warehouses', 'inventory_batches.warehouse_id', '=', 'warehouses.id')
            ->select('inventory_batches.*', 'products.name as product_name', 'products.mpn', 'warehouses.name as warehouse_name');

        if ($status = $request->input('status')) {
            $query->where('inventory_batches.status', $status);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'ilike', "%{$search}%")
                    ->orWhere('products.name', 'ilike', "%{$search}%")
                    ->orWhere('products.mpn', 'ilike', "%{$search}%");
            });
        }

        $batches = $query->orderByDesc('inventory_batches.created_at')->paginate(20);

        $stats = [
            'total' => DB::table('inventory_batches')->count(),
            'active' => DB::table('inventory_batches')->where('status', 'active')->count(),
            'expiring_soon' => DB::table('inventory_batches')->where('status', 'active')->where('expiry_date', '<=', now()->addDays(30))->where('expiry_date', '>=', now())->count(),
            'expired' => DB::table('inventory_batches')->where('expiry_date', '<', now())->where('status', '!=', 'depleted')->count(),
        ];

        return view('admin.inventory.batches', compact('batches', 'stats'));
    }

    public function show(int $batch): View
    {
        $record = DB::table('inventory_batches')
            ->leftJoin('products', 'inventory_batches.product_id', '=', 'products.id')
            ->leftJoin('product_variants', 'inventory_batches.product_variant_id', '=', 'product_variants.id')
            ->leftJoin('warehouses', 'inventory_batches.warehouse_id', '=', 'warehouses.id')
            ->leftJoin('suppliers', 'inventory_batches.supplier_id', '=', 'suppliers.id')
            ->leftJoin('users', 'inventory_batches.received_by', '=', 'users.id')
            ->select(
                'inventory_batches.*',
                'products.name as product_name',
                'products.mpn',
                'products.sku',
                'product_variants.name as variant_name',
                'warehouses.name as warehouse_name',
                'suppliers.name as supplier_name',
                'users.name as received_by_name'
            )
            ->where('inventory_batches.id', $batch)
            ->first();

        abort_unless($record, 404);

        $serials = DB::table('serial_numbers')
            ->where('inventory_batch_id', $batch)
            ->orderBy('serial_number')
            ->get();

        return view('admin.inventory.batch-detail', compact('record', 'serials'));
    }

    public function serials(Request $request): View
    {
        $query = DB::table('serial_numbers')
            ->leftJoin('products', 'serial_numbers.product_id', '=', 'products.id')
            ->leftJoin('inventory_batches', 'serial_numbers.inventory_batch_id', '=', 'inventory_batches.id')
            ->leftJoin('warehouses', 'serial_numbers.warehouse_id', '=', 'warehouses.id')
            ->select(
                'serial_numbers.*',
                'products.name as product_name',
                'products.mpn',
                'inventory_batches.batch_number',
                'warehouses.name as warehouse_name'
            );

        if ($status = $request->input('status')) {
            $query->where('serial_numbers.status', $status);
        }
        if ($warranty = $request->input('warranty')) {
            $query->where('serial_numbers.warranty_status', $warranty);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('serial_numbers.serial_number', 'ilike', "%{$search}%")
                    ->orWhere('serial_numbers.manufacturer_serial', 'ilike', "%{$search}%")
                    ->orWhere('products.name', 'ilike', "%{$search}%");
            });
        }

        $serials = $query->orderByDesc('serial_numbers.created_at')->paginate(20);

        $stats = [
            'total' => DB::table('serial_numbers')->count(),
            'available' => DB::table('serial_numbers')->where('status', 'available')->count(),
            'sold' => DB::table('serial_numbers')->where('status', 'sold')->count(),
            'under_warranty' => DB::table('serial_numbers')->where('warranty_status', 'active')->where('warranty_end_date', '>=', now())->count(),
        ];

        return view('admin.inventory.serials', compact('serials', 'stats'));
    }

    public function serialShow(int $serial): View
    {
        $record = DB::table('serial_numbers')
            ->leftJoin('products', 'serial_numbers.product_id', '=', 'products.id')
            ->leftJoin('product_variants', 'serial_numbers.product_variant_id', '=', 'product_variants.id')
            ->leftJoin('inventory_batches', 'serial_numbers.inventory_batch_id', '=', 'inventory_batches.id')
            ->leftJoin('warehouses', 'serial_numbers.warehouse_id', '=', 'warehouses.id')
            ->select(
                'serial_numbers.*',
                'products.name as product_name',
                'products.mpn',
                'products.sku',
                'product_variants.name as variant_name',
                'inventory_batches.batch_number',
                'warehouses.name as warehouse_name'
            )
            ->where('serial_numbers.id', $serial)
            ->first();

        abort_unless($record, 404);

        $serviceHistory = json_decode($record->service_history ?? '[]', true) ?? [];

        return view('admin.inventory.serial-detail', compact('record', 'serviceHistory'));
    }
}
