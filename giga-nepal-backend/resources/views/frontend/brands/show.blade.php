@extends('frontend.layout')
@php
    $seoTitle = $seoTitle ?? ($brand->seo_meta['title'] ?? "Buy {$brand->name} Products on NeoGiga | NeoGiga Engineering Marketplace");
    $seoDescription = $seoDescription ?? ($brand->seo_meta['description'] ?? "Shop {$brand->name} electronic components, semiconductors, modules and engineering products on NeoGiga. Authorized distributors, competitive pricing and regional availability.");
@endphp
@section('title', $seoTitle)
@section('description', \Illuminate\Support\Str::limit(strip_tags($seoDescription), 158))

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        ['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>url($publicBase)],
        ['@type'=>'ListItem','position'=>2,'name'=>'Brands','item'=>url($publicBase.'/brands')],
        ['@type'=>'ListItem','position'=>3,'name'=>$brand->name,'item'=>url($publicBase.'/brands/'.$brand->slug)],
    ],
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@push('head')
@if($brand->logo_path)
<meta property="og:image" content="{{ asset($brand->logo_path) }}">
@endif
@endpush

@section('content')
<nav class="crumbs" aria-label="Breadcrumb">
    <a href="{{ $publicBase }}">Home</a><span>/</span>
    <a href="{{ url($publicBase.'/brands') }}">Brands</a><span>/</span>
    <strong style="color:var(--soft)">{{ $brand->name }}</strong>
</nav>

{{-- Brand header --}}
<div style="background:rgba(13,34,64,.55);border:1px solid var(--line);border-radius:var(--r);padding:24px;margin-bottom:24px">
    <div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap">
        @if($brand->logo_path)
            <img src="{{ asset($brand->logo_path) }}" alt="{{ $brand->name }}" style="width:100px;height:100px;object-fit:contain;background:#fff;border-radius:var(--r);padding:8px">
        @else
            <div style="width:100px;height:100px;display:grid;place-items:center;background:rgba(40,216,251,.08);border:1px solid rgba(40,216,251,.3);border-radius:var(--r);color:var(--cyan);font-weight:700;font-size:2rem">{{ substr($brand->name, 0, 2) }}</div>
        @endif
        <div style="flex:1;min-width:280px">
            <h1 style="margin:0 0 8px;font-size:1.8rem">{{ $brand->name }}</h1>
            @if($brand->country)
                <p style="color:var(--muted);margin:0 0 12px;font-size:.95rem">Origin: {{ $brand->country->name }}</p>
            @endif
            @if($brand->website_url)
                <a href="{{ $brand->website_url }}" target="_blank" rel="noopener" style="color:var(--cyan);font-size:.9rem;text-decoration:none">Visit official website →</a>
            @endif
            @if($brand->is_featured)
                <span style="display:inline-block;margin-top:12px;padding:6px 14px;background:rgba(249,189,44,.15);border:1px solid rgba(249,189,44,.4);border-radius:999px;font-size:.8rem;color:var(--gold);font-weight:600">Featured Brand</span>
            @endif
        </div>
    </div>
    @if($brand->description)
        <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--line)">
            <p style="color:var(--muted);line-height:1.6">{{ $brand->description }}</p>
        </div>
    @endif
</div>

{{-- Products grid --}}
<h2 style="font-size:1.2rem;margin:0 0 16px;color:var(--soft)">Products by {{ $brand->name }}</h2>

@if ($products->isEmpty())
    <div style="background:rgba(13,34,64,.5);border:1px solid var(--line);border-radius:var(--r);padding:40px 22px;text-align:center;color:var(--muted)">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="none" style="opacity:.6;margin-bottom:8px"><path d="M21 8l-9-5-9 5 9 5 9-5zM3 8v8l9 5 9-5V8" stroke="#5b7a99" stroke-width="1.4" stroke-linejoin="round"/></svg>
        <h3 style="color:var(--soft);margin:0 0 4px">No products available yet</h3>
        <p style="margin:0 auto;max-width:44ch">Products from {{ $brand->name }} are being onboarded. Check back soon or contact us for sourcing requests.</p>
        <a href="/{{ $activePrefix }}/rfq" class="btn btn-primary" style="margin-top:16px">Request a Quote</a>
    </div>
@else
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px">
        @foreach ($products as $p)
            <article class="product-card" style="background:rgba(13,34,64,.55);border:1px solid var(--line);border-radius:var(--r);padding:16px;transition:transform .15s ease,border-color .15s ease">
                <a href="{{ url($publicBase.'/products/'.$p->slug) }}" style="text-decoration:none;color:inherit;display:block">
                    @if($p->primaryImage)
                        <img src="{{ asset($p->primaryImage->image_path) }}" alt="{{ $p->name }}" style="width:100%;aspect-ratio:1;object-fit:cover;border-radius:calc(var(--r) - 2px);margin-bottom:12px;background:rgba(13,34,64,.8)">
                    @else
                        <div style="width:100%;aspect-ratio:1;display:grid;place-items:center;background:rgba(40,216,251,.06);border:1px solid var(--line);border-radius:calc(var(--r) - 2px);margin-bottom:12px;color:var(--cyan);font-weight:600">{{ substr($p->name, 0, 2) }}</div>
                    @endif
                    <h3 style="font-size:1rem;margin:0 0 6px;line-clamp:2;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">{{ $p->name }}</h3>
                    @if($p->mpn)
                        <p style="font-size:.8rem;color:var(--muted);margin:0 0 8px">MPN: {{ $p->mpn }}</p>
                    @endif
                    @if($p->category)
                        <span style="display:inline-block;padding:4px 10px;background:rgba(40,216,251,.08);border:1px solid rgba(40,216,251,.25);border-radius:999px;font-size:.75rem;color:var(--cyan)">{{ $p->category->name }}</span>
                    @endif
                </a>
            </article>
        @endforeach
    </div>

    {{-- Pagination --}}
    @if ($products->hasPages())
        <div style="margin-top:32px;display:flex;justify-content:center">
            {{ $products->links('vendor.pagination.tailwind') }}
        </div>
    @endif
@endif

{{-- Related brands --}}
@php
    $relatedBrands = \App\Models\Marketplace\ProductBrand::query()
        ->where('id', '!=', $brand->id)
        ->where('is_active', true)
        ->whereNotNull('country_id')
        ->where('country_id', $brand->country_id ?? 0)
        ->orderBy('is_featured', 'desc')
        ->limit(4)
        ->get();
@endphp
@if($relatedBrands->isNotEmpty())
    <h2 style="font-size:1.1rem;margin:32px 0 16px;color:var(--soft)">Other brands from {{ $brand->country?->name ?? 'this region' }}</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">
        @foreach($relatedBrands as $rb)
            <a href="{{ url($publicBase.'/brands/'.$rb->slug) }}" style="display:flex;align-items:center;gap:12px;padding:12px;background:rgba(13,34,64,.4);border:1px solid var(--line);border-radius:var(--r);text-decoration:none;color:inherit">
                @if($rb->logo_path)
                    <img src="{{ asset($rb->logo_path) }}" alt="{{ $rb->name }}" style="width:40px;height:40px;object-fit:contain;border-radius:6px">
                @else
                    <div style="width:40px;height:40px;display:grid;place-items:center;background:rgba(40,216,251,.08);border:1px solid rgba(40,216,251,.25);border-radius:6px;color:var(--cyan);font-weight:600;font-size:.85rem">{{ substr($rb->name, 0, 2) }}</div>
                @endif
                <span style="font-size:.9rem;font-weight:500">{{ $rb->name }}</span>
            </a>
        @endforeach
    </div>
@endif
@endsection
