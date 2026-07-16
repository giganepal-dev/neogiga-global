@extends('frontend.layout')
@section('title', 'Engineering Brands | NeoGiga Engineering Marketplace')
@section('description', 'Browse active engineering manufacturers and brands available through the NeoGiga global and regional marketplace.')

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => 'NeoGiga engineering brands',
    'numberOfItems' => $brands->total(),
    'itemListElement' => collect($brands->items())->values()->map(fn ($brand, $index) => [
        '@type' => 'ListItem',
        'position' => (($brands->currentPage() - 1) * $brands->perPage()) + $index + 1,
        'name' => $brand->name,
        'url' => url('/'.($marketplaceContext['locale'] ?? 'en').'/brand/'.$brand->slug),
    ])->all(),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
@php($publicBase = '/'.($marketplaceContext['locale'] ?? 'en'))
<div class="wrap section">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ $publicBase }}">Home</a><span><x-icon name="chevron-right" size="12"/></span><strong>Brands</strong></nav>
    <div class="section-head"><div><p class="eyebrow"><x-icon name="brands" size="14"/> Manufacturers and brands</p><h1 class="section-title">Engineering brands in {{ $marketplaceContext['current']->name ?? 'NeoGiga Global' }}</h1><p class="sub">Browse published brand identities independently of local stock or pricing.</p></div><span class="badge b-info">{{ number_format($brands->total()) }} brands</span></div>
    @if($brands->count())
        <div class="category-grid grid">
            @foreach($brands as $brand)
                <a class="category-card" href="{{ $publicBase }}/brand/{{ $brand->slug }}">
                    @if($brand->logo_path)<img src="{{ $brand->logo_path }}" alt="{{ $brand->name }} logo" width="160" height="64" loading="lazy" style="height:52px;width:100%;object-fit:contain;object-position:left center;margin-bottom:12px">@else<div class="cat-icon">{{ strtoupper(substr($brand->name, 0, 1)) }}</div>@endif
                    <h2 style="font-size:1.1rem">{{ $brand->name }}</h2>
                    <p class="sub">{{ $brand->short_description ?: \Illuminate\Support\Str::limit(strip_tags($brand->description ?: 'Explore products, specifications and RFQ sourcing.'), 130) }}</p>
                    <span class="badge b-muted">{{ number_format($brand->public_products_count) }} products</span>
                </a>
            @endforeach
        </div>
        @if($brands->hasPages())<div style="margin-top:24px">{{ $brands->links() }}</div>@endif
    @else
        <div class="panel" style="padding:32px"><h2 class="section-title">Brands are being configured</h2><p class="sub">Browse the catalog or request a sourced part through RFQ.</p><a class="btn btn-primary" href="{{ $publicBase }}/products">Browse products</a></div>
    @endif
</div>
@endsection
