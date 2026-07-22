@extends('frontend.layout')
@php
    $seo = $category->seo_meta ?? [];
    $metaTitle = $pageSeo['title'] ?? ($seo['title'] ?? ($category->name.' — NeoGiga'));
    $metaDesc = $pageSeo['description'] ?? ($seo['description'] ?? ('Shop '.$category->name.' on NeoGiga — genuine parts, regional stock and engineering support.'));
    $categoryCanonical = $pageSeo['canonical'] ?? ($marketplaceSeo['canonical'] ?? url()->current());
    $canonicalParts = parse_url($categoryCanonical);
    $canonicalOrigin = ($canonicalParts['scheme'] ?? 'https').'://'.($canonicalParts['host'] ?? request()->getHost());
    $activePrefix = strtolower((string) request()->segment(1));
    $activePrefix = array_key_exists($activePrefix, config('neogiga_global.prefixes', []))
        ? $activePrefix
        : config('neogiga_global.default_prefix', 'en');
    $publicBase = '/'.$activePrefix;

    $sortOptions = [
        'newest'      => 'Newest',
        'price_asc'   => 'Price: Low → High',
        'price_desc'  => 'Price: High → Low',
        'name_asc'    => 'Name: A → Z',
        'name_desc'   => 'Name: Z → A',
        'rating_desc' => 'Top Rated',
    ];
    $stockOptions = [
        ''             => 'All Stock',
        'global'       => 'Global',
        'local'        => 'Local Stock',
        'out_of_stock' => 'Out of Stock',
    ];
@endphp
@section('title', $metaTitle)
@section('description', \Illuminate\Support\Str::limit(strip_tags($metaDesc), 158))

@push('head')
<script nonce="{{ $csp_nonce ?? '' }}" type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => collect($breadcrumb)->values()->map(fn($b,$i)=>[
        '@type'=>'ListItem','position'=>$i+1,'name'=>$b['name'],'item'=>$canonicalOrigin.$b['url'],
    ])->all(),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
<script nonce="{{ $csp_nonce ?? '' }}" type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => $category->name.' products',
    'url' => $categoryCanonical,
    'numberOfItems' => $products->count(),
    'itemListElement' => $products->values()->map(fn ($product, $index) => [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'url' => $canonicalOrigin.$publicBase.'/products/'.$product->slug,
        'name' => $product->name,
    ])->all(),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
@if ($children->isNotEmpty())
<script nonce="{{ $csp_nonce ?? '' }}" type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => $category->name.' subcategories',
    'numberOfItems' => $children->count(),
    'itemListElement' => $children->values()->map(fn($child,$index)=>[
        '@type'=>'ListItem','position'=>$index+1,'name'=>$child->name,
        'url'=>$canonicalOrigin.$publicBase.'/categories/'.$child->slug,
    ])->all(),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
@endif
@endpush

