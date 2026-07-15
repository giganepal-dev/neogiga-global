@extends('frontend.layout')
@section('title', $copy['title'].' | NeoGiga')
@section('description', \Illuminate\Support\Str::limit($copy['description'], 158))
@push('head')
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}</script>
@endpush
@section('content')
<section class="hero" style="padding:40px 0">
    <div class="wrap">
        <nav class="crumbs"><a href="/">Home</a><span>/</span><strong>{{ $name }}</strong></nav>
        <p class="eyebrow">{{ $copy['eyebrow'] }}</p>
        <h1 class="page-title" style="font-size:clamp(2rem,5vw,4.8rem)">{{ $name }}</h1>
        <p>{{ $copy['description'] }}</p>
        <form class="search hero-search" method="get" action="/products">
            <select aria-label="Search type"><option>{{ ucfirst($type) }}</option></select>
            <input name="q" value="{{ $type === 'mpn' ? $name : '' }}" placeholder="Search products, MPN, SKU, datasheet or project">
            <button type="submit">Search</button>
        </form>
        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:14px">
            <a class="btn btn-primary" href="/products?q={{ urlencode($name) }}">Explore Products</a>
            <a class="btn btn-gold" href="/rfq?message={{ urlencode('Source '.$name) }}">Create RFQ</a>
            <a class="btn btn-dark" href="/ai-commerce?prompt={{ urlencode('Build with '.$name) }}">Ask AI</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap layout-2">
        <aside class="panel filter">
            <h2>Commerce Scope</h2>
            <p class="sub">Single global MPN catalog with marketplace-specific overlays for price, tax, warehouse routing, promotions, shipping and inventory.</p>
            <table class="spec-table">
                <tr><th>Page type</th><td>{{ ucfirst($type) }}</td></tr>
                <tr><th>Regional pricing</th><td>Marketplace overlay</td></tr>
                <tr><th>Stock routing</th><td>Warehouse first</td></tr>
                <tr><th>AI output</th><td>Advisory only</td></tr>
            </table>
        </aside>
        <div>
            <div class="section-head">
                <div><p class="eyebrow">Global Catalog</p><h2>{{ $name }} products</h2></div>
                <a class="btn btn-ghost" href="/products?q={{ urlencode($name) }}">View all</a>
            </div>
            <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(230px,1fr))">
                @forelse($products as $product)
                    <article class="product-card">
                        <div class="product-img">{{ $product->manufacturer_name ?: ($product->brand?->name ?: 'NG') }}</div>
                        <span class="badge {{ ($product->stock_quantity ?? 0) > 0 ? 'b-ok' : 'b-warn' }}">{{ ($product->stock_quantity ?? 0) > 0 ? 'In stock' : 'RFQ ready' }}</span>
                        <h3><a href="/products/{{ $product->slug }}">{{ $product->name }}</a></h3>
                        <p class="sub">MPN {{ $product->mpn ?: 'Pending' }} · SKU {{ $product->sku ?: 'TBA' }}</p>
                        <a class="btn btn-ghost" href="/products/{{ $product->slug }}">Open product</a>
                    </article>
                @empty
                    <div class="panel" style="padding:34px;text-align:center;grid-column:1/-1">
                        <h2>Landing page ready</h2>
                        <p class="sub">{{ $copy['empty'] }}</p>
                        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap">
                            <a class="btn btn-primary" href="/rfq?message={{ urlencode('Source '.$name) }}">Request sourcing</a>
                            <a class="btn btn-ghost" href="/products">Browse catalog</a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</section>

<section class="section" style="background:#fff">
    <div class="wrap grid" style="grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
        <article class="info-card"><h2>AI BOM</h2><p class="sub">Generate a bill of materials for {{ $name }} projects, then convert it to cart or RFQ with regional availability checks.</p></article>
        <article class="info-card"><h2>LMS Links</h2><p class="sub">Tutorials, projects and lab kits can connect to this landing page as product-linked lessons are added.</p></article>
        <article class="info-card"><h2>Regional Stock</h2><p class="sub">NeoGiga routes stock by country storefront and warehouse overlay while preserving one global product identity.</p></article>
    </div>
</section>
@endsection
