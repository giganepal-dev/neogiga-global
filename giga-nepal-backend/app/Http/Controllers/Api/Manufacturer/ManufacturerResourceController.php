<?php

namespace App\Http\Controllers\Api\Manufacturer;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Manufacturer\ManufacturerContextService;
use App\Services\Manufacturer\ManufacturerInventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ManufacturerResourceController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly ManufacturerContextService $context,
        private readonly ManufacturerInventoryService $inventory,
    ) {}

    public function profile(Request $request): JsonResponse
    {
        return $this->success($this->context->abortUnlessManufacturer($request->user()));
    }

    public function inventorySummary(Request $request): JsonResponse
    {
        $manufacturer = $this->context->abortUnlessManufacturer($request->user());

        return $this->success($this->inventory->globalSummary($manufacturer));
    }

    public function inventory(Request $request): JsonResponse
    {
        $manufacturer = $this->context->abortUnlessManufacturer($request->user());

        return $this->success($this->inventory->paginateGlobalInventory($manufacturer, (int) $request->input('per_page', 25)));
    }

    public function allocations(Request $request): JsonResponse
    {
        $manufacturer = $this->context->abortUnlessManufacturer($request->user());

        return $this->success($this->inventory->paginateAllocations($manufacturer, (int) $request->input('per_page', 25)));
    }

    public function storeAllocation(Request $request): JsonResponse
    {
        $manufacturer = $this->context->abortUnlessManufacturer($request->user());
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'marketplace_id' => ['nullable', 'integer'],
            'warehouse_id' => ['required', 'integer'],
            'quantity_allocated' => ['required', 'numeric', 'min:0.0001'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $allocation = $this->inventory->allocateToRegion($manufacturer, $data);

        return $this->success($allocation, 201);
    }
}
