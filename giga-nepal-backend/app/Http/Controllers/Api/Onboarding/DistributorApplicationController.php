<?php

namespace App\Http\Controllers\Api\Onboarding;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\DistributorApplicationRequest;
use App\Models\Onboarding\DistributorApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class DistributorApplicationController extends Controller
{
    use ApiResponses;

    public function store(DistributorApplicationRequest $request): JsonResponse
    {
        if (! Schema::hasTable('distributor_applications')) {
            return $this->error('Distributor application migration is pending.', 503);
        }

        $data = $request->validated();
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
