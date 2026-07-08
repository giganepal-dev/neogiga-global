<?php

namespace App\Http\Controllers\Api\Distributor;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Distributor\DistributorApplyRequest;
use App\Models\Distributor\Distributor;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DistributorApplicationController extends Controller
{
    use ApiResponses;

    public function apply(DistributorApplyRequest $request): JsonResponse
    {
        if (! Schema::hasTable('distributors')) {
            return $this->error('Distributor foundation migration is pending.', 503);
        }

        $data = $request->validated();
        if (Distributor::where('email', $data['email'])->exists()) {
            return $this->error('A distributor application already exists for this email.', 422);
        }

        $slug = Str::slug($data['name']);
        $base = $slug;
        $i = 1;
        while (Distributor::where('slug', $slug)->exists()) {
            $slug = $base . '-' . ++$i;
        }

        $distributor = Distributor::create([
            'name' => $data['name'],
            'slug' => $slug,
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'type' => $data['type'],
            'country_id' => $data['country_id'] ?? null,
            'status' => 'pending',
            'metadata' => ['notes' => $data['notes'] ?? null],
        ]);

        $distributor->profile()->create(['business_name' => $data['business_name'] ?? null]);

        return $this->success($distributor->only(['id', 'name', 'slug', 'status', 'type']), 201);
    }
}
