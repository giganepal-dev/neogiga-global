<?php

namespace App\Http\Controllers\Api\Onboarding;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\SellerApplicationRequest;
use App\Models\Onboarding\SellerApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use App\Services\Partner\PartnerCountryService;

class SellerApplicationController extends Controller
{
    use ApiResponses;

    public function store(SellerApplicationRequest $request, PartnerCountryService $countries): JsonResponse
    {
        if (! Schema::hasTable('seller_applications')) {
            return $this->error('Seller application migration is pending.', 503);
        }

        $data = $request->validated();
        $data['country_id'] = $countries->resolveSignupCountry($request, $data['country_id'] ?? null);
        $data['operating_scope'] = $countries->normalizeScope($data['operating_scope'] ?? null);
        if (Schema::hasColumn('seller_applications', 'country')) {
            $data['country'] = $countries->countryName($data['country_id']);
        }
        $recent = SellerApplication::where('email', $data['email'])
            ->whereIn('status', ['pending', 'contacted', 'approved_for_onboarding'])
            ->latest()
            ->first();

        if ($recent) {
            return $this->error('A seller early access application already exists for this email.', 422);
        }

        $application = SellerApplication::create($data + [
            'status' => 'pending',
            'source' => $data['source'] ?? 'public_sell_on_neogiga',
        ]);

        return $this->success($application->only(['id', 'business_name', 'email', 'status', 'source']), 201);
    }
}
