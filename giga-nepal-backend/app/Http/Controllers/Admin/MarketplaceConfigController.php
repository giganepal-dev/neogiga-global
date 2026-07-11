<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Country;
use App\Models\Marketplace\Currency;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\MarketplaceAuditLog;
use App\Services\Marketplace\MarketplaceDomainService;
use App\Services\Marketplace\MarketplaceLaunchValidator;
use App\Services\Marketplace\MarketplaceSeoService;
use App\Services\Marketplace\MarketplaceStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Server-rendered admin UI for marketplace domain/SEO/status configuration
 * (codex §3, §11). Gated by admin.web. Delegates every domain/SEO/status
 * decision to the marketplace services — no such logic lives here — so the UI
 * cannot activate a marketplace that fails validation, cannot overwrite a
 * custom/locked domain, and cannot mark a domain verified without a real check.
 */
class MarketplaceConfigController extends Controller
{
    public function __construct(
        private readonly MarketplaceDomainService $domains,
        private readonly MarketplaceSeoService $seo,
        private readonly MarketplaceLaunchValidator $validator,
        private readonly MarketplaceStatusService $status,
    ) {
    }

    public function edit(int $id): View
    {
        $marketplace = Marketplace::with(['country', 'currency', 'domains'])->findOrFail($id);

        return view('admin.marketplace-edit', [
            'm' => $marketplace,
            'validation' => $this->validator->validate($marketplace),
            'suggestedDomain' => $this->domains->suggestGeneratedDomain($marketplace),
            'countries' => Country::orderBy('name')->get(['id', 'name', 'iso_code_2']),
            'currencies' => Currency::orderBy('code')->get(['id', 'code', 'symbol']),
            'audit' => MarketplaceAuditLog::where('marketplace_id', $id)->latest()->limit(40)->get(),
        ]);
    }

    /** Tab-aware save (general / domain / status / seo / branding / advanced). */
    public function update(Request $request, int $id): RedirectResponse
    {
        $m = Marketplace::findOrFail($id);
        $tab = (string) $request->input('tab', 'general');
        $userId = Auth::id();
        $before = $m->only(['name', 'domain', 'seo_title', 'is_active']);

        match ($tab) {
            'general' => $this->saveGeneral($request, $m),
            'domain' => $this->saveDomain($request, $m),
            'status' => $this->saveStatus($request, $m),
            'seo' => $this->saveSeo($request, $m),
            'branding' => $this->saveBranding($request, $m),
            'advanced' => $this->saveAdvanced($request, $m),
            default => null,
        };

        $m->updated_by = $userId;
        $m->save();
        MarketplaceAuditLog::record($m->id, "config_{$tab}_updated", $before, $m->only(array_keys($before)), $userId, $request->ip(), $request->userAgent());

        return back()->with('status', ucfirst($tab) . ' settings saved.')->with('tab', $tab);
    }

    public function enable(Request $request, int $id): RedirectResponse
    {
        $m = Marketplace::findOrFail($id);
        $isSuper = (Auth::user()?->role->name ?? null) === 'super_admin';
        $result = $this->status->enable($m, force: $request->boolean('force'), isSuperAdmin: $isSuper, userId: Auth::id());

        if (! $result['ok']) {
            $failed = collect($result['checklist'])->where('passed', false)->where('critical', true)->pluck('label')->implode(', ');

            return back()->withErrors(['enable' => "Activation blocked. Fix: {$failed}"])->with('tab', 'status');
        }

        return back()->with('status', 'Marketplace enabled.')->with('tab', 'status');
    }

    public function disable(Request $request, int $id): RedirectResponse
    {
        $request->validate(['reason' => 'required|string|max:1000']);
        $m = Marketplace::findOrFail($id);
        $this->status->disable($m, (string) $request->input('reason'), Auth::id());

        return back()->with('status', 'Marketplace disabled.')->with('tab', 'status');
    }

