@extends('frontend.layout')
@section('title', $pageSeo['title'])
@section('description', $pageSeo['description'])

@push('head')
<script nonce="{{ $csp_nonce ?? '' }}" type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Brand',
    'name' => $brand->name,
    'url' => $pageSeo['canonical'],
    'logo' => $brand->logo_path,
    'description' => strip_tags($brand->description ?: $brand->short_description ?: $pageSeo['description']),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
@php($publicBase = '/'.($marketplaceContext['locale'] ?? 'en'))
<div class="wrap section">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ $publicBase }}">Home</a><span>/</span><a href="{{ $publicBase }}/brands">Brands</a><span>/</span><strong>{{ $brand->name }}</strong></nav>
    <section class="panel" style="padding:18px 20px;margin:16px 0 20px">
        @if($brand->banner_path)<img src="{{ $brand->banner_path }}" alt="{{ $brand->name }} engineering products" width="1200" height="200" style="width:100%;max-height:150px;object-fit:cover;border-radius:10px;margin-bottom:14px">@endif
        <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
            @if($brand->logo_path)<img src="{{ $brand->logo_path }}" alt="{{ $brand->name }} logo" width="120" height="56" style="max-width:120px;max-height:56px;object-fit:contain;background:#fff;border-radius:8px;padding:6px">@else<div class="cat-icon" style="width:52px;height:52px;font-size:1.15rem">{{ strtoupper(substr($brand->name, 0, 1)) }}</div>@endif
            <div style="flex:1;min-width:240px">
                <h1 class="section-title" style="font-size:clamp(1.25rem,2.6vw,1.6rem);margin:0 0 4px">{{ $brand->name }}</h1>
                <p class="sub" style="margin:0 0 8px;font-size:.9rem">{{ \Illuminate\Support\Str::limit(strip_tags($brand->description ?: $brand->short_description ?: $pageSeo['description']), 160) }}</p>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                    <span class="badge b-info">{{ number_format($brandProductTotal) }} products</span>
                    @if($brand->manufacturer)<a class="badge b-muted" href="{{ $publicBase }}/manufacturer/{{ $brand->manufacturer->slug }}">{{ $brand->manufacturer->name }}</a>@endif
                    @if($brand->website_url)<a class="badge b-muted" href="{{ $brand->website_url }}" rel="nofollow noopener" target="_blank">Official website</a>@endif
                </div>
            </div>
        </div>
    </section>

    @if($categories->isNotEmpty())<div class="section-head"><div><p class="eyebrow">Categories</p><h2>Product categories</h2></div></div><div class="category-grid grid" style="margin-bottom:34px">@foreach($categories as $category)<a class="category-card" href="{{ $publicBase }}/categories/{{ $category->slug }}"><h3>{{ $category->name }}</h3><span class="badge b-muted">{{ number_format($category->products_count) }} products</span></a>@endforeach</div>@endif

    <form method="get" action="{{ $publicBase }}/brand/{{ $brand->slug }}" style="display:flex;gap:8px;margin:0 0 14px;flex-wrap:wrap;align-items:center">
        <input type="search" name="q" value="{{ $productSearch }}" placeholder="Search within {{ $brand->name }}" aria-label="Search within {{ $brand->name }}" style="flex:1;min-width:200px;max-width:420px;padding:9px 12px;border:1px solid var(--line);border-radius:8px;background:var(--s1);color:var(--on);font:inherit">
        @if($categories->isNotEmpty())
        <select name="category" aria-label="Filter by category" style="padding:9px 10px;border:1px solid var(--line);border-radius:8px;background:var(--s1);color:var(--on);font:inherit">
            <option value="">All categories</option>
            @foreach($categories as $category)<option value="{{ $category->id }}" @selected($productCategory === $category->id)>{{ $category->name }} ({{ $category->products_count }})</option>@endforeach
        </select>
        @endif
        <select name="sort" aria-label="Sort products" style="padding:9px 10px;border:1px solid var(--line);border-radius:8px;background:var(--s1);color:var(--on);font:inherit">
            <option value="featured" @selected($productSort==='featured')>Featured</option>
            <option value="name" @selected($productSort==='name')>Name A–Z</option>
            <option value="newest" @selected($productSort==='newest')>Newest</option>
        </select>
        <button type="submit" class="btn btn-ghost btn-sm">Apply</button>
        @if($productSearch !== '' || $productCategory > 0 || $productSort !== 'featured')<a class="btn btn-ghost btn-sm" href="{{ $publicBase }}/brand/{{ $brand->slug }}">Reset</a>@endif
    </form>
    <div class="section-head"><div><p class="eyebrow">Catalog</p><h2>{{ $brand->name }} products</h2></div><a class="btn btn-ghost" href="{{ $publicBase }}/products?brand_id={{ $brand->id }}">Open filtered catalog</a></div>
    @if($products->count())
        <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(230px,1fr))">
            @foreach($products as $product)
                @php($image = $product->images->firstWhere('is_primary', true) ?: $product->images->first())
                <article class="product-card"><a href="{{ $publicBase }}/products/{{ $product->slug }}"><div class="product-img"><x-product-image-badges :product="$product" /><img src="{{ $image?->publicUrl() ?: url('/images/products/neogiga-product-placeholder-2026.png') }}" alt="{{ $image?->alt_text ?: $product->name }}" width="480" height="360" loading="lazy" decoding="async" style="width:100%;height:100%;object-fit:contain;background:transparent"></div><h3>{{ $product->name }}</h3></a><p class="sub">@if($product->mpn)<a href="/mpn/{{ str_replace('/','--', urlencode($product->mpn)) }}">{{ $product->mpn }}</a>@else<a href="{{ $publicBase }}/products?q={{ urlencode($product->sku) }}">{{ $product->sku }}</a>@endif @if($product->category) · <a href="{{ $publicBase }}/categories/{{ $product->category->slug }}">{{ $product->category->name }}</a>@endif</p><x-product-certification-marks /><a class="btn btn-ghost" href="{{ $publicBase }}/products/{{ $product->slug }}">View product</a></article>
            @endforeach
        </div>
        @if($products->hasPages())<div style="margin-top:24px">{{ $products->links() }}</div>@endif
    @else
        <div class="panel" style="padding:28px">
            @if($productSearch !== '' || $productCategory > 0)
                <h3>No products match these filters</h3><p class="sub">Try a different search term or category.</p><a class="btn btn-primary" href="{{ $publicBase }}/brand/{{ $brand->slug }}">Reset filters</a>
            @else
                <h3>Brand page available</h3><p class="sub">No public products are currently listed for this brand. Regional stock, price, or RFQ availability does not affect this brand identity page.</p><a class="btn btn-primary" href="{{ $publicBase }}/rfq">Request a {{ $brand->name }} part</a>
            @endif
        </div>
    @endif

    <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr));margin-top:34px">
        <section class="info-card"><h2>Regional availability</h2>@forelse($regionalAvailability as $row)<p><strong>{{ $row->name ?: 'Regional network' }}</strong><br><span class="sub">{{ number_format($row->product_count) }} products · {{ number_format($row->available_quantity) }} units</span></p>@empty<p class="sub">Use RFQ for global or regional availability. A missing local stock row does not make this brand unavailable.</p>@endforelse</section>
        <section class="info-card"><h2>Technical resources</h2>@forelse($technicalResources as $resource)<p><a href="{{ $resource->file_url ?: $resource->source_url }}" rel="nofollow"><strong>{{ $resource->title }}</strong></a><br><span class="sub">{{ $resource->product_name }} · {{ $resource->document_type }}</span></p>@empty<p class="sub">Datasheets and technical resources will appear as verified files are linked.</p>@endforelse</section>
        <section class="info-card"><h2>Source and verification</h2><p class="sub">Data source: NeoGiga catalog identity{{ $brand->manufacturer ? ' / '.$brand->manufacturer->name : '' }}.</p>@if($brand->website_url)<details><summary style="cursor:pointer;color:var(--cyan)">References</summary><p><a href="{{ $brand->website_url }}" rel="nofollow noopener" target="_blank">Manufacturer website</a></p></details>@endif<p class="sub">{{ $pageSeo['source_notes'] }} · confidence: {{ $pageSeo['confidence_level'] }} · updated {{ $pageSeo['last_updated'] }} · Advisory only</p></section>
    </div>
</div>
@endsection
