<?php

namespace App\Http\Controllers\Api\Onboarding;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\DistributorApplicationRequest;
use App\Models\Onboarding\DistributorApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use App\Services\Partner\PartnerCountryService;

class DistributorApplicationController extends Controller
{
    use ApiResponses;

    public function store(DistributorApplicationRequest $request, PartnerCountryService $countries): JsonResponse
    {
        if (! Schema::hasTable('distributor_applications')) {
            return $this->error('Distributor application migration is pending.', 503);
        }

        $data = $request->validated();
        $data['country_id'] = $countries->resolveSignupCountry($request, $data['country_id'] ?? null);
        $data['operating_scope'] = $countries->normalizeScope($data['operating_scope'] ?? null);
        if (Schema::hasColumn('distributor_applications', 'full_name')) {
            $data['full_name'] = $data['contact_person'];
        }
        if (Schema::hasColumn('distributor_applications', 'company_name')) {
            $data['company_name'] = $data['business_name'];
        }
        $recent = DistributorApplication::where('email', $data['email'])
            ->whereIn('status', ['pending', 'contacted', 'approved_for_onboarding'])
            ->latest()
            ->first();

        if ($recent) {
            return $this->error('A distributor network application already exists for this email.', 422);
        }

        $application = DistributorApplication::create($data + [
            'status' => 'pending',
            'source' => $data['source'] ?? 'public_distributor_network',
        ]);

        return $this->success($application->only(['id', 'business_name', 'email', 'status', 'source']), 201);
    }
}
