<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Marketplace\Product;
use App\Models\Marketplace\ProductCategory;
use App\Services\Marketplace\GlobalMarketplaceContextService;
use App\Services\Seo\CatalogSeoTemplateService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /** Allowed sort values mapped to DB columns/expressions */
    private const SORT_MAP = [
        'newest'        => ['products.created_at', 'desc'],
        'price_asc'     => ['marketplace_product_prices.base_price', 'asc'],
        'price_desc'    => ['marketplace_product_prices.base_price', 'desc'],
        'name_asc'      => ['products.name', 'asc'],
        'name_desc'     => ['products.name', 'desc'],
        'rating_desc'   => ['products.rating_avg', 'desc'],
    ];

    private const DEFAULT_SORT = 'newest';
    private const PER_PAGE = 24;

    public function index(): View
    {
        $roots = ProductCategory::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            // Imported supplier path labels are retained for audit, but are not
            // storefront roots. NeoGiga taxonomy roots always have a display order.
            ->where('sort_order', '>', 0)
            ->where('slug', '!=', 'uncategorized')
            ->with(['children' => fn ($q) => $q
                ->where('is_active', true)
                ->where('name', 'not like', '%|%')
                ->orderBy('sort_order')
                ->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $total = $roots->count();

        return view('frontend.categories.index', compact('roots', 'total'));
    }

    public function show(string $slug): View
    {
        $request = request(); // Route passes slug first, Request via helper

        $category = ProductCategory::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // --- Resolve all descendant IDs recursively ---
        $categoryIds = $category->idsIncludingSelfAndDescendants();

        // --- Immediate children for subcategory nav ---
        $children = ProductCategory::query()
            ->where('parent_id', $category->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // --- Sort ---
        $sort = $request->query('sort', self::DEFAULT_SORT);
        if (! array_key_exists($sort, self::SORT_MAP)) {
            $sort = self::DEFAULT_SORT;
        }
        [$sortCol, $sortDir] = self::SORT_MAP[$sort];

        // --- Base product query with regional price join ---
        $productsQuery = Product::query()
            ->with(['images' => fn ($q) => $q->where('is_active', true)
                ->orderByDesc('is_primary')->orderBy('sort_order')->limit(1)])
            ->with('category')
            ->whereIn('products.category_id', $categoryIds)
            ->published();

        // --- Regional price join ---
        $marketplace = app(GlobalMarketplaceContextService::class)->context($request)['current'] ?? null;
        if ($marketplace) {
            $productsQuery->leftJoin('marketplace_product_prices as price', function ($join) use ($marketplace) {
                $join->on('price.product_id', '=', 'products.id')
                    ->where('price.marketplace_id', '=', $marketplace->id);
            });
            $productsQuery->addSelect('products.*', DB::raw('COALESCE(price.base_price, NULL) as regional_price'));
        } else {
            $productsQuery->addSelect('products.*');
        }

        // --- Stock filter ---
        $stock = $request->query('stock');
        if ($stock === 'local') {
            $productsQuery->whereHas('stocks', fn ($q) => $q->where('quantity_available', '>', 0));
        } elseif ($stock === 'global') {
            // no-op: show all (global inherently includes everything)
        } elseif ($stock === 'out_of_stock') {
            $productsQuery->whereDoesntHave('stocks', fn ($q) => $q->where('quantity_available', '>', 0));
        }

        // --- Apply sort ---
        if ($sortCol === 'marketplace_product_prices.base_price' && $marketplace) {
            $productsQuery->orderByRaw('COALESCE(price.base_price, 999999999) ' . $sortDir);
        } elseif ($sortCol === 'products.rating_avg') {
            $productsQuery->orderByRaw('COALESCE(products.rating_avg, 0) ' . $sortDir);
        } else {
            $productsQuery->orderBy($sortCol, $sortDir);
        }

        // --- Paginate ---
        $page = max(1, (int) $request->query('page', 1));
        $total = $productsQuery->count('products.id');
        $products = $productsQuery
            ->skip(($page - 1) * self::PER_PAGE)
            ->take(self::PER_PAGE)
            ->get();

        $paginator = new LengthAwarePaginator(
            $products,
            $total,
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        // --- Counts ---
        $directCount = Product::query()->where('category_id', $category->id)->published()->count();
        $inclusiveCount = $total; // Already counted above

        $breadcrumb = $this->breadcrumb($category);
        $relatedLessons = $this->relatedLessons($category);
        $marketplaceContext = app(GlobalMarketplaceContextService::class)->context($request);
        $pageSeo = app(CatalogSeoTemplateService::class)->activeCategory(
            $category,
            $marketplaceContext['current'] ?? null,
            $marketplaceContext['locale'] ?? 'en',
        );

        return view('frontend.categories.show', [
            'category'         => $category,
            'children'         => $children,
            'products'         => $paginator,
            'directCount'      => $directCount,
            'inclusiveCount'   => $inclusiveCount,
            'currentSort'      => $sort,
            'currentStock'     => $stock,
            'breadcrumb'       => $breadcrumb,
            'relatedLessons'   => $relatedLessons,
            'pageSeo'          => $pageSeo,
            'canonical'        => $pageSeo['canonical'],
            'robots'           => $pageSeo['robots'],
            'robotsReason'     => $pageSeo['robots_reason'],
        ]);
    }

    /**
     * @return array<int, array{name:string, url:string}>
     */
    private function breadcrumb(ProductCategory $category): array
    {
        $category->loadMissing('parent.parent.parent.parent.parent.parent.parent.parent.parent.parent.parent.parent');

        $chain = [];
        $node = $category;
        $guard = 0;
        $publicBase = $this->publicBase();

        while ($node && $guard++ < 12) {
            array_unshift($chain, ['name' => $node->name, 'url' => $publicBase.'/categories/'.$node->slug]);
            $node = $node->parent;
        }

        return array_merge(
            [
                ['name' => 'Home', 'url' => $publicBase],
                ['name' => 'Categories', 'url' => $publicBase.'/categories'],
            ],
            $chain,
        );
    }

    private function publicBase(): string
    {
        $prefix = strtolower((string) request()->segment(1));
        if (! array_key_exists($prefix, config('neogiga_global.prefixes', []))) {
            $prefix = config('neogiga_global.default_prefix', 'en');
        }

        return '/'.$prefix;
    }

    /**
     * @return Collection<int, object>
     */
    private function relatedLessons(ProductCategory $category): Collection
    {
        try {
            if (! Schema::hasTable('category_lms_links')) {
                return collect();
            }

            $links = DB::table('category_lms_links as l')
                ->leftJoin('lms_courses as c', 'c.id', '=', 'l.lms_course_id')
                ->leftJoin('lms_projects as p', 'p.id', '=', 'l.lms_project_id')
                ->where('l.product_category_id', $category->id)
                ->where('l.is_active', true)
                ->select('l.title', 'l.relation_type', 'c.slug as course_slug', 'p.slug as project_slug')
                ->orderByDesc('l.id')
                ->limit(6)
                ->get();

            return $links->map(function ($link) {
                $link->url = $link->project_slug
                    ? '/learn/projects/'.$link->project_slug
                    : ($link->course_slug ? '/learn?course='.$link->course_slug : '/learn');

                return $link;
            });
        } catch (\Throwable) {
            return collect();
        }
    }
}
