<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Onboarding\ApplicationStatusRequest;
use App\Models\Distributor\Distributor;
use App\Models\Marketplace\Vendor;
use App\Models\Onboarding\DistributorApplication;
use App\Models\Onboarding\SellerApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OnboardingAdminController extends Controller
{
    use ApiResponses;

    public function sellerApplications(): JsonResponse
    {
        if (! Schema::hasTable('seller_applications')) {
            return $this->error('Seller application migration is pending.', 503);
        }

        return $this->success(SellerApplication::latest()->paginate(25));
    }

    public function sellerApplication(int $application): JsonResponse
    {
        if (! Schema::hasTable('seller_applications')) {
            return $this->error('Seller application migration is pending.', 503);
        }

        return $this->success(SellerApplication::findOrFail($application));
    }

    public function updateSellerStatus(ApplicationStatusRequest $request, int $application): JsonResponse
    {
        $record = SellerApplication::findOrFail($application);
        $this->updateStatus($record, $request);

        return $this->success($record->fresh());
    }

    public function convertSellerToVendor(Request $request, int $application): JsonResponse
    {
        if (! Schema::hasTable('vendors')) {
            return $this->error('Vendor table is not available.', 503);
        }

        $record = SellerApplication::findOrFail($application);
        if (Vendor::where('email', $record->email)->exists()) {
            return $this->error('A vendor already exists for this email.', 422);
        }
        $slug = $this->uniqueSlug(Vendor::class, $record->business_name);
        $vendor = Vendor::create([
            'name' => $record->business_name,
            'slug' => $slug,
            'email' => $record->email,
            'phone' => $record->phone,
            'status' => 'pending',
        ]);
        $record->forceFill([
            'status' => 'approved_for_onboarding',
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'admin_notes' => trim(($record->admin_notes ? $record->admin_notes . "\n" : '') . 'Converted to pending vendor ID ' . $vendor->id),
        ])->save();

        $this->log('seller_application.converted_to_vendor', $request, ['application_id' => $record->id, 'vendor_id' => $vendor->id]);

        return $this->success(['application' => $record->fresh(), 'vendor' => $vendor], 201);
    }

    public function distributorApplications(): JsonResponse
    {
        if (! Schema::hasTable('distributor_applications')) {
            return $this->error('Distributor application migration is pending.', 503);
        }

        return $this->success(DistributorApplication::latest()->paginate(25));
    }

    public function distributorApplication(int $application): JsonResponse
    {
        if (! Schema::hasTable('distributor_applications')) {
            return $this->error('Distributor application migration is pending.', 503);
        }

        return $this->success(DistributorApplication::findOrFail($application));
    }

    public function updateDistributorStatus(ApplicationStatusRequest $request, int $application): JsonResponse
    {
        $record = DistributorApplication::findOrFail($application);
        $this->updateStatus($record, $request);

        return $this->success($record->fresh());
    }

    public function convertDistributor(Request $request, int $application): JsonResponse
    {
        if (! Schema::hasTable('distributors')) {
            return $this->error('Distributor table is not available.', 503);
        }

        $record = DistributorApplication::findOrFail($application);
        if (Distributor::where('email', $record->email)->exists()) {
            return $this->error('A distributor already exists for this email.', 422);
        }
        $slug = $this->uniqueSlug(Distributor::class, $record->business_name);
        $distributor = Distributor::create([
            'name' => $record->business_name,
            'slug' => $slug,
            'email' => $record->email,
            'phone' => $record->phone,
            'type' => $record->distributor_type,
            'country_id' => $record->country_id,
            'status' => 'pending',
            'metadata' => ['source_application_id' => $record->id],
        ]);
        $record->forceFill([
            'status' => 'approved_for_onboarding',
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'admin_notes' => trim(($record->admin_notes ? $record->admin_notes . "\n" : '') . 'Converted to pending distributor ID ' . $distributor->id),
        ])->save();

        $this->log('distributor_application.converted_to_distributor', $request, ['application_id' => $record->id, 'distributor_id' => $distributor->id]);

        return $this->success(['application' => $record->fresh(), 'distributor' => $distributor], 201);
    }

    public function sellerOnboardingSummary(): JsonResponse
    {
        return $this->success([
            'pending_seller_applications' => Schema::hasTable('seller_applications') ? SellerApplication::where('status', 'pending')->count() : 0,
            'pending_distributor_applications' => Schema::hasTable('distributor_applications') ? DistributorApplication::where('status', 'pending')->count() : 0,
            'countries_with_seller_interest' => Schema::hasTable('seller_applications') ? SellerApplication::whereNotNull('country_id')->distinct('country_id')->count('country_id') : 0,
            'regions_with_distributor_interest' => Schema::hasTable('distributor_applications') ? DistributorApplication::whereNotNull('region_id')->distinct('region_id')->count('region_id') : 0,
        ]);
    }

    public function aiCommerceSummary(): JsonResponse
    {
        return $this->success([
            'ai_sessions' => Schema::hasTable('commerce_ai_sessions') ? DB::table('commerce_ai_sessions')->count() : 0,
            'bom_requests' => Schema::hasTable('commerce_ai_bom_requests') ? DB::table('commerce_ai_bom_requests')->count() : 0,
            'top_requested_ai_boms' => Schema::hasTable('commerce_ai_bom_requests')
                ? DB::table('commerce_ai_bom_requests')->select('intent', DB::raw('count(*) as total'))->groupBy('intent')->orderByDesc('total')->limit(8)->get()
                : [],
            'most_requested_product_categories' => [],
        ]);
    }

    private function updateStatus($record, ApplicationStatusRequest $request): void
    {
        $data = $request->validated();
        $record->forceFill([
            'status' => $data['status'],
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'admin_notes' => $data['admin_notes'] ?? $record->admin_notes,
        ])->save();

        $this->log(class_basename($record) . '.status_changed', $request, ['application_id' => $record->id, 'status' => $data['status']]);
    }

    private function uniqueSlug(string $modelClass, string $name): string
    {
        $slug = Str::slug($name);
        $base = $slug ?: 'application';
        $slug = $base;
        $i = 1;
        while ($modelClass::where('slug', $slug)->exists()) {
            $slug = $base . '-' . ++$i;
        }

        return $slug;
    }

    private function log(string $action, Request $request, array $metadata): void
    {
        if (! Schema::hasTable('marketing_admin_audit_logs')) {
            return;
        }

        DB::table('marketing_admin_audit_logs')->insert([
            'user_id' => $request->user()?->id,
            'action' => $action,
            'entity_type' => 'onboarding',
            'entity_id' => $metadata['application_id'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'metadata' => json_encode($metadata + ['ip' => $request->ip()]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
