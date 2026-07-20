<?php

namespace App\Http\Controllers\Api\Reseller;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Services\Marketplace\UserMarketplaceScopeService;
use App\Services\Reseller\ResellerApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ResellerApplicationController extends Controller
{
    use ApiResponses;

    public function apply(
        Request $request,
        ResellerApplicationService $service,
        UserMarketplaceScopeService $marketplaceScope,
    ): JsonResponse {
        if (! Schema::hasTable('reseller_applications')) {
            return $this->error('Reseller application migration is pending.', 503);
        }

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:190'],
            'contact_person' => ['required', 'string', 'max:140'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'marketplace_id' => ['nullable', 'integer', 'exists:marketplaces,id'],
            'registration_number' => ['nullable', 'string', 'max:120'],
            'tax_number' => ['nullable', 'string', 'max:120'],
            'territory_notes' => ['nullable', 'string', 'max:2000'],
            'document_company_reg' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'document_reseller_certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'document_tax_certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'document_gst_info' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        if (empty($data['marketplace_id'])) {
            $data['marketplace_id'] = $marketplaceScope->homeMarketplaceIdForRegistration($request);
        }

        $application = $service->apply($data, $request, $request->user());

        return $this->success($application->only(['id', 'company_name', 'email', 'status']), 201);
    }
}