    public function generateDomain(Request $request, int $id): RedirectResponse
    {
        $m = Marketplace::with('country')->findOrFail($id);
        $suggestion = $this->domains->suggestGeneratedDomain($m);

        if ($suggestion === null) {
            return back()->withErrors(['domain' => 'No ISO country code to generate a domain from.'])->with('tab', 'domain');
        }
        if ($this->domains->isDuplicateDomain($suggestion, $m->id)) {
            return back()->withErrors(['domain' => "Suggested domain {$suggestion} is already in use."])->with('tab', 'domain');
        }
        if (! $request->boolean('confirm')) {
            return back()->with('status', "Suggested domain: {$suggestion}. Confirm to save.")->with('suggested', $suggestion)->with('tab', 'domain');
        }
        if ($m->is_domain_locked) {
            return back()->withErrors(['domain' => 'Domain is locked (Super Admin only).'])->with('tab', 'domain');
        }

        $m->generated_domain = $suggestion;
        $m->save();
        MarketplaceAuditLog::record($m->id, 'domain_generated', [], ['generated_domain' => $suggestion], Auth::id());

        return back()->with('status', "Generated domain saved: {$suggestion}")->with('tab', 'domain');
    }

    public function verifyDomain(int $id): RedirectResponse
    {
        $m = Marketplace::findOrFail($id);
        $host = $m->domain ?: $m->generated_domain;
        if (empty($host)) {
            return back()->withErrors(['domain' => 'No domain to verify.'])->with('tab', 'domain');
        }

        $resolves = @checkdnsrr($host, 'A') || @checkdnsrr($host, 'AAAA') || @checkdnsrr($host, 'CNAME');
        if ($resolves) {
            $m->domain_verified_at = now();
            $m->save();
        }
        MarketplaceAuditLog::record($m->id, 'domain_verification_attempted', [], ['host' => $host, 'resolves' => $resolves], Auth::id());

        return back()->with('status', $resolves ? "DNS verified for {$host}." : "DNS did NOT resolve for {$host}; not verified.")->with('tab', 'domain');
    }

    public function generateSeo(Request $request, int $id): RedirectResponse
    {
        $m = Marketplace::with('country')->findOrFail($id);
        $written = $this->seo->apply($m, onlyEmpty: $request->boolean('only_empty'));
        MarketplaceAuditLog::record($m->id, 'seo_changed', [], ['generated_fields' => $written], Auth::id());

        return back()->with('status', count($written) . ' SEO field(s) generated.')->with('tab', 'seo');
    }

    public function clearCache(int $id): RedirectResponse
    {
        $m = Marketplace::findOrFail($id);
        foreach (['marketplace:public-editions', 'marketplace:all-editions'] as $key) {
            Cache::forget($key);
        }
        Cache::forget('seo:sitemap:' . ($m->domain ?: $m->generated_domain) . ':full');
        MarketplaceAuditLog::record($m->id, 'cache_cleared', [], [], Auth::id());

        return back()->with('status', 'Marketplace cache cleared.')->with('tab', 'advanced');
    }

    // ---- tab savers ----

