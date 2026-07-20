<?php

namespace App\Services\Reseller;

use App\Models\Reseller;
use App\Models\ResellerApplication;
use App\Models\ResellerTerritory;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ResellerApplicationService
{
    public function apply(array $data, Request $request, ?User $user = null): ResellerApplication
    {
        $documents = $this->storeDocuments($request);
        unset(
            $data['document_company_reg'],
            $data['document_reseller_certificate'],
            $data['document_tax_certificate'],
            $data['document_gst_info'],
        );

        return ResellerApplication::create([
            ...$data,
            ...$documents,
            'user_id' => $user?->id,
            'status' => 'pending',
        ]);
    }

    public function approve(ResellerApplication $application, ?int $reviewerId = null): Reseller
    {
        return DB::transaction(function () use ($application) {
            $user = $application->user_id
                ? User::find($application->user_id)
                : User::where('email', $application->email)->first();

            $reseller = Reseller::create([
                'user_id' => $user?->id,
                'company_name' => $application->company_name,
                'contact_person' => $application->contact_person,
                'email' => $application->email,
                'phone' => $application->phone,
                'country_id' => $application->country_id,
                'home_marketplace_id' => $application->marketplace_id,
                'registration_number' => $application->registration_number,
                'tax_number' => $application->tax_number,
                'status' => 'approved',
                'is_active' => true,
            ]);

            ResellerTerritory::create([
                'reseller_id' => $reseller->id,
                'marketplace_id' => $application->marketplace_id,
                'country_id' => $application->country_id,
                'is_primary' => true,
                'is_active' => true,
                'status' => 'active',
            ]);

            if ($user) {
                $role = Role::firstOrCreate(
                    ['name' => 'reseller'],
                    [
                        'display_name' => 'Reseller',
                        'description' => 'Regional reseller partner',
                        'permissions' => [
                            'reseller.access',
                            'reseller.products.manage',
                            'reseller.orders.view',
                            'reseller.rfq.bid',
                            'reseller.support.manage',
                            'reseller.messaging.manage',
                        ],
                        'is_active' => true,
                    ]
                );
                $user->forceFill(['role_id' => $role->id])->save();
            }

            $application->forceFill(['status' => 'approved'])->save();

            return $reseller;
        });
    }

    /**
     * @return array<string, ?string>
     */
    private function storeDocuments(Request $request): array
    {
        $paths = [
            'document_company_reg' => null,
            'document_reseller_certificate' => null,
            'document_tax_certificate' => null,
            'document_gst_info' => null,
        ];

        foreach (array_keys($paths) as $field) {
            /** @var UploadedFile|null $file */
            $file = $request->file($field);
            if ($file) {
                $paths[$field] = $file->store('reseller-applications/'.Str::slug($request->input('company_name', 'app')), 'public');
            }
        }

        return $paths;
    }
}