@section('content')
<style nonce="{{ $csp_nonce ?? '' }}">
    .category-show{padding-bottom:64px}
    .category-hero{display:flex;align-items:center;gap:18px;margin:8px 0 22px;padding:22px;background:var(--s1);border:1px solid var(--line);border-radius:var(--r)}
    .category-hero img{width:104px;height:104px;object-fit:contain;border:1px solid var(--line);border-radius:12px;background:transparent;flex:none}
    .category-hero h1{margin:0 0 6px;font-size:clamp(1.8rem,4vw,2.7rem);letter-spacing:-.025em}.category-hero .lead{margin:0}
    .category-section-title{display:flex;align-items:center;gap:8px;font-size:1.05rem;margin:20px 0 12px;color:var(--on)}
    .subcategory-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-bottom:30px}
    .subcategory-card{display:flex;align-items:center;justify-content:space-between;gap:10px;background:var(--s1);border:1px solid var(--line);border-radius:10px;padding:14px 16px;transition:border-color .18s,transform .18s,box-shadow .18s}
    .subcategory-card:hover{border-color:var(--cyan);transform:translateY(-1px);box-shadow:0 10px 24px rgba(23,43,77,.08)}.subcategory-card svg{color:var(--cyan)}
    .category-search{display:flex;gap:8px;margin:0 0 12px}.category-search input{flex:1;min-width:200px;padding:9px 12px;border:1px solid var(--line);border-radius:8px;background:var(--s1);color:var(--on)}
    .category-toolbar{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin:8px 0 14px}.category-toolbar-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap}.stock-tabs{display:flex;border:1px solid var(--line);border-radius:8px;overflow:hidden;background:var(--s1)}.stock-tabs a{padding:7px 12px;font-size:.78rem;color:var(--muted)}.stock-tabs a.active{background:var(--cyan);color:#fff;font-weight:700}
    .category-sort{width:auto;min-height:36px;font-size:.78rem;padding:6px 10px}
    .category-products{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px}.category-product{background:var(--s1);border:1px solid var(--line);border-radius:var(--r);padding:14px;content-visibility:auto;contain-intrinsic-size:330px;transition:border-color .18s,transform .18s,box-shadow .18s}.category-product:hover{border-color:rgba(15,98,230,.42);transform:translateY(-2px);box-shadow:0 14px 34px rgba(23,43,77,.1)}
    .category-product-media{position:relative;display:block;aspect-ratio:4/3;background:var(--s2);border:1px solid var(--line);border-radius:9px;margin-bottom:10px;overflow:hidden}.category-product-media img{width:100%;height:100%;object-fit:contain;background:transparent}.category-product strong{display:block;line-height:1.35}.category-product-meta{color:var(--muted);font-size:.78rem;margin-top:7px;overflow-wrap:anywhere}
    @media(max-width:620px){.category-hero{align-items:flex-start;padding:16px}.category-hero img{width:76px;height:76px}.category-search{display:grid;grid-template-columns:1fr 1fr}.category-search input{grid-column:1/-1}.category-toolbar{align-items:flex-start}.stock-tabs{max-width:100%;overflow-x:auto}.category-products{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:430px){.category-hero{display:grid}.category-products,.subcategory-grid{grid-template-columns:1fr}}
</style>
<div class="wrap category-show">
<nav class="crumbs" aria-label="Breadcrumb">
    @foreach ($breadcrumb as $i => $b)
        @if ($i === count($breadcrumb)-1)
            <strong style="color:var(--soft)">{{ $b['name'] }}</strong>
        @else
            <a href="{{ $b['url'] }}">{{ $b['name'] }}</a><span><x-icon name="chevron-right" size="12"/></span>
        @endif
    @endforeach
</nav>

<div class="category-hero">
    <img src="{{ $category->image_path ?: url('/images/brand/neogiga-icon-512.png') }}" alt="{{ $category->name }} category" width="104" height="104" loading="eager" fetchpriority="high">
    <div><h1>{{ $category->name }}</h1><p class="lead">{{ $metaDesc }}</p></div>
</div>

@if ($children->isNotEmpty())
    <h2 class="category-section-title">Subcategories <span class="badge b-muted">{{ number_format($children->count()) }}</span></h2>
    <div class="subcategory-grid">
        @foreach ($children as $child)
            <a class="subcategory-card" href="{{ $publicBase }}/categories/{{ $child->slug }}">
                <span>{{ $child->name }}</span>
                <span aria-hidden="true"><x-icon name="chevron-right" size="16"/></span>
            </a>
        @endforeach
    </div>
@endif

{{-- Search within category --}}
<form class="category-search" method="get" action="{{ request()->url() }}">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search within {{ $category->name }}" aria-label="Search within {{ $category->name }}">
    <button type="submit" class="btn btn-ghost btn-sm">Search</button>
    @if(request('q'))<a href="{{ request()->url() }}" class="btn btn-ghost btn-sm">Clear</a>@endif
</form>

{{-- Filter bar: stock tabs + sort dropdown --}}
<div class="category-toolbar">
    <div style="display:flex;align-items:center;gap:8px">
        <h2 class="category-section-title" style="margin:0">Products</h2>
        <span class="badge b-info" style="font-size:.75rem">{{ number_format($inclusiveCount) }}</span>
    </div>
    <div class="category-toolbar-actions">
        {{-- Stock filter pills --}}
        <div class="stock-tabs">
            @foreach ($stockOptions as $val => $label)
                <a href="{{ request()->fullUrlWithQuery(['stock' => $val ?: null, 'page' => null]) }}"
                   class="{{ ($currentStock ?? '') === $val ? 'active' : '' }}"
                >{{ $label }}</a>
            @endforeach
        </div>
        {{-- Sort dropdown --}}
        <select class="control category-sort" onchange="location=this.value">
            @foreach ($sortOptions as $val => $label)
                <option value="{{ request()->fullUrlWithQuery(['sort' => $val, 'page' => null]) }}" @selected(($currentSort ?? 'newest') === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
</div>

@if ($products->isEmpty())
    <div style="background:rgba(13,34,64,.5);border:1px solid var(--line);border-radius:var(--r);padding:40px 22px;text-align:center;color:var(--muted)">
        <x-icon name="products" size="40" style="opacity:.6;margin-bottom:8px;color:#5b7a99"/>
        <h3 style="color:var(--soft);margin:0 0 4px">No products match</h3>
        <p style="margin:0 auto;max-width:44ch">No products found for the selected filters. Try adjusting stock or sort options.</p>
    </div>
@else
    <div class="category-products">
        @foreach ($products as $p)
            @php($cardImage = $p->images->first())
            <article class="category-product">
                <a class="category-product-media" href="{{ $publicBase }}/products/{{ $p->slug }}"><x-product-image-badges :product="$p" /><img src="{{ $cardImage?->publicUrl() ?: url('/images/products/neogiga-product-placeholder-2026.png') }}" @if($cardImage?->srcset()) srcset="{{ $cardImage->srcset() }}" sizes="(max-width: 480px) 50vw, (max-width: 768px) 33vw, 20vw" @endif alt="{{ $cardImage?->alt_text ?: $p->name.' product image' }}" width="480" height="360" loading="lazy" decoding="async"></a>
                <a href="{{ $publicBase }}/products/{{ $p->slug }}"><strong>{{ $p->name }}</strong></a>
                <div class="category-product-meta mono">@if($p->sku)<a href="{{ $publicBase }}/products?q={{ urlencode($p->sku) }}">{{ $p->sku }}</a>@endif @if($p->mpn)· <a href="/mpn/{{ str_replace('/','--', urlencode($p->mpn)) }}">{{ $p->mpn }}</a>@endif</div>
                <x-product-certification-marks />
            </article>
        @endforeach
    </div>

    {{-- Pagination --}}
    @if ($products->hasPages())
        <div style="display:flex;justify-content:center;margin-top:24px">
            {{ $products->appends(request()->except('page'))->links() }}
        </div>
    @endif
@endif
</div>
@endsection