    private function saveGeneral(Request $request, Marketplace $m): void
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'country_id' => 'nullable|integer|exists:countries,id',
            'currency_id' => 'nullable|integer|exists:currencies,id',
            'locale' => 'nullable|string|max:10',
            'default_language' => 'nullable|string|max:10',
            'timezone' => 'nullable|string|max:64',
            'short_description' => 'nullable|string|max:500',
        ]);
        $m->fill($data);
    }

    private function saveDomain(Request $request, Marketplace $m): void
    {
        $data = $request->validate([
            'domain_mode' => 'required|in:custom_domain,subdomain,path',
            'domain' => 'nullable|string|max:255',
            'domain_prefix' => 'nullable|string|max:20',
            'canonical_domain' => 'nullable|string|max:255',
            'www_redirect_mode' => 'nullable|in:none,www_to_non_www,non_www_to_www',
        ]);

        // Locked custom production domain: only a Super Admin may change the
        // hostname or the lock flag.
        $isSuper = (Auth::user()?->role->name ?? null) === 'super_admin';
        if ($m->is_domain_locked && ! $isSuper) {
            unset($data['domain']);
        }

        if (! empty($data['domain'])) {
            $clean = $this->domains->sanitizeHostname($data['domain']);
            if ($clean === null) {
                throw ValidationException::withMessages(['domain' => 'Invalid hostname (no protocol/path/space/IP/wildcard).']);
            }
            if ($this->domains->isDuplicateDomain($clean, $m->id)) {
                throw ValidationException::withMessages(['domain' => "Domain {$clean} already used by another marketplace."]);
            }
            $data['domain'] = $clean;
        }

        $m->fill($data);
        $m->domain_mode = $data['domain_mode'];
        $m->www_redirect_mode = $data['www_redirect_mode'] ?? 'none';
        $m->force_https = $request->boolean('force_https');
        $m->redirect_to_canonical = $request->boolean('redirect_to_canonical');
        if ($isSuper) {
            $m->is_domain_locked = $request->boolean('is_domain_locked');
        }
    }

    private function saveStatus(Request $request, Marketplace $m): void
    {
        // is_active is only changed via enable/disable (validated). Here we save
        // the softer access toggles.
        $m->allow_customer_registration = $request->boolean('allow_customer_registration');
        $m->allow_vendor_registration = $request->boolean('allow_vendor_registration');
        $m->checkout_enabled = $request->boolean('checkout_enabled');
        $m->maintenance_mode = $request->boolean('maintenance_mode');
        $m->maintenance_message = $request->input('maintenance_message');
        $m->launch_at = $request->input('launch_at') ?: null;
    }

    private function saveSeo(Request $request, Marketplace $m): void
    {
        $fields = [
            'seo_title', 'seo_description', 'seo_keywords', 'seo_h1', 'seo_canonical_url',
            'seo_robots', 'seo_og_title', 'seo_og_description', 'seo_og_image',
            'seo_twitter_title', 'seo_twitter_description', 'seo_twitter_image',
        ];
        $manual = [];
        foreach ($fields as $f) {
            if ($request->filled($f)) {
                $m->{$f} = $request->input($f);
                $manual[] = $f; // any field the operator sets by hand is protected from auto-gen
            }
        }
        $m->indexable = $request->boolean('indexable');
        $m->sitemap_enabled = $request->boolean('sitemap_enabled');
        $m->hreflang_enabled = $request->boolean('hreflang_enabled');
        // Merge with any previously protected fields.
        $existing = (array) ($m->seo_manual_override_fields ?? []);
        $m->seo_manual_override_fields = array_values(array_unique(array_merge($existing, $manual)));
    }

    private function saveBranding(Request $request, Marketplace $m): void
    {
        $m->fill($request->validate([
            'logo' => 'nullable|string|max:255',
            'favicon' => 'nullable|string|max:255',
            'banner_image' => 'nullable|string|max:255',
            'homepage_heading' => 'nullable|string|max:255',
            'homepage_subheading' => 'nullable|string|max:255',
            'marketplace_description' => 'nullable|string|max:2000',
        ]));
    }

    private function saveAdvanced(Request $request, Marketplace $m): void
    {
        // Custom scripts are Super-Admin only (codex §9).
        if ((Auth::user()?->role->name ?? null) === 'super_admin') {
            $m->seo_header_scripts = $request->input('seo_header_scripts');
            $m->seo_footer_scripts = $request->input('seo_footer_scripts');
        }
        $settings = $request->input('settings');
        if ($settings !== null && $settings !== '') {
            $decoded = json_decode((string) $settings, true);
            if (is_array($decoded)) {
                $m->settings = $decoded;
            }
        }
    }
}
