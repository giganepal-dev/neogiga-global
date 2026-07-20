<?php

namespace App\Services\Reseller;

use App\Models\Reseller;
use App\Models\ResellerTerritory;
use App\Models\ResellerTerritoryRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ResellerTerritoryService
{
    public function requestExpansion(Reseller $reseller, array $data, Request $request): ResellerTerritoryRequest
    {
        $documents = [];
        foreach (['document_company_reg', 'document_reseller_certificate', 'document_tax_certificate'] as $field) {
            /** @var UploadedFile|null $file */
            $file = $request->file($field);
            if ($file) {
                $documents[$field] = $file->store('reseller-territory-requests/'.$reseller->id, 'public');
            }
        }

        return ResellerTerritoryRequest::create([
            'reseller_id' => $reseller->id,
            'marketplace_id' => $data['marketplace_id'] ?? null,
            'country_id' => $data['country_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'pending',
            ...$documents,
        ]);
    }

    public function approveRequest(ResellerTerritoryRequest $request): void
    {
        DB::transaction(function () use ($request) {
            ResellerTerritory::updateOrCreate(
                [
                    'reseller_id' => $request->reseller_id,
                    'marketplace_id' => $request->marketplace_id,
                ],
                [
                    'country_id' => $request->country_id,
                    'is_primary' => false,
                    'is_active' => true,
                    'status' => 'active',
                ]
            );

            $request->forceFill(['status' => 'approved', 'rejection_reason' => null])->save();
        });
    }

    public function rejectRequest(ResellerTerritoryRequest $request, string $reason): void
    {
        $request->forceFill([
            'status' => 'rejected',
            'notes' => trim(($request->notes ?? '')."\nRejected: ".$reason),
        ])->save();
    }
}
