<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Models\Marketplace\Vendor;
use App\Models\Marketplace\VendorMarketplaceApproval;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\Partner\PartnerCountryService;

class VendorController extends Controller
{
    use ApiResponses;

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $vendors = Vendor::query()
            ->where('status', 'active')
            ->with('country:id,name,iso_code_2')
            ->orderBy('name')
            ->paginate($validated['per_page'] ?? 25);

        return $this->success($vendors);
    }

    public function show(string $slug): JsonResponse
    {
        $vendor = Vendor::query()
            ->where('slug', $slug)
            ->where('status', 'active')
            ->with(['country:id,name,iso_code_2', 'profile'])
            ->first();

        if (!$vendor) {
            return $this->error('Vendor not found', 404);
        }

        return $this->success($vendor);
    }

    /**
     * Public seller-onboarding application (Blueprint §26).
     * Creates a vendor in `pending` status; activation requires
     * marketplace-ops approval (never automatic).
     */
    public function register(Request $request, PartnerCountryService $countries): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:190', 'unique:vendors,email'],
            'phone' => ['required', 'string', 'max:30'],
            'website' => ['sometimes', 'nullable', 'url', 'max:190'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'type' => ['required', 'in:individual,company,manufacturer,distributor'],
            'tax_number' => ['sometimes', 'nullable', 'string', 'max:60'],
            'registration_number' => ['sometimes', 'nullable', 'string', 'max:60'],
            'operating_scope' => ['sometimes', 'in:country,global'],
        ]);
        $validated['country_id'] = $countries->resolveSignupCountry($request, $validated['country_id'] ?? null);
        $validated['operating_scope'] = $countries->normalizeScope($validated['operating_scope'] ?? null);

        $slug = Str::slug($validated['name']);
        $suffix = 1;
        while (Vendor::where('slug', $slug)->exists()) {
            $slug = Str::slug($validated['name']) . '-' . ++$suffix;
        }

        $vendor = Vendor::create([
            ...$validated,
            'slug' => $slug,
            'status' => 'pending',
            'is_verified' => false,
        ]);

        foreach ($countries->marketplaceIdsForScope($validated['operating_scope'], $validated['country_id']) as $marketplaceId) {
            VendorMarketplaceApproval::create([
                'vendor_id' => $vendor->id,
                'marketplace_id' => $marketplaceId,
                'status' => 'pending',
                'application_notes' => 'Marketplace access requested during vendor application.',
                'metadata' => ['operating_scope' => $validated['operating_scope']],
            ]);
        }

        return $this->success([
            'vendor' => $vendor->only(['id', 'name', 'slug', 'status']),
            'message' => 'Application received. Our marketplace operations team will review it.',
        ], 201);
    }

    public function marketplaceApprovals(Vendor $vendor): JsonResponse
    {
        // NOTE (SEC-10): ownership scoping pending auth in Phase 1 — only
        // non-sensitive status fields are exposed until then.
        $approvals = $vendor->marketplaceApprovals()
            ->with('marketplace:id,name,code')
            ->get(['id', 'vendor_id', 'marketplace_id', 'status', 'created_at']);

        return $this->success($approvals);
    }

    public function applyMarketplace(Request $request, Vendor $vendor): JsonResponse
    {
        $validated = $request->validate([
            'marketplace_id' => ['required', 'integer', 'exists:marketplaces,id'],
        ]);

        $approval = VendorMarketplaceApproval::firstOrCreate(
            [
                'vendor_id' => $vendor->id,
                'marketplace_id' => $validated['marketplace_id'],
            ],
            ['status' => 'pending'],
        );

        return $this->success(
            $approval->only(['id', 'vendor_id', 'marketplace_id', 'status']),
            $approval->wasRecentlyCreated ? 201 : 200,
        );
    }
}
