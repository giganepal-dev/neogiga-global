@extends('frontend.layout')
@section('title', $pageTitle ?? (($category->name ?? 'Products').' — NeoGiga Engineering Marketplace'))
@section('description', $metaDescription ?? 'Browse engineering components and tools: semiconductors, electronics, IoT, robotics, batteries, power and automation. Search by part number, SKU or keyword.')
@php
    $activePrefix = strtolower((string) request()->segment(1));
    $activePrefix = array_key_exists($activePrefix, config('neogiga_global.prefixes', []))
        ? $activePrefix
        : config('neogiga_global.default_prefix', 'en');
    $publicBase = '/'.$activePrefix;
    $listingCanonical = $canonical ?? ($marketplaceSeo['canonical'] ?? url()->current());
    $canonicalParts = parse_url($listingCanonical);
    $canonicalOrigin = ($canonicalParts['scheme'] ?? 'https').'://'.($canonicalParts['host'] ?? request()->getHost());
@endphp

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => $category?->name ?? 'NeoGiga engineering products',
    'url' => $listingCanonical,
    'numberOfItems' => $products->total(),
    'itemListElement' => $products->values()->map(fn ($product, $index) => [
        '@type' => 'ListItem',
        'position' => (($products->currentPage() - 1) * $products->perPage()) + $index + 1,
        'url' => $canonicalOrigin.$publicBase.'/products/'.$product->slug,
        'name' => $product->name,
    ])->all(),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $canonicalOrigin.$publicBase],
        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Products', 'item' => $canonicalOrigin.$publicBase.'/products'],
    ],
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
<style>
    .plist-head{display:flex;flex-wrap:wrap;gap:12px;align-items:center;justify-content:space-between;margin:24px 0 16px}
    .plist-head h1{margin:0;font-size:1.5rem}
    .psearch{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;width:100%}
    .psearch input,.psearch select{padding:10px 12px;border:1px solid rgba(15,23,42,.2);border-radius:9px;font:inherit;min-width:200px}
    .psearch button{min-height:43px}
    .facetbar{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 16px}
    .facetbar a{border:1px solid rgba(15,23,42,.14);border-radius:999px;padding:6px 10px;text-decoration:none;color:#0f172a;background:#fff;font-size:.82rem}
    .facetbar a:hover{border-color:#06b6d4;color:#075985}
    .pgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;margin:16px 0 32px}
    .pcard{border:1px solid rgba(15,23,42,.12);border-radius:12px;padding:18px;background:#fff;display:flex;flex-direction:column;gap:8px}
    .pcard-media{position:relative;display:block;aspect-ratio:4/3;margin:-6px -6px 4px;border-radius:9px;overflow:hidden;background:#081527}
    .pcard-media img{display:block;width:100%;height:100%;object-fit:contain}
    .pcard h2{font-size:1rem;margin:0;line-height:1.35}
    .pcard a{color:#0F172A;text-decoration:none}.pcard a:hover{color:#0369A1}
    .pmeta{color:#64748B;font-size:.82rem}
    .ptag{display:inline-block;background:#ECFEFF;color:#155E75;border-radius:999px;padding:2px 10px;font-size:.75rem;font-weight:600}
    .pstock{font-size:.8rem;font-weight:600}.in{color:#065F46}.out{color:#991B1B}
    .pempty{border:1px dashed rgba(15,23,42,.2);border-radius:12px;padding:48px;text-align:center;color:#475569;margin:24px 0}
</style>

<div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ $publicBase }}">Home</a> › <span>Products</span>@if($category) › <span>{{ $category->name }}</span>@endif</nav>

    <div class="plist-head">
        <h1>{{ $category->name ?? 'All Products' }}</h1>
        <form class="psearch" method="get" action="{{ $publicBase }}/products">
            <input type="search" name="q" value="{{ $q }}" placeholder="Search part number, SKU, keyword…" aria-label="Search products">
            <select name="category" aria-label="Category">
                <option value="">All categories</option>
                @foreach ($rootCategories as $c)
                    <option value="{{ $c->slug }}" @selected(($category->slug ?? '') === $c->slug)>{{ $c->name }}</option>
                @endforeach
            </select>
            <select name="brand_id" aria-label="Brand">
                <option value="">All brands</option>
                @foreach ($brands as $brand)
                    <option value="{{ $brand->id }}" @selected((string) $filters['brandId'] === (string) $brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
            <input name="manufacturer" value="{{ $filters['manufacturer'] }}" placeholder="Manufacturer or MPN">
            <select name="stock" aria-label="Stock">
                <option value="">Any stock</option>
                <option value="in" @selected($filters['stock'] === 'in')>In stock / indexed offer</option>
                <option value="low" @selected($filters['stock'] === 'low')>Low stock</option>
                <option value="out" @selected($filters['stock'] === 'out')>Out of stock</option>
            </select>
            <select name="country_id" aria-label="Country warehouse">
                <option value="">Any country</option>
                @foreach ($countries as $country)
                    <option value="{{ $country->id }}" @selected((string) $filters['countryId'] === (string) $country->id)>{{ $country->name }}</option>
                @endforeach
            </select>
            <input type="number" name="price_min" value="{{ $filters['priceMin'] ?? '' }}" placeholder="Min price" step="0.01" min="0" style="min-width:100px">
            <input type="number" name="price_max" value="{{ $filters['priceMax'] ?? '' }}" placeholder="Max price" step="0.01" min="0" style="min-width:100px">
            <select name="rating_min" aria-label="Minimum rating">
                <option value="">Any rating</option>
                <option value="4" @selected(($filters['ratingMin'] ?? '') === '4')>4★ & above</option>
                <option value="3" @selected(($filters['ratingMin'] ?? '') === '3')>3★ & above</option>
                <option value="2" @selected(($filters['ratingMin'] ?? '') === '2')>2★ & above</option>
            </select>
            <select name="datasheet" aria-label="Datasheet">
                <option value="">Any datasheet</option>
                <option value="1" @selected($filters['datasheet'] === '1')>Datasheet available</option>
            </select>
            <select name="package" aria-label="Package">
                <option value="">Any package</option>
                @foreach (($facetGroups['package'] ?? collect())->take(20) as $facet)
                    <option value="{{ $facet->facet_value }}" @selected($filters['package'] === $facet->facet_value)>{{ $facet->facet_value }} ({{ $facet->product_count }})</option>
                @endforeach
            </select>
            <select name="quality" aria-label="Data quality">
                <option value="">Any quality</option>
                <option value="high" @selected($filters['quality'] === 'high')>High quality indexed data</option>
                <option value="needs_review" @selected($filters['quality'] === 'needs_review')>Needs review</option>
            </select>
            <select name="sort" aria-label="Sort products">
                <option value="relevance" @selected($filters['sort'] === 'relevance')>Relevance</option>
                <option value="newest" @selected($filters['sort'] === 'newest')>Newest</option>
                <option value="stock" @selected($filters['sort'] === 'stock')>Stock</option>
                <option value="manufacturer" @selected($filters['sort'] === 'manufacturer')>Manufacturer</option>
                <option value="price" @selected($filters['sort'] === 'price')>Price</option>
            </select>
            <button class="btn btn-primary" type="submit"><x-icon name="search" size="16"/> Search</button>
        </form>
    </div>
    <div class="pmeta">
        Indexed search documents: {{ number_format($indexedSummary['searchable_documents'] ?? $indexedSummary['documents'] ?? 0) }} searchable / {{ number_format($indexedSummary['documents'] ?? 0) }} total.
        Imported catalog rows are searchable; SEO publication remains controlled separately.
    </div>
    @if(($facetGroups['manufacturer'] ?? collect())->isNotEmpty() || ($facetGroups['stock'] ?? collect())->isNotEmpty())
        <div class="facetbar" aria-label="Indexed catalog facets">
            @foreach (($facetGroups['manufacturer'] ?? collect())->take(8) as $facet)
                <a href="{{ request()->fullUrlWithQuery(['manufacturer' => $facet->facet_value]) }}">{{ $facet->facet_value }} <span class="pmeta">{{ $facet->product_count }}</span></a>
            @endforeach
            @foreach (($facetGroups['stock'] ?? collect())->take(3) as $facet)
                <a href="{{ request()->fullUrlWithQuery(['stock' => $facet->facet_value === 'in_stock' ? 'in' : 'out']) }}">{{ str_replace('_', ' ', $facet->facet_value) }} <span class="pmeta">{{ $facet->product_count }}</span></a>
            @endforeach
        </div>
    @endif

    @if ($products->count())
        <div class="pmeta">{{ number_format($products->total()) }} product(s)</div>
        <div class="pgrid">
            @foreach ($products as $p)
                @php
                    $cardImage = $p->images->first();
                    $cardBrandUrl = $p->brand ? $publicBase.'/brand/'.$p->brand->slug : null;
                    $cardCategoryUrl = $p->category ? $publicBase.'/categories/'.$p->category->slug : null;
                @endphp
                <div class="pcard">
                    <a class="pcard-media" href="{{ $publicBase }}/products/{{ $p->slug }}"><x-product-image-badges :product="$p" /><img src="{{ $cardImage?->publicUrl() ?: url('/images/products/neogiga-product-placeholder-2026.png') }}" alt="{{ $cardImage?->alt_text ?: trim(($p->manufacturer_name ?? '').' '.($p->mpn ?? '').' product image') }}" width="480" height="360" loading="lazy"></a>
                    @if($p->brand)<a class="ptag" href="{{ $cardBrandUrl }}">{{ $p->brand->name }}</a>@endif
                    <h2><a href="{{ $publicBase }}/products/{{ $p->slug }}">{{ $p->name }}</a></h2>
                    <div class="pmeta">
                        @if($p->mpn)MPN: <a class="mono" href="/mpn/{{ str_replace('/','--', urlencode($p->mpn)) }}">{{ $p->mpn }}</a> · @endif
                        @if($p->sku)SKU: <a class="mono" href="{{ $publicBase }}/products?q={{ urlencode($p->sku) }}">{{ $p->sku }}</a>@endif
                        @if($p->category) · <a href="{{ $cardCategoryUrl }}">{{ $p->category->name }}</a>@endif
                    </div>
                    @if($p->track_inventory)
                        <span class="pstock {{ $p->stock_quantity > 0 ? 'in' : 'out' }}">{{ $p->stock_quantity > 0 ? 'In stock' : 'Out of stock' }}</span>
                    @endif
                    <div style="margin-top:auto"><a class="btn btn-ghost" href="{{ $publicBase }}/products/{{ $p->slug }}"><x-icon name="view" size="16"/> View specs &amp; RFQ</a></div>
                </div>
            @endforeach
        </div>
        @if ($products->hasPages())<div style="margin:16px 0">{{ $products->links() }}</div>@endif
    @else
        <div class="pempty">
            <h2>No products match yet</h2>
            <p>The catalog is being loaded. Meanwhile, browse <a href="{{ $publicBase }}/categories">all categories</a> or <a href="{{ $publicBase }}/sell-on-neogiga">sell on NeoGiga</a>.</p>
        </div>
    @endif
</div>
@endsection
