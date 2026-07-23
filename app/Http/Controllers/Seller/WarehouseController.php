<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\VendorWarehouse;
use App\Models\SellerShipment;
use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:vendor');
    }

    public function index()
    {
        $vendor = Auth::guard('vendor')->user();
        
        $warehouses = VendorWarehouse::where('vendor_id', $vendor->id)
            ->withCount(['offers', 'inventoryMovements'])
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => VendorWarehouse::where('vendor_id', $vendor->id)->count(),
            'approved' => VendorWarehouse::where('vendor_id', $vendor->id)->where('status', 'approved')->count(),
            'pending' => VendorWarehouse::where('vendor_id', $vendor->id)->where('status', 'pending_verification')->count(),
            'rejected' => VendorWarehouse::where('vendor_id', $vendor->id)->where('status', 'rejected')->count(),
        ];

        return view('seller.warehouses.index', compact('warehouses', 'stats'));
    }

    public function create()
    {
        return view('seller.warehouses.create');
    }

    public function store(Request $request)
    {
        $vendor = Auth::guard('vendor')->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|size:2',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:50',
            'operating_hours' => 'nullable|string|max:255',
            'dispatch_cutoff_time' => 'nullable|date_format:H:i',
            'is_primary' => 'boolean',
            'documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        DB::beginTransaction();
        try {
            // If primary, unset other primaries
            if ($validated['is_primary'] ?? false) {
                VendorWarehouse::where('vendor_id', $vendor->id)
                    ->update(['is_primary' => false]);
            }

            $warehouse = VendorWarehouse::create([
                'vendor_id' => $vendor->id,
                'name' => $validated['name'],
                'address_line_1' => $validated['address_line_1'],
                'address_line_2' => $validated['address_line_2'] ?? null,
                'city' => $validated['city'],
                'state' => $validated['state'] ?? null,
                'postal_code' => $validated['postal_code'],
                'country' => $validated['country'],
                'contact_name' => $validated['contact_name'],
                'contact_email' => $validated['contact_email'],
                'contact_phone' => $validated['contact_phone'],
                'operating_hours' => $validated['operating_hours'] ?? null,
                'dispatch_cutoff_time' => $validated['dispatch_cutoff_time'] ?? null,
                'is_primary' => $validated['is_primary'] ?? false,
                'status' => 'pending_verification',
            ]);

            // Handle document uploads
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $document) {
                    $path = $document->store('warehouses/' . $warehouse->id, 'public');
                    
                    \App\Models\VendorDocument::create([
                        'vendor_id' => $vendor->id,
                        'warehouse_id' => $warehouse->id,
                        'type' => 'warehouse_document',
                        'file_path' => $path,
                        'file_name' => $document->getClientOriginalName(),
                        'status' => 'pending_review',
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('seller.warehouses.index')
                ->with('success', 'Warehouse submitted for verification.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create warehouse: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function show($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $warehouse = VendorWarehouse::where('vendor_id', $vendor->id)
            ->with(['documents', 'offers.product'])
            ->findOrFail($id);

        $inventory = \App\Models\SellerInventoryMovement::where('warehouse_id', $id)
            ->latest()
            ->take(50)
            ->get();

        return view('seller.warehouses.show', compact('warehouse', 'inventory'));
    }

    public function edit($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $warehouse = VendorWarehouse::where('vendor_id', $vendor->id)->findOrFail($id);

        // Can only edit pending or rejected warehouses
        if (!in_array($warehouse->status, ['pending_verification', 'rejected', 'approved'])) {
            return back()->withErrors(['error' => 'Cannot edit warehouse in current status.']);
        }

        return view('seller.warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, $id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $warehouse = VendorWarehouse::where('vendor_id', $vendor->id)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|size:2',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:50',
            'operating_hours' => 'nullable|string|max:255',
            'dispatch_cutoff_time' => 'nullable|date_format:H:i',
            'is_primary' => 'boolean',
            'documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        DB::beginTransaction();
        try {
            // If primary, unset other primaries
            if (($validated['is_primary'] ?? false) && !$warehouse->is_primary) {
                VendorWarehouse::where('vendor_id', $vendor->id)
                    ->where('id', '!=', $id)
                    ->update(['is_primary' => false]);
            }

            $warehouse->update([
                'name' => $validated['name'],
                'address_line_1' => $validated['address_line_1'],
                'address_line_2' => $validated['address_line_2'] ?? null,
                'city' => $validated['city'],
                'state' => $validated['state'] ?? null,
                'postal_code' => $validated['postal_code'],
                'country' => $validated['country'],
                'contact_name' => $validated['contact_name'],
                'contact_email' => $validated['contact_email'],
                'contact_phone' => $validated['contact_phone'],
                'operating_hours' => $validated['operating_hours'] ?? null,
                'dispatch_cutoff_time' => $validated['dispatch_cutoff_time'] ?? null,
                'is_primary' => $validated['is_primary'] ?? false,
            ]);

            // Handle new document uploads
            if ($request->hasFile('documents')) {
                foreach ($request->file('documents') as $document) {
                    $path = $document->store('warehouses/' . $warehouse->id, 'public');
                    
                    \App\Models\VendorDocument::create([
                        'vendor_id' => $vendor->id,
                        'warehouse_id' => $warehouse->id,
                        'type' => 'warehouse_document',
                        'file_path' => $path,
                        'file_name' => $document->getClientOriginalName(),
                        'status' => 'pending_review',
                    ]);
                }
            }

            // If was approved and edited, reset to pending
            if ($warehouse->status === 'approved') {
                $warehouse->status = 'pending_verification';
                $warehouse->save();
            }

            DB::commit();

            return redirect()->route('seller.warehouses.index')
                ->with('success', 'Warehouse updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update warehouse: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy($id)
    {
        $vendor = Auth::guard('vendor')->user();
        
        $warehouse = VendorWarehouse::where('vendor_id', $vendor->id)->findOrFail($id);

        // Cannot delete if has active offers or stock
        $hasOffers = \App\Models\SellerOffer::where('warehouse_id', $id)->exists();
        $hasStock = \App\Models\SellerInventoryMovement::where('warehouse_id', $id)->exists();

        if ($hasOffers || $hasStock) {
            return back()->withErrors(['error' => 'Cannot delete warehouse with existing offers or inventory.']);
        }

        $warehouse->delete();

        return redirect()->route('seller.warehouses.index')
            ->with('success', 'Warehouse deleted.');
    }
}
