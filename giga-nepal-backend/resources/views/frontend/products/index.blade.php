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
<script nonce="{{ $csp_nonce ?? '' }}" type="application/ld+json">
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
<script nonce="{{ $csp_nonce ?? '' }}" type="application/ld+json">
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
<style nonce="{{ $csp_nonce ?? '' }}">
    .plist-head{display:grid;gap:14px;margin:24px 0 16px}
    .plist-title{display:flex;align-items:end;justify-content:space-between;gap:16px;flex-wrap:wrap}
    .plist-title h1{margin:0;font-size:clamp(1.65rem,3vw,2.35rem);letter-spacing:-.025em}
    .plist-title p{margin:4px 0 0;color:var(--muted)}
    .psearch{display:grid;grid-template-columns:minmax(260px,2fr) minmax(180px,1fr) minmax(180px,1fr) minmax(150px,.8fr) auto;gap:10px;width:100%;padding:14px;background:var(--s1);border:1px solid var(--line);border-radius:var(--r);box-shadow:0 8px 24px rgba(23,43,77,.05)}
    .psearch input,.psearch select{width:100%;min-width:0;min-height:44px;padding:10px 12px;border:1px solid #c7d4e6;border-radius:9px;background:var(--s1);color:var(--on);font:inherit}
    .psearch input:focus,.psearch select:focus{outline:0;border-color:var(--cyan);box-shadow:0 0 0 3px rgba(15,98,230,.12)}
    .psearch button{min-height:44px;white-space:nowrap}
    .pfilter-advanced{grid-column:1/-1;border-top:1px solid var(--line);padding-top:10px}
    .pfilter-advanced summary{display:flex;align-items:center;justify-content:space-between;gap:12px;cursor:pointer;color:var(--cyan);font-size:.84rem;font-weight:700;list-style:none}
    .pfilter-advanced summary::-webkit-details-marker{display:none}.pfilter-advanced summary::after{content:'+';font-size:1.15rem}.pfilter-advanced[open] summary::after{content:'−'}
    .pfilter-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;padding-top:12px}
    .pfilter-actions{display:flex;justify-content:flex-end;gap:8px;align-items:center}
    .pfilter-clear{font-size:.82rem;color:var(--muted)}.pfilter-clear:hover{color:var(--cyan)}
    .facetbar{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 16px}
    .facetbar a{border:1px solid rgba(15,23,42,.14);border-radius:999px;padding:6px 10px;text-decoration:none;color:#0f172a;background:#fff;font-size:.82rem}
    .facetbar a:hover{border-color:#06b6d4;color:#075985}
    .pgrid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:16px;margin:16px 0 32px}
    @media(max-width:980px){.pgrid{grid-template-columns:repeat(auto-fill,minmax(220px,1fr))}}
    @media(max-width:620px){.pgrid{grid-template-columns:repeat(auto-fill,minmax(180px,1fr))}}
    @media(max-width:430px){.pgrid{grid-template-columns:1fr}}
    .pcard{border:1px solid var(--line);border-radius:12px;padding:14px;background:var(--s1);display:flex;flex-direction:column;gap:9px;min-width:0;content-visibility:auto;contain-intrinsic-size:360px;transition:border-color .18s,box-shadow .18s,transform .18s}
    .pcard:hover{border-color:rgba(15,98,230,.42);box-shadow:0 14px 34px rgba(23,43,77,.11);transform:translateY(-2px)}
    .pcard-media{position:relative;display:block;aspect-ratio:4/3;margin:0 0 3px;border-radius:9px;overflow:hidden;background:var(--s2);border:1px solid var(--line)}
    .pcard-media img{display:block;width:100%;height:100%;object-fit:contain}
    .pcard h2{font-size:1rem;margin:0;line-height:1.35}
    .pcard a{color:var(--on);text-decoration:none}.pcard a:hover{color:var(--cyan)}
    .pmeta{color:var(--muted);font-size:.82rem}.pcard .pmeta a{color:var(--muted)}.pcard .pmeta a:hover{color:var(--cyan)}
    .ptag,.pcard .ptag{display:inline-block;background:#ECFEFF;color:#155E75;border-radius:999px;padding:2px 10px;font-size:.75rem;font-weight:700}
    .pcard .btn-ghost{color:var(--on);border-color:var(--line);background:var(--s1)}.pcard .btn-ghost:hover{color:var(--cyan);border-color:var(--cyan)}
    .pstock{font-size:.8rem;font-weight:700}.in{color:#10b981}.out{color:#f87171}
    .pempty{border:1px dashed rgba(15,23,42,.2);border-radius:12px;padding:48px;text-align:center;color:var(--muted);margin:24px 0}
    @media(max-width:1080px){.psearch{grid-template-columns:2fr 1fr 1fr}.psearch>.btn{width:auto}.pfilter-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
    @media(max-width:760px){.psearch{grid-template-columns:1fr 1fr}.psearch>input:first-child{grid-column:1/-1}.pfilter-grid{grid-template-columns:1fr 1fr}.psearch>.btn{width:100%}}
    @media(max-width:520px){.psearch,.pfilter-grid{grid-template-columns:1fr}.psearch>input:first-child{grid-column:auto}.plist-title{align-items:start}.pfilter-actions{justify-content:stretch}.pfilter-actions>*{flex:1}}
</style>

<div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ $publicBase }}">Home</a> › <span>Products</span>@if($category) › <span>{{ $category->name }}</span>@endif</nav>

    <div class="plist-head">
        <div class="plist-title">
            <div><h1>{{ $category->name ?? 'All Products' }}</h1><p>Find parts by MPN, technical attributes, regional stock, or manufacturer.</p></div>
            <span class="badge b-info">{{ number_format($products->total()) }} results</span>
        </div>
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
            <select name="sort" aria-label="Sort products">
                <option value="relevance" @selected($filters['sort'] === 'relevance')>Relevance</option>
                <option value="newest" @selected($filters['sort'] === 'newest')>Newest</option>
                <option value="stock" @selected($filters['sort'] === 'stock')>Stock</option>
                <option value="manufacturer" @selected($filters['sort'] === 'manufacturer')>Manufacturer</option>
                <option value="price" @selected($filters['sort'] === 'price')>Price</option>
            </select>
            <button class="btn btn-primary" type="submit"><x-icon name="search" size="16"/> Search</button>
            @php
                $advancedFiltersActive = collect(request()->only([
                    'manufacturer', 'stock', 'country_id', 'price_min', 'price_max',
                    'rating_min', 'datasheet', 'package', 'quality',
                ]))->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
            @endphp
            <details class="pfilter-advanced" @if($advancedFiltersActive) open @endif>
                <summary>Advanced filters <span class="pmeta">Stock, region, price, rating, package, and data quality</span></summary>
                <div class="pfilter-grid">
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
                    <select name="rating_min" aria-label="Minimum rating">
                        <option value="">Any rating</option>
                        <option value="4" @selected(($filters['ratingMin'] ?? 0) == 4)>4★ & above</option>
                        <option value="3" @selected(($filters['ratingMin'] ?? 0) == 3)>3★ & above</option>
                        <option value="2" @selected(($filters['ratingMin'] ?? 0) == 2)>2★ & above</option>
                    </select>
                    <input type="number" name="price_min" value="{{ ($filters['priceMin'] ?? 0) > 0 ? $filters['priceMin'] : '' }}" placeholder="Minimum price" step="0.01" min="0">
                    <input type="number" name="price_max" value="{{ ($filters['priceMax'] ?? 0) > 0 ? $filters['priceMax'] : '' }}" placeholder="Maximum price" step="0.01" min="0">
                    <select name="datasheet" aria-label="Datasheet"><option value="">Any datasheet</option><option value="1" @selected($filters['datasheet'] === '1')>Datasheet available</option></select>
                    <select name="package" aria-label="Package">
                        <option value="">Any package</option>
                        @foreach (($facetGroups['package'] ?? collect())->take(20) as $facet)
                            <option value="{{ $facet->facet_value }}" @selected($filters['package'] === $facet->facet_value)>{{ $facet->facet_value }} ({{ $facet->product_count }})</option>
                        @endforeach
                    </select>
                    <select name="quality" aria-label="Data quality"><option value="">Any quality</option><option value="high" @selected($filters['quality'] === 'high')>High quality indexed data</option><option value="needs_review" @selected($filters['quality'] === 'needs_review')>Needs review</option></select>
                    <div class="pfilter-actions">
                        @if(request()->query())<a class="pfilter-clear" href="{{ $publicBase }}/products">Clear all</a>@endif
                        <button class="btn btn-ghost" type="submit">Apply filters</button>
                    </div>
                </div>
            </details>
        </form>
    </div>
    <div class="pmeta">
        {{ number_format(($catalogTotal ?? 0) ?: ($indexedSummary['documents'] ?? 0)) }} products indexed and searchable across the NeoGiga catalog.
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
        <div class="pmeta">{{ number_format($products->total()) }} products available</div>
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
