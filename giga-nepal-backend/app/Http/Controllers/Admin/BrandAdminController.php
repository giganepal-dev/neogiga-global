<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Country;
use App\Models\Marketplace\Marketplace;
use App\Models\Marketplace\ProductBrand;
use App\Models\Marketplace\ProductCategory;
use App\Services\Catalog\BrandVisibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BrandAdminController extends Controller
{
    public function __construct(private readonly BrandVisibilityService $visibility)
    {
    }

    public function index(Request $request): View
    {
        $query = ProductBrand::query()->withCount('products');
        if ($term = trim((string) $request->query('q', ''))) {
            $query->where(fn ($inner) => $inner->where('name', 'ilike', "%{$term}%")->orWhere('slug', 'ilike', "%{$term}%"));
        }
        if (($status = $request->query('status')) !== null && $status !== '') {
            $query->where('is_active', $status === 'active');
        }
        if (($menu = $request->query('menu')) !== null && $menu !== '') {
            $query->where('is_menu_visible', $menu === 'visible');
        }

        return view('admin.brands', [
            'brands' => $query->orderBy('sort_order')->orderBy('name')->paginate(30)->withQueryString(),
            'countries' => Country::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'iso_code_2']),
            'marketplaces' => Marketplace::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'code']),
            'categories' => ProductCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['q', 'status', 'menu']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $id = $request->integer('id') ?: null;
        $data = $request->validate([
            'id' => ['nullable', 'integer', 'exists:product_brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('product_brands', 'slug')->ignore($id)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'logo_path' => ['nullable', 'string', 'max:1000'],
            'banner_path' => ['nullable', 'string', 'max:1000'],
            'website_url' => ['nullable', 'url', 'max:1000'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'marketplace_ids' => ['nullable', 'array'],
            'marketplace_ids.*' => ['integer', 'exists:marketplaces,id'],
            'country_ids' => ['nullable', 'array'],
            'country_ids.*' => ['integer', 'exists:countries,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:product_categories,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'menu_placement' => ['nullable', 'in:primary,featured,footer'],
            'publication_starts_at' => ['nullable', 'date'],
            'publication_ends_at' => ['nullable', 'date', 'after_or_equal:publication_starts_at'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'string', 'max:1000'],
            'canonical_url' => ['nullable', 'url', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_menu_visible' => ['nullable', 'boolean'],
            'display_desktop' => ['nullable', 'boolean'],
            'display_mobile' => ['nullable', 'boolean'],
            'hide_when_unavailable' => ['nullable', 'boolean'],
            'landing_page_enabled' => ['nullable', 'boolean'],
        ]);

        $payload = [
            'name' => $data['name'],
            'slug' => ($data['slug'] ?? null) ?: Str::slug($data['name']),
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'logo_path' => $data['logo_path'] ?? null,
            'banner_path' => $data['banner_path'] ?? null,
            'website_url' => $data['website_url'] ?? null,
            'country_id' => $data['country_id'] ?? null,
            'marketplace_visibility' => $data['marketplace_ids'] ?? [],
            'country_visibility' => $data['country_ids'] ?? [],
            'category_visibility' => $data['category_ids'] ?? [],
            'sort_order' => $data['sort_order'] ?? 0,
            'menu_placement' => $data['menu_placement'] ?? 'primary',
            'publication_starts_at' => $data['publication_starts_at'] ?? null,
            'publication_ends_at' => $data['publication_ends_at'] ?? null,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'seo_keywords' => $data['seo_keywords'] ?? null,
            'canonical_url' => $data['canonical_url'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured'),
            'is_menu_visible' => $request->boolean('is_menu_visible'),
            'display_desktop' => $request->boolean('display_desktop'),
            'display_mobile' => $request->boolean('display_mobile'),
            'hide_when_unavailable' => $request->boolean('hide_when_unavailable'),
            'landing_page_enabled' => $request->boolean('landing_page_enabled'),
        ];

        $brand = $id ? ProductBrand::findOrFail($id) : new ProductBrand();
        $before = $brand->exists ? $brand->only(array_keys($payload)) : [];
        $brand->fill($payload)->save();
        $this->visibility->clear();
        $this->audit($request, $brand, $id ? 'brand_updated' : 'brand_created', $before, $payload);

        return back()->with('status', "Brand {$brand->name} saved.");
    }

    public function toggle(Request $request, ProductBrand $brand): RedirectResponse
    {
        $before = ['is_active' => $brand->is_active];
        $brand->update(['is_active' => ! $brand->is_active]);
        $this->visibility->clear();
        $this->audit($request, $brand, 'brand_status_toggled', $before, ['is_active' => $brand->is_active]);

        return back()->with('status', "Brand {$brand->name} is now ".($brand->is_active ? 'active' : 'inactive').'.');
    }

    public function destroy(Request $request, ProductBrand $brand): RedirectResponse
    {
        if ($brand->products()->exists()) {
            return back()->with('error', 'This brand is assigned to products and was preserved. Deactivate it instead.');
        }

        $before = $brand->only(['id', 'name', 'slug']);
        $brand->delete();
        $this->visibility->clear();
        $this->audit($request, $brand, 'brand_deleted', $before, []);

        return back()->with('status', 'Unused brand deleted.');
    }

    private function audit(Request $request, ProductBrand $brand, string $action, array $old, array $new): void
    {
        try {
            DB::table('audit_logs')->insert([
                'user_id' => $request->user()?->id,
                'action' => $action,
                'model_type' => 'product_brands',
                'model_id' => $brand->id,
                'model_display_name' => $brand->name,
                'old_values' => json_encode($old),
                'new_values' => json_encode($new),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
        }
    }
}
