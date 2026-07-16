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
@endphp
@section('title', $metaTitle)
@section('description', \Illuminate\Support\Str::limit(strip_tags($metaDesc), 158))

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => collect($breadcrumb)->values()->map(fn($b,$i)=>[
        '@type'=>'ListItem','position'=>$i+1,'name'=>$b['name'],'item'=>$canonicalOrigin.$b['url'],
    ])->all(),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
<script type="application/ld+json">
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
<script type="application/ld+json">
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
<nav class="crumbs" aria-label="Breadcrumb">
    @foreach ($breadcrumb as $i => $b)
        @if ($i === count($breadcrumb)-1)
            <strong style="color:var(--soft)">{{ $b['name'] }}</strong>
        @else
            <a href="{{ $b['url'] }}">{{ $b['name'] }}</a><span><x-icon name="chevron-right" size="12"/></span>
        @endif
    @endforeach
</nav>

<div class="category-hero" style="display:flex;align-items:center;gap:18px;margin-bottom:12px">
    <img src="{{ $category->image_path ?: url('/images/brand/neogiga-icon-512.png') }}" alt="{{ $category->name }} category" width="112" height="112" loading="lazy" style="width:112px;height:112px;object-fit:contain;border:1px solid var(--line);border-radius:12px;background:#081527;flex:none">
    <div><h1 style="margin:0 0 8px">{{ $category->name }}</h1><p class="lead" style="margin:0">{{ $metaDesc }}</p></div>
</div>

@if ($children->isNotEmpty())
    <h2 style="font-size:1.05rem;margin:8px 0 12px;color:var(--soft)">Subcategories</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-bottom:34px">
        @foreach ($children as $child)
            <a href="{{ $publicBase }}/categories/{{ $child->slug }}" style="display:flex;align-items:center;justify-content:space-between;gap:10px;background:rgba(13,34,64,.5);border:1px solid var(--line);border-radius:10px;padding:14px 16px">
                <span>{{ $child->name }}</span>
                <span aria-hidden="true" style="color:var(--cyan)"><x-icon name="chevron-right" size="16"/></span>
            </a>
        @endforeach
    </div>
@endif

<h2 style="font-size:1.05rem;margin:8px 0 12px;color:var(--soft)">Products in {{ $category->name }}</h2>
@if ($products->isEmpty())
    <div style="background:rgba(13,34,64,.5);border:1px solid var(--line);border-radius:var(--r);padding:40px 22px;text-align:center;color:var(--muted)">
        <x-icon name="products" size="40" style="opacity:.6;margin-bottom:8px;color:#5b7a99"/>
        <h3 style="color:var(--soft);margin:0 0 4px">Catalog coming soon</h3>
        <p style="margin:0 auto;max-width:44ch">Products for this category are being onboarded. Meanwhile, try the <a href="{{ $publicBase }}/#ai" style="color:var(--cyan)">AI BOM Builder</a> or <a href="{{ $publicBase }}/#vendors" style="color:var(--cyan)">list your products</a> as a vendor.</p>
    </div>
@else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px">
        @foreach ($products as $p)
            @php($cardImage = $p->images->first())
            <article style="background:rgba(13,34,64,.55);border:1px solid var(--line);border-radius:var(--r);padding:16px">
                <a href="{{ $publicBase }}/products/{{ $p->slug }}"><img src="{{ $cardImage?->publicUrl() ?: url('/images/products/neogiga-product-placeholder-2026.png') }}" alt="{{ $cardImage?->alt_text ?: $p->name.' product image' }}" width="480" height="360" loading="lazy" style="display:block;width:100%;aspect-ratio:4/3;object-fit:contain;background:#081527;border-radius:9px;margin-bottom:12px"><strong>{{ $p->name }}</strong></a>
                <div style="color:var(--muted);font-size:.82rem" class="mono">{{ $p->sku }}</div>
            </article>
        @endforeach
    </div>
@endif
@endsection
