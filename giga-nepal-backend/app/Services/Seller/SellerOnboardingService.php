<?php

namespace App\Services\Seller;

use App\Models\SellerApplication;
use App\Models\Marketplace\Vendor;
use App\Models\Marketplace\VendorProfile;
use App\Models\Marketplace\VendorWarehouse;
use App\Models\Marketplace\VendorDocument;
use App\Models\Marketplace\VendorMarketplaceApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\UploadedFile;

class SellerOnboardingService
{
    protected const ONBOARDING_STEPS = [
        'business_profile' => 1,
        'legal_registration' => 2,
        'tax_registration' => 3,
        'authorized_representative' => 4,
        'bank_account' => 5,
        'warehouse' => 6,
        'marketplace_application' => 7,
        'compliance_declaration' => 8,
        'seller_agreement' => 9,
        'admin_verification' => 10,
    ];

    protected const STEP_STATUSES = [
        'not_started',
        'in_progress',
        'submitted',
        'correction_required',
        'approved',
        'rejected',
        'expired',
        'suspended',
    ];

    public function getOnboardingSteps(SellerApplication $application): array
    {
        $vendor = Vendor::where('user_id', $application->user_id)->first();
        
        $steps = [];
        foreach (self::ONBOARDING_STEPS as $stepKey => $stepOrder) {
            $status = $this->calculateStepStatus($application, $vendor, $stepKey);
            $steps[] = [
                'key' => $stepKey,
                'name' => $this->getStepName($stepKey),
                'order' => $stepOrder,
                'status' => $status,
                'required_documents' => $this->getRequiredDocuments($stepKey),
            ];
        }

        return $steps;
    }

    public function calculateReadinessPercentage(SellerApplication $application): float
    {
        $vendor = Vendor::where('user_id', $application->user_id)->first();
        
        if (!$vendor) {
            return 0.0;
        }

        $completedSteps = 0;
        $totalSteps = count(self::ONBOARDING_STEPS);

        foreach (self::ONBOARDING_STEPS as $stepKey => $stepOrder) {
            $status = $this->calculateStepStatus($application, $vendor, $stepKey);
            if (in_array($status, ['approved'])) {
                $completedSteps++;
            }
        }

        return round(($completedSteps / $totalSteps) * 100, 2);
    }

    protected function calculateStepStatus(SellerApplication $application, ?Vendor $vendor, string $stepKey): string
    {
        switch ($stepKey) {
            case 'business_profile':
                if ($application->status === 'approved') {
                    return 'approved';
                }
                if ($application->status === 'rejected') {
                    return 'rejected';
                }
                if ($application->status === 'pending') {
                    return 'submitted';
                }
                return 'not_started';

            case 'legal_registration':
                if (!$vendor) return 'not_started';
                $docs = $this->getLegalDocs($vendor);
                if ($docs->where('status', 'approved')->count() >= 2) {
                    return 'approved';
                }
                if ($docs->where('status', 'pending_review')->count() > 0) {
                    return 'submitted';
                }
                if ($docs->where('status', 'correction_required')->count() > 0) {
                    return 'correction_required';
                }
                return $docs->count() > 0 ? 'in_progress' : 'not_started';

            case 'tax_registration':
                if (!$vendor) return 'not_started';
                $taxDocs = $this->getTaxDocs($vendor);
                if ($taxDocs->where('status', 'approved')->count() > 0) {
                    return 'approved';
                }
                if ($taxDocs->where('status', 'pending_review')->count() > 0) {
                    return 'submitted';
                }
                return $taxDocs->count() > 0 ? 'in_progress' : 'not_started';

            case 'authorized_representative':
                if (!$vendor || !$vendor->profile) return 'not_started';
                $profile = $vendor->profile;
                if (!empty($profile->metadata['authorized_representative'])) {
                    return 'approved';
                }
                return 'not_started';

            case 'bank_account':
                if (!$vendor) return 'not_started';
                $bankDocs = $this->getBankDocs($vendor);
                if ($bankDocs->where('status', 'approved')->count() > 0) {
                    return 'approved';
                }
                return $bankDocs->count() > 0 ? 'in_progress' : 'not_started';

            case 'warehouse':
                if (!$vendor) return 'not_started';
                $warehouses = $vendor->warehouses;
                if ($warehouses->where('approval_status', 'approved')->count() > 0) {
                    return 'approved';
                }
                if ($warehouses->where('approval_status', 'pending')->count() > 0) {
                    return 'submitted';
                }
                return $warehouses->count() > 0 ? 'in_progress' : 'not_started';

            case 'marketplace_application':
                if (!$vendor) return 'not_started';
                $approvals = $vendor->marketplaceApprovals;
                if ($approvals->where('status', 'approved')->count() > 0) {
                    return 'approved';
                }
                if ($approvals->where('status', 'pending')->count() > 0) {
                    return 'submitted';
                }
                return $approvals->count() > 0 ? 'in_progress' : 'not_started';

            case 'compliance_declaration':
                if (!$vendor) return 'not_started';
                $complianceDocs = $this->getComplianceDocs($vendor);
                if ($complianceDocs->where('status', 'approved')->count() > 0) {
                    return 'approved';
                }
                return $complianceDocs->count() > 0 ? 'in_progress' : 'not_started';

            case 'seller_agreement':
                if (!$vendor) return 'not_started';
                if (!empty($vendor->metadata['agreement_accepted_at'])) {
                    return 'approved';
                }
                return 'not_started';

            case 'admin_verification':
                if ($application->status === 'approved') {
                    return 'approved';
                }
                if ($application->status === 'pending') {
                    return 'submitted';
                }
                return 'not_started';

            default:
                return 'not_started';
        }
    }

