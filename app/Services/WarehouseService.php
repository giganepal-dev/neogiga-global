<?php

namespace App\Services;

use App\Models\VendorWarehouse;
use App\Models\VendorDocument;
use App\Models\SellerApplication;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class WarehouseService
{
    /**
     * Create a new warehouse for a vendor
     */
    public function create(array $data, int $vendorId): VendorWarehouse
    {
        return DB::transaction(function () use ($data, $vendorId) {
            $warehouse = VendorWarehouse::create([
                'vendor_id' => $vendorId,
                'name' => $data['name'],
                'type' => $data['type'] ?? 'owned',
                'address_line1' => $data['address_line1'],
                'address_line2' => $data['address_line2'] ?? null,
                'city' => $data['city'],
                'state' => $data['state'] ?? null,
                'postal_code' => $data['postal_code'],
                'country' => $data['country'],
                'contact_name' => $data['contact_name'],
                'contact_email' => $data['contact_email'],
                'contact_phone' => $data['contact_phone'],
                'is_active' => false,
                'is_verified' => false,
                'verification_status' => 'pending',
            ]);

            // Handle document uploads
            if (isset($data['documents'])) {
                foreach ($data['documents'] as $document) {
                    $path = $document->store('warehouses/' . $warehouse->id, 'private');
                    VendorDocument::create([
                        'vendor_id' => $warehouse->vendor_id,
                        'warehouse_id' => $warehouse->id,
                        'type' => 'warehouse_document',
                        'file_path' => $path,
                        'file_name' => $document->getClientOriginalName(),
                        'status' => 'pending_review',
                    ]);
                }
            }

            return $warehouse;
        });
    }

    /**
     * Update warehouse details
     */
    public function update(VendorWarehouse $warehouse, array $data): VendorWarehouse
    {
        return DB::transaction(function () use ($warehouse, $data) {
            $warehouse->update($data);

            // Handle new document uploads
            if (isset($data['documents'])) {
                foreach ($data['documents'] as $document) {
                    $path = $document->store('warehouses/' . $warehouse->id, 'private');
                    VendorDocument::create([
                        'vendor_id' => $warehouse->vendor_id,
                        'warehouse_id' => $warehouse->id,
                        'type' => 'warehouse_document',
                        'file_path' => $path,
                        'file_name' => $document->getClientOriginalName(),
                        'status' => 'pending_review',
                    ]);
                }
            }

            // If warehouse data changed significantly, reset verification
            if ($warehouse->wasChanged(['address_line1', 'city', 'country'])) {
                $warehouse->update([
                    'is_verified' => false,
                    'verification_status' => 'pending',
                ]);
            }

            return $warehouse->fresh();
        });
    }

    /**
     * Submit warehouse for verification
     */
    public function submitForVerification(VendorWarehouse $warehouse): void
    {
        if ($warehouse->is_verified) {
            throw ValidationException::withMessages([
                'warehouse' => 'Warehouse is already verified.',
            ]);
        }

        $warehouse->update([
            'verification_status' => 'submitted',
            'submitted_at' => now(),
        ]);

        // Trigger notification to admin
        event(new \App\Events\WarehouseSubmittedForVerification($warehouse));
    }

    /**
     * Approve warehouse (Admin only)
     */
    public function approve(VendorWarehouse $warehouse, int $adminId, ?string $notes = null): void
    {
        DB::transaction(function () use ($warehouse, $adminId, $notes) {
            $warehouse->update([
                'is_verified' => true,
                'is_active' => true,
                'verification_status' => 'approved',
                'verified_at' => now(),
                'verified_by' => $adminId,
                'verification_notes' => $notes,
            ]);

            // Log audit trail
            \App\Models\AuditLog::create([
                'user_id' => $adminId,
                'user_type' => 'admin',
                'action' => 'warehouse_approved',
                'model_type' => VendorWarehouse::class,
                'model_id' => $warehouse->id,
                'description' => "Warehouse '{$warehouse->name}' approved by admin",
                'metadata' => ['notes' => $notes],
            ]);
        });

        event(new \App\Events\WarehouseApproved($warehouse));
    }

    /**
     * Reject warehouse (Admin only)
     */
    public function reject(VendorWarehouse $warehouse, int $adminId, string $reason): void
    {
        DB::transaction(function () use ($warehouse, $adminId, $reason) {
            $warehouse->update([
                'is_verified' => false,
                'verification_status' => 'rejected',
                'rejection_reason' => $reason,
                'rejected_at' => now(),
                'rejected_by' => $adminId,
            ]);

            \App\Models\AuditLog::create([
                'user_id' => $adminId,
                'user_type' => 'admin',
                'action' => 'warehouse_rejected',
                'model_type' => VendorWarehouse::class,
                'model_id' => $warehouse->id,
                'description' => "Warehouse '{$warehouse->name}' rejected: {$reason}",
                'metadata' => ['reason' => $reason],
            ]);
        });

        event(new \App\Events\WarehouseRejected($warehouse));
    }

    /**
     * Request corrections on warehouse (Admin only)
     */
    public function requestCorrection(VendorWarehouse $warehouse, int $adminId, array $corrections): void
    {
        DB::transaction(function () use ($warehouse, $adminId, $corrections) {
            $warehouse->update([
                'verification_status' => 'correction_required',
                'correction_requested_at' => now(),
                'correction_requested_by' => $adminId,
                'correction_notes' => json_encode($corrections),
            ]);

            \App\Models\AuditLog::create([
                'user_id' => $adminId,
                'user_type' => 'admin',
                'action' => 'warehouse_correction_requested',
                'model_type' => VendorWarehouse::class,
                'model_id' => $warehouse->id,
                'description' => "Corrections requested for warehouse '{$warehouse->name}'",
                'metadata' => ['corrections' => $corrections],
            ]);
        });

        event(new \App\Events\WarehouseCorrectionRequested($warehouse));
    }

    /**
     * Suspend warehouse
     */
    public function suspend(VendorWarehouse $warehouse, int $adminId, string $reason): void
    {
        $warehouse->update([
            'is_active' => false,
            'suspension_reason' => $reason,
            'suspended_at' => now(),
            'suspended_by' => $adminId,
        ]);

        event(new \App\Events\WarehouseSuspended($warehouse));
    }

    /**
     * Reactivate warehouse
     */
    public function reactivate(VendorWarehouse $warehouse): void
    {
        $warehouse->update([
            'is_active' => true,
            'suspension_reason' => null,
            'suspended_at' => null,
            'suspended_by' => null,
        ]);
    }

    /**
     * Get warehouses by marketplace coverage
     */
    public function getWarehousesByMarketplace(int $vendorId, string $marketplace): array
    {
        $query = VendorWarehouse::where('vendor_id', $vendorId)
            ->where('is_active', true)
            ->where('is_verified', true);

        if ($marketplace !== 'global') {
            $query->where(function ($q) use ($marketplace) {
                $q->whereJsonContains('marketplace_coverage', $marketplace)
                  ->orWhereNull('marketplace_coverage');
            });
        }

        return $query->get()->toArray();
    }

    /**
     * Validate warehouse can be used for order fulfillment
     */
    public function canFulfillFromWarehouse(VendorWarehouse $warehouse): bool
    {
        return $warehouse->is_active 
            && $warehouse->is_verified 
            && $warehouse->verification_status === 'approved';
    }
}
