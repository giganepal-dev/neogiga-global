<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\SellerInventoryAdjustRequest;
use App\Services\Seller\SellerContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SellerInventoryController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly SellerContextService $context)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());

        if (! Schema::hasTable('vendor_inventory')) {
            return $this->error('Vendor inventory table is pending migration.', 503);
        }

        return $this->success(DB::table('vendor_inventory')->where('vendor_id', $vendor->id)->latest('id')->paginate(25));
    }

    public function adjust(SellerInventoryAdjustRequest $request): JsonResponse
    {
        $vendor = $this->context->abortUnlessVendor($request->user());
        $data = $request->validated();

        if (! Schema::hasTable('vendor_inventory')) {
            return $this->error('Vendor inventory table is pending migration.', 503);
        }

        $row = DB::table('vendor_inventory')
            ->where('vendor_id', $vendor->id)
            ->where('product_id', $data['product_id'])
            ->where('warehouse_id', $data['warehouse_id'])
            ->first();

        abort_if(! $row, 404, 'Vendor inventory row not found.');

        $available = max(0, (int) $row->quantity_available + (int) $data['quantity_change']);

        DB::table('vendor_inventory')->where('id', $row->id)->update([
            'quantity_available' => $available,
            'updated_at' => now(),
        ]);

        return $this->success(['id' => $row->id, 'quantity_available' => $available]);
    }
}
