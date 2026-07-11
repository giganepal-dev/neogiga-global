<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceAuditLog;
use App\Services\Marketplace\MarketplaceDomainService;
use App\Services\Marketplace\MarketplaceLaunchValidator;
use App\Services\Marketplace\MarketplaceSeoService;
use App\Services\Marketplace\MarketplaceStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Admin marketplace domain/SEO/status management (codex §8). Gated by the
 * fail-closed admin.token middleware. Wraps MarketplaceDomainService /
 * MarketplaceSeoService / MarketplaceLaunchValidator / MarketplaceStatusService
 * — no domain/SEO/status logic lives here. Never activates without validation
 * and never marks a domain verified without a real DNS check.
 */
class MarketplaceAdminController extends Controller
{
    public function __construct(
        private readonly MarketplaceDomainService $domains,
        private readonly MarketplaceSeoService $seo,
        private readonly MarketplaceLaunchValidator $validator,
        private readonly MarketplaceStatusService $status,
    ) {
    }

    public function index(): JsonResponse
    {
        $rows = Marketplace::query()
            ->with(['country:id,name,iso_code_2', 'currency:id,code,symbol'])
            ->orderByDesc('is_active')->orderBy('name')
            ->get()
            ->map(fn (Marketplace $m) => $this->summary($m));

        return response()->json(['data' => $rows]);
    }

    public function show(string $id): JsonResponse
    {
        $m = Marketplace::with(['country', 'currency', 'domains'])->findOrFail($id);

        return response()->json(['data' => $m, 'validation' => $this->validator->validate($m)]);
    }

    /** PATCH .../status  body: {action: enable|disable, reason?, force?} */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'action' => 'required|in:enable,disable',
            'reason' => 'nullable|string|max:1000',
            'force' => 'nullable|boolean',
        ]);
        $m = Marketplace::findOrFail($id);
        $userId = $request->user()?->id;

        if ($data['action'] === 'disable') {
            $reason = $data['reason'] ?? '';
            if (trim($reason) === '') {
                return response()->json(['message' => 'A reason is required to disable a marketplace.', 'errors' => ['reason' => ['required']]], 422);
            }
            $this->status->disable($m, $reason, $userId);

            return response()->json(['data' => $this->summary($m->fresh())]);
        }

        // enable
        $isSuperAdmin = (bool) ($request->user()?->is_super_admin ?? false);
        $result = $this->status->enable($m, force: (bool) ($data['force'] ?? false), isSuperAdmin: $isSuperAdmin, userId: $userId);
        if (! $result['ok']) {
            return response()->json([
                'message' => 'Activation blocked by validation.',
                'validation' => $result,
            ], 422);
        }

        return response()->json(['data' => $this->summary($m->fresh()), 'validation' => $result]);
    }

    /** POST .../generate-domain  body: {confirm?: bool}. Preview unless confirm. */
    public function generateDomain(Request $request, string $id): JsonResponse
    {
        $m = Marketplace::with('country')->findOrFail($id);
        $suggestion = $this->domains->suggestGeneratedDomain($m);

        if ($suggestion === null) {
            return response()->json(['message' => 'No ISO country code to generate a domain from.'], 422);
        }
        if ($this->domains->isDuplicateDomain($suggestion, $m->id)) {
            return response()->json(['message' => "Generated domain {$suggestion} is already in use.", 'suggested' => $suggestion], 409);
        }

        if (! $request->boolean('confirm')) {
            // Preview only — do not save (codex §2: require explicit confirmation).
            return response()->json(['suggested' => $suggestion, 'saved' => false]);
        }
        if ($m->is_domain_locked) {
            return response()->json(['message' => 'Domain is locked; unlock (Super Admin) before regenerating.'], 423);
        }

        $old = ['generated_domain' => $m->generated_domain];
        $m->generated_domain = $suggestion;
        $m->save();
        MarketplaceAuditLog::record($m->id, 'domain_generated', $old, ['generated_domain' => $suggestion], $request->user()?->id);

        return response()->json(['suggested' => $suggestion, 'saved' => true, 'data' => $this->summary($m)]);
    }

    /** POST .../generate-seo  body: {only_empty?: bool} */
    public function generateSeo(Request $request, string $id): JsonResponse
    {
        $m = Marketplace::with('country')->findOrFail($id);
        $written = $this->seo->apply($m, onlyEmpty: $request->boolean('only_empty'));
        MarketplaceAuditLog::record($m->id, 'seo_changed', [], ['generated_fields' => $written], $request->user()?->id);

        return response()->json(['data' => $m->fresh(), 'generated_fields' => $written]);
    }

    /** POST .../validate-launch */
    public function validateLaunch(string $id): JsonResponse
    {
        $m = Marketplace::with(['country', 'currency'])->findOrFail($id);

        return response()->json($this->validator->validate($m));
    }

    /** POST .../verify-domain — real DNS check only; never fakes verification. */
    public function verifyDomain(Request $request, string $id): JsonResponse
    {
        $m = Marketplace::findOrFail($id);
        $host = $m->domain ?: $m->generated_domain;
        if (empty($host)) {
            return response()->json(['message' => 'No domain to verify.'], 422);
        }

        $resolves = @checkdnsrr($host, 'A') || @checkdnsrr($host, 'AAAA') || @checkdnsrr($host, 'CNAME');
        if ($resolves) {
            $m->domain_verified_at = now();
            $m->save();
        }
        MarketplaceAuditLog::record($m->id, 'domain_verification_attempted', [], ['host' => $host, 'resolves' => $resolves], $request->user()?->id);

        return response()->json(['host' => $host, 'verified' => (bool) $resolves, 'domain_verified_at' => $m->domain_verified_at]);
    }

    /** POST .../clear-cache */
    public function clearCache(Request $request, string $id): JsonResponse
    {
        $m = Marketplace::findOrFail($id);
        foreach (['marketplace:public-editions', 'marketplace:all-editions'] as $key) {
            Cache::forget($key);
        }
        MarketplaceAuditLog::record($m->id, 'cache_cleared', [], [], $request->user()?->id);

        return response()->json(['message' => 'Marketplace cache cleared.']);
    }

    public function auditHistory(string $id): JsonResponse
    {
        $rows = MarketplaceAuditLog::where('marketplace_id', $id)->latest()->limit(200)->get();

        return response()->json(['data' => $rows]);
    }

    private function summary(Marketplace $m): array
    {
        return [
            'id' => $m->id,
            'name' => $m->name,
            'code' => $m->code,
            'country' => $m->country?->name,
            'currency' => $m->currency?->code,
            'domain' => $m->domain,
            'generated_domain' => $m->generated_domain,
            'domain_mode' => $m->domain_mode,
            'ssl_status' => $m->ssl_status,
            'domain_verified' => $m->domain_verified_at !== null,
            'is_active' => (bool) $m->is_active,
            'is_visible' => (bool) $m->is_visible,
            'checkout_enabled' => (bool) $m->checkout_enabled,
            'launch_status' => $m->launch_status,
            'seo_complete' => ! empty($m->seo_title) && ! empty($m->seo_description),
            'indexable' => (bool) $m->indexable,
            'seo_robots' => $m->seo_robots,
        ];
    }
}