    protected function getLegalDocs(Vendor $vendor)
    {
        return VendorDocument::where('vendor_id', $vendor->id)
            ->whereIn('document_type', ['company_registration', 'business_license', 'incorporation_certificate'])
            ->get();
    }

    protected function getTaxDocs(Vendor $vendor)
    {
        return VendorDocument::where('vendor_id', $vendor->id)
            ->whereIn('document_type', ['pan_certificate', 'vat_certificate', 'tax_clearance'])
            ->get();
    }

    protected function getBankDocs(Vendor $vendor)
    {
        return VendorDocument::where('vendor_id', $vendor->id)
            ->whereIn('document_type', ['bank_account_proof', 'cancelled_cheque'])
            ->get();
    }

    protected function getComplianceDocs(Vendor $vendor)
    {
        return VendorDocument::where('vendor_id', $vendor->id)
            ->whereIn('document_type', ['compliance_declaration', 'iso_certificate', 'quality_certification'])
            ->get();
    }

    protected function getStepName(string $key): string
    {
        $names = [
            'business_profile' => 'Business Profile',
            'legal_registration' => 'Legal Registration',
            'tax_registration' => 'Tax Registration',
            'authorized_representative' => 'Authorized Representative',
            'bank_account' => 'Bank Account',
            'warehouse' => 'Warehouse Setup',
            'marketplace_application' => 'Marketplace Application',
            'compliance_declaration' => 'Compliance Declaration',
            'seller_agreement' => 'Seller Agreement',
            'admin_verification' => 'Admin Verification',
        ];

        return $names[$key] ?? $key;
    }

    protected function getRequiredDocuments(string $key): array
    {
        $documents = [
            'business_profile' => [],
            'legal_registration' => ['company_registration', 'business_license'],
            'tax_registration' => ['pan_certificate', 'vat_certificate'],
            'authorized_representative' => [],
            'bank_account' => ['bank_account_proof', 'cancelled_cheque'],
            'warehouse' => [],
            'marketplace_application' => [],
            'compliance_declaration' => ['compliance_declaration'],
            'seller_agreement' => [],
            'admin_verification' => [],
        ];

        return $documents[$key] ?? [];
    }

    public function submitBusinessProfile(User $user, array $data): SellerApplication
    {
        return DB::transaction(function () use ($user, $data) {
            $application = SellerApplication::updateOrCreate(
                ['user_id' => $user->id],
                array_merge($data, ['status' => 'pending'])
            );

            // Create vendor record
            Vendor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'business_name' => $data['business_name'] ?? $user->name,
                    'status' => 'pending',
                ]
            );

            // Send notification
            $this->sendNotification($user, 'onboarding.submitted', [
                'step' => 'business_profile',
                'application_id' => $application->id,
            ]);

            return $application;
        });
    }

    public function uploadDocument(User $user, string $documentType, UploadedFile $file, ?string $description = null): VendorDocument
    {
        $vendor = Vendor::where('user_id', $user->id)->firstOrFail();

        $path = $file->store('vendor-documents/' . $vendor->id, 'public');

        return VendorDocument::create([
            'vendor_id' => $vendor->id,
            'document_type' => $documentType,
            'title' => $description ?: ucfirst(str_replace('_', ' ', $documentType)),
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $user->id,
            'status' => 'pending_review',
        ]);
    }

    public function addWarehouse(User $user, array $data): VendorWarehouse
    {
        $vendor = Vendor::where('user_id', $user->id)->firstOrFail();

        return VendorWarehouse::create([
            'vendor_id' => $vendor->id,
            'name' => $data['name'],
            'address_line_1' => $data['address_line_1'],
            'address_line_2' => $data['address_line_2'] ?? null,
            'city' => $data['city'],
            'state' => $data['state'] ?? null,
            'country' => $data['country'],
            'postal_code' => $data['postal_code'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'warehouse_type' => $data['warehouse_type'] ?? 'own',
            'is_active' => false,
            'approval_status' => 'pending',
            'metadata' => [
                'operating_hours' => $data['operating_hours'] ?? null,
                'dispatch_cutoff_time' => $data['dispatch_cutoff_time'] ?? '15:00',
                'coverage_areas' => $data['coverage_areas'] ?? [],
            ],
        ]);
    }

    public function applyToMarketplace(User $user, int $marketplaceId): VendorMarketplaceApproval
    {
        $vendor = Vendor::where('user_id', $user->id)->firstOrFail();

        // Check if already applied
        $existing = VendorMarketplaceApproval::where('vendor_id', $vendor->id)
            ->where('marketplace_id', $marketplaceId)
            ->first();

        if ($existing) {
            throw new \Exception('Already applied to this marketplace');
        }

        return VendorMarketplaceApproval::create([
            'vendor_id' => $vendor->id,
            'marketplace_id' => $marketplaceId,
            'status' => 'pending',
            'applied_at' => now(),
        ]);
    }

    public function acceptAgreement(User $user, string $ipAddress): Vendor
    {
        $vendor = Vendor::where('user_id', $user->id)->firstOrFail();

        $vendor->update([
            'metadata' => array_merge(
                $vendor->metadata ?? [],
                [
                    'agreement_accepted_at' => now()->toIso8601String(),
                    'agreement_ip_address' => $ipAddress,
                    'agreement_version' => config('neogiga.seller_agreement_version', '1.0'),
                ]
            ),
        ]);

        return $vendor;
    }

    protected function sendNotification(User $user, string $event, array $data): void
    {
        // This will be connected to the notification system
        // For now, we'll create a database notification
        $user->notifications()->create([
            'type' => 'seller_onboarding',
            'data' => [
                'event' => $event,
                ...$data,
            ],
        ]);
    }
}
