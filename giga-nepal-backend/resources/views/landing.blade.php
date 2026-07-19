@extends('frontend.layout')

@section('title', $marketplaceSeo['title'] ?? 'NeoGiga — Global Engineering Marketplace')
@section('description', $marketplaceSeo['description'] ?? 'Source semiconductors, electronic components, robotics, IoT, automation and engineering tools through NeoGiga.')

@php
    $activePrefix = strtolower((string) request()->segment(1));
    $activePrefix = array_key_exists($activePrefix, config('neogiga_global.prefixes', []))
        ? $activePrefix
        : config('neogiga_global.default_prefix', 'en');
    $publicBase = '/'.$activePrefix;
    $currentMarketplace = $marketplaceContext['current'] ?? null;
    $editionName = $currentMarketplace?->regional_brand_name ?: ($currentMarketplace?->name ?: 'NeoGiga Global');
    $countryName = $currentMarketplace?->country?->name ?: 'Global';
    $currencyCode = $marketplaceContext['currency_code'] ?? 'USD';
    $isLiveEdition = ($currentMarketplace?->launch_status ?? 'active') === 'active';
@endphp

@push('head')
    <script type="application/ld+json">@json($homeSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
    <style>
        .home-status{display:grid;gap:12px;padding:24px}.home-status-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 0;border-bottom:1px solid var(--line)}.home-status-row:last-child{border:0}.home-status-row span{color:var(--muted)}.home-status-row strong{color:#fff;text-align:right}.home-stats{display:grid;grid-template-columns:repeat(4,1fr);border:1px solid var(--line);border-radius:var(--r);overflow:hidden;background:var(--glass)}.home-stat{padding:24px;border-right:1px solid var(--line)}.home-stat:last-child{border:0}.home-stat strong{display:block;color:#fff;font-size:clamp(1.7rem,3vw,2.35rem);line-height:1}.home-stat span{display:block;color:var(--muted);font-size:.82rem;margin-top:7px}.home-product-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.home-product-card{padding:0;overflow:hidden}.home-product-card .product-copy{display:grid;gap:9px;padding:18px}.home-product-card h3{margin:0;font-size:1rem}.home-product-card .product-img img{width:100%;height:100%;object-fit:contain;background:#081527}.home-price{color:var(--cyan);font-size:1.1rem;font-weight:700}.home-brand-row{display:flex;gap:10px;flex-wrap:wrap}.home-brand-row a{border:1px solid var(--line);border-radius:999px;padding:9px 14px;color:var(--muted);font-size:.84rem;transition:.15s}.home-brand-row a:hover{border-color:var(--cyan);color:var(--cyan)}.home-capabilities{grid-template-columns:repeat(4,minmax(0,1fr))}.home-editions{grid-template-columns:repeat(3,minmax(0,1fr))}.home-edition{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px}.home-edition:hover{border-color:var(--cyan)}.home-edition strong{display:block}.home-edition small{color:var(--muted)}.home-cta{display:flex;align-items:center;justify-content:space-between;gap:22px;padding:32px}.home-cta h2{margin:0 0 8px}.home-cta p{margin:0;color:var(--muted)}
        @media(max-width:980px){.home-product-grid,.home-capabilities{grid-template-columns:repeat(2,minmax(0,1fr))}.home-editions{grid-template-columns:1fr 1fr}.home-stats{grid-template-columns:1fr 1fr}.home-stat:nth-child(2){border-right:0}.home-stat:nth-child(-n+2){border-bottom:1px solid var(--line)}}
        @media(max-width:620px){.home-product-grid,.home-capabilities,.home-editions{grid-template-columns:1fr}.home-stats{grid-template-columns:1fr}.home-stat,.home-stat:nth-child(2){border-right:0;border-bottom:1px solid var(--line)}.home-stat:last-child{border-bottom:0}.home-cta{display:grid}.home-cta .btn{width:100%}}
    </style>
@endpush

@section('content')
<section class="hero">
    <div class="wrap hero-grid">
        <div>
            @if($welcomeMessage)
                <p class="eyebrow">{{ $welcomeMessage['title'] }}</p>
                @if(!empty($welcomeMessage['subtitle']))<p style="color:var(--cyan);font-weight:600;margin:-8px 0 0">{{ $welcomeMessage['subtitle'] }}</p>@endif
            @else
                <p class="eyebrow">{{ $editionName }} · {{ $currencyCode }} · {{ $isLiveEdition ? 'Live regional catalog' : 'Regional platform preview' }}</p>
            @endif
            <h1>Engineering supply,<br><span style="color:var(--cyan)">one global platform.</span></h1>
            <p>Source semiconductors, electronic components, robotics, IoT, industrial automation, batteries and engineering tools through one verified product master—with regional pricing, inventory and fulfilment for {{ $countryName }}.</p>
            <form class="search hero-search" method="get" action="{{ $publicBase }}/products" role="search">
                <select name="category" aria-label="Engineering category"><option value="">All categories</option>@foreach($categories->take(8) as $category)<option value="{{ $category['slug'] }}">{{ $category['name'] }}</option>@endforeach</select>
                <input name="q" type="search" placeholder="Search MPN, SKU, brand or component" aria-label="Search the NeoGiga catalog">
                <button type="submit">Search</button>
            </form>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:18px">
                <a class="btn btn-primary" href="{{ $publicBase }}/products">Browse products</a>
                <a class="btn btn-gold" href="{{ $publicBase }}/rfq">Create bulk RFQ</a>
                <a class="btn btn-ghost" href="{{ $publicBase }}/ai-commerce">Build with AI</a>
            </div>
        </div>
        <aside class="panel home-status" aria-label="Regional sourcing hub">
            <div>
                <span class="badge b-ok">Regional Engineering Sourcing Hub</span>
                <h2 style="margin:12px 0 4px">Source electronic components faster in {{ $countryName }}</h2>
                <p class="sub" style="margin:0">Search by MPN, upload a BOM, request an RFQ, compare regional stock and source from verified warehouses and distributors.</p>
            </div>
            <div class="home-status-row"><span>Products available</span><strong>{{ number_format($stats['products']) }}</strong></div>
            <div class="home-status-row"><span>Regional currency</span><strong>{{ $currencyCode }}</strong></div>
            <div class="home-status-row"><span>Delivered across</span><strong>{{ $countryName }}</strong></div>
            <div style="display:flex;flex-direction:column;gap:8px;margin-top:4px">
                <a class="btn btn-primary" href="{{ $publicBase }}/bom"><x-icon name="rfq" size="16"/> Upload BOM</a>
                <a class="btn btn-gold" href="{{ $publicBase }}/rfq"><x-icon name="rfq" size="16"/> Request RFQ</a>
                <a class="btn btn-ghost" href="{{ $publicBase }}/products">Browse products</a>
            </div>
        </aside>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="home-stats" aria-label="NeoGiga catalog coverage">
            <a class="home-stat" href="{{ $publicBase }}/products"><strong>1M+</strong><span>Sourcing MPNs</span></a>
            <a class="home-stat" href="{{ $publicBase }}/categories"><strong>4K+</strong><span>Engineering Categories</span></a>
            <a class="home-stat" href="{{ $publicBase }}/brands"><strong>2,000+</strong><span>Brands &amp; Manufacturers</span></a>
            <a class="home-stat" href="{{ $publicBase }}/distributors"><strong>26+</strong><span>Regional Warehouses &amp; Distribution Centres</span></a>
        </div>
    </div>
</section>

<section class="section" style="padding-top:10px">
    <div class="wrap">
        <div class="section-head"><div><p class="eyebrow">Catalog taxonomy</p><h2>Shop by engineering domain</h2><p class="sub">Every category opens the live shared catalog, including its nested child categories.</p></div><a class="btn btn-ghost" href="{{ $publicBase }}/categories">All categories</a></div>
        <div class="grid category-grid">
            @foreach($categories as $category)
                <a class="category-card" href="{{ $publicBase }}/categories/{{ $category['slug'] }}">
                    <span class="cat-icon mono">{{ $category['icon'] }}</span>
                    <h3>{{ $category['name'] }}</h3>
                    <p class="sub">{{ Illuminate\Support\Str::limit($category['blurb'], 112) }}</p>
                    @if($category['children_count'] > 0)<span class="badge b-muted">{{ number_format($category['children_count']) }} subcategories</span>@endif
                </a>
            @endforeach
        </div>
    </div>
</section>

<section class="section" style="background:var(--bg2)">
    <div class="wrap">
        <div class="section-head"><div><p class="eyebrow">Regional availability</p><h2>Recently verified products</h2><p class="sub">Real catalog records and exact product-linked media only.</p></div><a class="btn btn-primary" href="{{ $publicBase }}/products">Open full catalog</a></div>
        @if($products->isNotEmpty())
            <div class="grid home-product-grid">
                @foreach($products as $product)
                    @php
                        $image = $product->images->first();
                        $regionalPrice = $product->marketplacePrices->first();
                        $price = $regionalPrice?->sale_price ?: $regionalPrice?->base_price;
                        $priceCurrency = $regionalPrice?->currency_code;
                    @endphp
                    <article class="product-card home-product-card">
                        <a class="product-img" href="{{ $publicBase }}/products/{{ $product->slug }}">
                            <x-product-image-badges :product="$product" />
                            <img src="{{ $image?->publicUrl() ?: url('/images/products/neogiga-product-placeholder-2026.png') }}" alt="{{ $image?->alt_text ?: trim(($product->manufacturer_name ?? '').' '.($product->mpn ?? '').' product image') }}" width="480" height="360" loading="lazy">
                        </a>
                        <div class="product-copy">
                            <span class="badge {{ (float) $product->stock_quantity > 0 ? 'b-ok' : 'b-warn' }}">{{ (float) $product->stock_quantity > 0 ? number_format((float) $product->stock_quantity).' available' : 'RFQ sourcing' }}</span>
                            <h3><a href="{{ $publicBase }}/products/{{ $product->slug }}">{{ $product->name }}</a></h3>
                            <span class="sub mono">{{ $product->mpn ?: $product->sku }}{{ $product->brand ? ' · '.$product->brand->name : '' }}</span>
                            <div class="home-price">{{ $price && $priceCurrency ? number_format((float) $price, 2).' '.$priceCurrency : 'Request price' }}</div>
                            <a class="btn btn-ghost" href="{{ $publicBase }}/products/{{ $product->slug }}">View product</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="panel" style="padding:30px"><h3>Catalog synchronization is running</h3><p class="sub">Use product search, browse categories or create an RFQ while the featured list is prepared.</p><div style="display:flex;gap:10px;flex-wrap:wrap"><a class="btn btn-primary" href="{{ $publicBase }}/products">Search products</a><a class="btn btn-ghost" href="{{ $publicBase }}/rfq">Create RFQ</a></div></div>
        @endif
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="section-head"><div><p class="eyebrow">Connected workflows</p><h2>One platform from idea to delivery</h2></div></div>
        <div class="grid home-capabilities">
            <a class="info-card" href="{{ $publicBase }}/ai-commerce"><span class="cat-icon">AI</span><h3>AI project builder</h3><p class="sub">Turn an engineering idea into parts, alternatives and a sourcing path.</p></a>
            <a class="info-card" href="{{ $publicBase }}/rfq"><span class="cat-icon">RFQ</span><h3>B2B procurement</h3><p class="sub">Submit bulk requirements for regional supply and quotation.</p></a>
            <a class="info-card" href="{{ $publicBase }}/lms"><span class="cat-icon">LMS</span><h3>Learn and build</h3><p class="sub">Connect technical learning with the exact parts used in projects.</p></a>
            <a class="info-card" href="{{ $publicBase }}/sell-on-neogiga"><span class="cat-icon">B2B</span><h3>Seller network</h3><p class="sub">Onboard products once and manage regional marketplace availability.</p></a>
        </div>
    </div>
</section>

@if($brands->isNotEmpty())
<section class="section" style="padding-top:0">
    <div class="wrap"><div class="section-head"><div><p class="eyebrow">Manufacturers and brands</p><h2>Browse verified product identities</h2></div><a class="btn btn-ghost" href="{{ $publicBase }}/brands">Brand directory</a></div><div class="home-brand-row">@foreach($brands as $brand)<a href="{{ $publicBase }}/brand/{{ $brand->slug }}">{{ $brand->name }}</a>@endforeach</div></div>
</section>
@endif

<section class="section" id="regional-editions" style="background:var(--bg2)">
    <div class="wrap">
        <div class="section-head"><div><p class="eyebrow">Regional websites</p><h2>The same NeoGiga platform in every active market</h2><p class="sub">Regional domains keep their own canonical SEO, currency and warehouse context while using the shared global application.</p></div></div>
        <div class="grid home-editions">
            @foreach(($marketplaceContext['editions'] ?? []) as $edition)
                <a class="panel home-edition" href="{{ $edition['url'] }}">
                    <span><strong>{{ $edition['name'] }}</strong><small class="mono">{{ $edition['domain'] ?: parse_url($edition['url'], PHP_URL_HOST) }}</small></span>
                    <span class="badge {{ $edition['launch_status'] === 'active' ? 'b-ok' : 'b-warn' }}">{{ $edition['currency_code'] }} · {{ $edition['launch_status'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap"><div class="panel home-cta"><div><h2>Ready to source or sell engineering products?</h2><p>Search the catalog, request a quote, or join the regional seller network.</p></div><div style="display:flex;gap:10px;flex-wrap:wrap"><a class="btn btn-primary" href="{{ $publicBase }}/products">Start sourcing</a><a class="btn btn-gold" href="{{ $publicBase }}/seller-early-access">Apply as seller</a></div></div></div>
</section>
@endsection
