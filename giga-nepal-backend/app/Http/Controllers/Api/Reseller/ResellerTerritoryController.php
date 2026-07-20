<?php

namespace App\Http\Controllers\Api\Reseller;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\ResellerTerritory;
use App\Services\Reseller\ResellerContextService;
use App\Services\Reseller\ResellerTerritoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ResellerTerritoryController extends Controller
{
    use ApiResponses;

    public function index(Request $request, ResellerContextService $context): JsonResponse
    {
        $reseller = $context->abortUnlessReseller($request->user());

        return $this->success(
            ResellerTerritory::query()->where('reseller_id', $reseller->id)->get()
        );
    }

    public function requestExpansion(Request $request, ResellerContextService $context, ResellerTerritoryService $territories): JsonResponse
    {
        $reseller = $context->abortUnlessReseller($request->user());

        if (! Schema::hasTable('reseller_territory_requests')) {
            return $this->error('Territory request migration is pending.', 503);
        }

        $data = $request->validate([
            'marketplace_id' => ['required', 'integer', 'exists:marketplaces,id'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'document_company_reg' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'document_reseller_certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'document_tax_certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        return $this->success($territories->requestExpansion($reseller, $data, $request), 201);
    }
}
