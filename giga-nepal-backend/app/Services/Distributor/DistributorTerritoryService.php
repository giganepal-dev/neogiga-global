<?php

namespace App\Services\Distributor;

use App\Models\Distributor\Distributor;
use App\Models\Distributor\DistributorTerritory;
use App\Models\Distributor\DistributorTerritoryRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class DistributorTerritoryService
{
    public function requestExpansion(Distributor $distributor, array $data, Request $request): DistributorTerritoryRequest
    {
        $documents = [];
        foreach (array_keys(config('distributor.territory_documents', [])) as $field) {
            /** @var UploadedFile|null $file */
            $file = $request->file($field);
            if ($file) {
                $documents[$field] = $file->store('distributor-territory-requests/'.$distributor->id, 'public');
            }
        }

        return DistributorTerritoryRequest::create([
            'distributor_id' => $distributor->id,
            'country_id' => $data['country_id'] ?? null,
            'region_id' => $data['region_id'] ?? null,
            'city_id' => $data['city_id'] ?? null,
            'territory_name' => $data['territory_name'],
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
            ...$documents,
        ]);
    }

    public function approveRequest(DistributorTerritoryRequest $territoryRequest): void
    {
        DB::transaction(function () use ($territoryRequest) {
            DistributorTerritory::create([
                'distributor_id' => $territoryRequest->distributor_id,
                'country_id' => $territoryRequest->country_id,
                'region_id' => $territoryRequest->region_id,
                'city_id' => $territoryRequest->city_id,
                'territory_name' => $territoryRequest->territory_name,
                'exclusive' => false,
                'can_manage_downlines' => false,
            ]);

            $territoryRequest->forceFill(['status' => 'approved', 'rejection_reason' => null])->save();
        });
    }

    public function rejectRequest(DistributorTerritoryRequest $territoryRequest, string $reason): void
    {
        $territoryRequest->forceFill([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ])->save();
    }
}
