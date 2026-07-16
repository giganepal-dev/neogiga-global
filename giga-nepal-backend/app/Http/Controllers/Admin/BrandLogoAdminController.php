<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\CheckBrokenBrandLogosJob;
use App\Jobs\DiscoverBrandLogoJob;
use App\Jobs\RegenerateBrandLogoVariantsJob;
use App\Jobs\VerifyBrandLogoJob;
use App\Models\Marketplace\BrandLogoHistory;
use App\Models\Marketplace\ProductBrand;
use App\Services\Catalog\BrandLogoDiscoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class BrandLogoAdminController extends Controller
{
    public function index(Request $request): View
    {
        $query = ProductBrand::query()->withCount('products')->with(['logoHistory' => fn ($history) => $history->limit(3)]);
        if ($request->filled('q')) {
            $query->where(fn ($brands) => $brands->where('name', 'like', '%'.$request->string('q').'%')->orWhere('slug', 'like', '%'.$request->string('q').'%'));
        }
        if ($request->filled('status')) {
            $query->where('logo_status', $request->string('status'));
        }

        return view('admin.brand-logos', [
            'brands' => $query->orderBy('name')->paginate(40)->withQueryString(),
            'filters' => ['q' => (string) $request->query('q', ''), 'status' => (string) $request->query('status', '')],
        ]);
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate(['action' => ['required', 'in:discover_missing,verify_pending,regenerate_variants,check_broken']]);
        if ($data['action'] === 'check_broken') {
            CheckBrokenBrandLogosJob::dispatch();

            return back()->with('status', 'Broken-logo check queued. It will never delete media.');
        }

        $count = 0;
        if ($data['action'] === 'discover_missing') {
            ProductBrand::query()->where(fn ($brands) => $brands->whereNull('logo_path')->orWhere('logo_verified', false))->orderBy('id')->eachById(function (ProductBrand $brand) use (&$count, $request): void {
                DiscoverBrandLogoJob::dispatch($brand->id, $request->user()?->id);
                $count++;
            });
        }
        if ($data['action'] === 'verify_pending') {
            BrandLogoHistory::query()->where('status', 'pending')->orderBy('id')->eachById(function (BrandLogoHistory $history) use (&$count): void {
                VerifyBrandLogoJob::dispatch($history->brand_id, $history->id);
                $count++;
            });
        }
        if ($data['action'] === 'regenerate_variants') {
            ProductBrand::query()->where('logo_verified', true)->orderBy('id')->eachById(function (ProductBrand $brand) use (&$count): void {
                RegenerateBrandLogoVariantsJob::dispatch($brand->id);
                $count++;
            });
        }

        return back()->with('status', number_format($count).' brand logo job(s) queued.');
    }

    public function exportMissing(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['brand_id', 'brand_name', 'slug', 'website_url', 'logo_status']);
            ProductBrand::query()->where(fn ($brands) => $brands->whereNull('logo_path')->orWhere('logo_verified', false))->orderBy('name')->eachById(function (ProductBrand $brand) use ($stream): void {
                fputcsv($stream, [$brand->id, $brand->name, $brand->slug, $brand->website_url, $brand->logo_status]);
            });
            fclose($stream);
        }, 'neogiga-brands-missing-official-logos.csv', ['Content-Type' => 'text/csv']);
    }

    public function discover(Request $request, ProductBrand $brand, BrandLogoDiscoveryService $logos): RedirectResponse
    {
        $plan = $logos->discoverOfficialLogo($brand);
        if (($plan['action'] ?? null) !== 'stage_for_approval') {
            $brand->update(['logo_status' => 'manual_review', 'logo_review_note' => $plan['review_note']]);

            return back()->with('status', 'No high-confidence official logo was staged. The brand is queued for manual review.');
        }
        $logos->stageDiscoveredLogo($brand, $plan, $request->user()?->id);

        return back()->with('status', 'Official logo candidate staged. Review and approve it before publishing.');
    }

    public function upload(Request $request, ProductBrand $brand, BrandLogoDiscoveryService $logos): RedirectResponse
    {
        $data = $request->validate([
            'logo' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif,avif,svg'],
            'source_url' => ['nullable', 'url', 'max:2000'],
            'source_domain' => ['nullable', 'string', 'max:190'],
            'review_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $logos->stageManualUpload($brand, $data['logo'], [
            'original_url' => $data['source_url'] ?? null,
            'source_domain' => $data['source_domain'] ?? parse_url((string) ($data['source_url'] ?? ''), PHP_URL_HOST),
            'review_note' => $data['review_note'] ?? null,
        ], $request->user()?->id);

        return back()->with('status', 'Logo upload staged. It remains unpublished until an admin approves it.');
    }

    public function approve(Request $request, ProductBrand $brand, BrandLogoHistory $history, BrandLogoDiscoveryService $logos): RedirectResponse
    {
        $logos->approveStagedLogo($brand, $history, (int) $request->user()->id);

        return back()->with('status', 'Verified official logo published. Previous logo provenance remains in history.');
    }

    public function reject(Request $request, ProductBrand $brand, BrandLogoHistory $history): RedirectResponse
    {
        abort_unless($history->brand_id === $brand->id, 404);
        $history->update(['status' => 'rejected', 'review_note' => $request->input('review_note', $history->review_note)]);

        return back()->with('status', 'Logo candidate rejected. The current published logo was not changed.');
    }

    public function unavailable(Request $request, ProductBrand $brand): RedirectResponse
    {
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:2000']]);
        $brand->update(['logo_status' => 'unavailable', 'logo_review_note' => $data['review_note'] ?? 'Official logo could not be verified.']);

        return back()->with('status', 'Brand marked as logo unavailable. Existing logo data was preserved.');
    }

    public function remove(Request $request, ProductBrand $brand): RedirectResponse
    {
        BrandLogoHistory::create([
            'brand_id' => $brand->id,
            'action' => 'removed',
            'storage_disk' => config('brand_logos.disk', 'public'),
            'logo_path' => $brand->logo_path,
            'original_url' => $brand->logo_original_url,
            'source_domain' => $brand->logo_source_domain,
            'source_type' => $brand->logo_source_type,
            'confidence' => $brand->logo_confidence,
            'status' => 'archived',
            'review_note' => 'Logo removed from public display; stored asset retained for audit.',
            'created_by' => $request->user()?->id,
        ]);
        $brand->update(['logo_path' => null, 'logo_verified' => false, 'logo_status' => 'pending']);

        return back()->with('status', 'Logo removed from public display. The underlying media and audit trail were retained.');
    }
}
