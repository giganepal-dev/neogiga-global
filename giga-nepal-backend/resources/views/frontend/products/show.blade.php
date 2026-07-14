@extends('frontend.layout')
@section('title', $pageSeo['title'] ?? $product->name.' - NeoGiga')
@section('description', $pageSeo['description'] ?? \Illuminate\Support\Str::limit(strip_tags($product->short_description ?: ($product->description ?: 'Datasheet, technical specifications, stock and RFQ for '.$product->name.' on NeoGiga.')), 155))
@section('og_type','product')

@push('head')
@php
    $activePrefix = strtolower((string) request()->segment(1));
    $activePrefix = array_key_exists($activePrefix, config('neogiga_global.prefixes', []))
        ? $activePrefix
        : config('neogiga_global.default_prefix', 'en');
    $publicBase = '/'.$activePrefix;
    $productCanonical = $pageSeo['canonical'] ?? ($marketplaceSeo['canonical'] ?? url($publicBase.'/products/'.$product->slug));
    $canonicalParts = parse_url($productCanonical);
    $canonicalOrigin = ($canonicalParts['scheme'] ?? 'https').'://'.($canonicalParts['host'] ?? request()->getHost());
    $schemaManufacturer = $product->relationLoaded('manufacturer') ? $product->manufacturer : null;
    $schemaBrandManufacturer = $product->brand && $product->brand->relationLoaded('manufacturer') ? $product->brand->manufacturer : null;
    $schemaManufacturer ??= $schemaBrandManufacturer;
    $schemaPrice = $marketplacePrice?->sale_price ?: ($marketplacePrice?->base_price ?: ($product->sale_price ?: $product->base_price));
    $schemaCurrency = $marketplacePrice?->currency_code ?: ($marketplaceContext['currency_code'] ?? 'USD');
    $productSchema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'sku' => $product->sku,
        'mpn' => $product->mpn,
        'image' => $productImages->isNotEmpty() ? $productImages->map(fn ($image) => $image->publicUrl())->all() : [$ogImage],
        'brand' => $product->brand?->name ? [
            '@type' => 'Brand',
            'name' => $product->brand->name,
            'url' => $canonicalOrigin.$publicBase.'/brand/'.$product->brand->slug,
        ] : null,
        'manufacturer' => $schemaManufacturer?->name ? [
            '@type' => 'Organization',
            'name' => $schemaManufacturer->name,
            'url' => $canonicalOrigin.$publicBase.'/manufacturer/'.$schemaManufacturer->slug,
        ] : (($product->manufacturer_name ?: $product->brand?->name) ? [
            '@type' => 'Organization',
            'name' => $product->manufacturer_name ?: $product->brand->name,
        ] : null),
        'category' => $product->category?->name,
        'url' => $productCanonical,
        'description' => strip_tags($product->short_description ?: $product->description ?: $product->name),
    ], fn ($value) => $value !== null && $value !== '');
    if (($reviewSummary->count ?? 0) > 0) {
        $productSchema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $reviewSummary->average,
            'reviewCount' => $reviewSummary->count,
        ];
    }
    if ((float) $schemaPrice > 0) {
        $productSchema['offers'] = [
            '@type' => 'Offer',
            'url' => $productCanonical,
            'priceCurrency' => strtoupper((string) $schemaCurrency),
            'price' => number_format((float) $schemaPrice, 2, '.', ''),
            'availability' => ($product->stock_quantity ?? 0) > 0
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
        ];
    }
    $schemaBreadcrumb = [
        ['name' => 'Home', 'item' => $canonicalOrigin.$publicBase],
        ['name' => 'Products', 'item' => $canonicalOrigin.$publicBase.'/products'],
    ];
    if ($product->category) {
        $schemaBreadcrumb[] = ['name' => $product->category->name, 'item' => $canonicalOrigin.$publicBase.'/categories/'.$product->category->slug];
    }
    $schemaBreadcrumb[] = ['name' => $product->name, 'item' => $productCanonical];
@endphp
<script type="application/ld+json">
{!! json_encode($productSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => collect($schemaBreadcrumb)->values()->map(fn ($item, $index) => [
        '@type' => 'ListItem',
        'position' => $index + 1,
        'name' => $item['name'],
        'item' => $item['item'],
    ])->all(),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
@php
    $reviewCount = (int) ($reviewSummary->count ?? 0);
    $reviewAverage = $reviewSummary->average ?? null;
    $reviewBadge = $reviewCount > 0 ? number_format((float) $reviewAverage, 1).'/5' : 'No approved reviews yet';
    $productMeta = [];
    $manufacturerRecord = $product->relationLoaded('manufacturer') ? $product->manufacturer : null;
    $brandManufacturer = $product->brand && $product->brand->relationLoaded('manufacturer') ? $product->brand->manufacturer : null;
    $manufacturerRecord ??= $brandManufacturer;
    $manufacturerName = $manufacturerRecord?->name ?: ($product->manufacturer_name ?: null);
    $brandUrl = $product->brand ? $publicBase.'/brand/'.$product->brand->slug : null;
    $manufacturerUrl = $manufacturerRecord ? $publicBase.'/manufacturer/'.$manufacturerRecord->slug : ($manufacturerName ? $publicBase.'/manufacturer/'.\Illuminate\Support\Str::slug($manufacturerName) : null);
    if ($product->brand) {
        $productMeta[] = 'Brand: '.$product->brand->name;
    }
    if ($manufacturerName) {
        $productMeta[] = 'Manufacturer: '.$manufacturerName;
    }
    if ($product->mpn) {
        $productMeta[] = 'MPN: '.$product->mpn;
    }
    $productMeta[] = 'NeoGiga SKU: '.($product->sku ?? 'TBA');
    if ($reviewCount > 0) {
        $productMeta[] = 'Rating: '.number_format((float) $reviewAverage, 1).'/5 from '.$reviewCount.' reviews';
    }
    $priceCurrency = $marketplacePrice?->currency_native_symbol ?: ($marketplacePrice?->currency_symbol ?: ($marketplacePrice?->currency_code ?: ($marketplaceContext['currency_code'] ?? 'USD')));
    $displayPrice = $marketplacePrice ? ($marketplacePrice->sale_price ?: $marketplacePrice->base_price) : ($product->sale_price ?: $product->base_price);
    $displayCurrency = $marketplacePrice ? $priceCurrency : ($marketplaceContext['currency_code'] ?? 'USD');
    $galleryImages = $productImages->filter(fn ($image) => $image->is_active)->values();
    $primaryImage = $galleryImages->firstWhere('is_primary', true) ?: $galleryImages->first();
    $primaryImageUrl = $primaryImage?->publicUrl() ?: url('/images/products/neogiga-product-placeholder-2026.png');
    $primaryImageAlt = $primaryImage?->alt_text ?: $product->name.' product image';
@endphp
<section class="section" style="padding-top:18px">
    <div class="wrap">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ $publicBase }}">Home</a><span>/</span><a href="{{ $publicBase }}/products">Products</a>@if($product->category)<span>/</span><a href="{{ $publicBase }}/categories/{{ $product->category->slug }}">{{ $product->category->name }}</a>@endif<span>/</span><strong>{{ $product->name }}</strong></nav>
        <div class="grid product-primary-grid" style="grid-template-columns:minmax(300px,.9fr) minmax(0,1.4fr) 340px;align-items:start">
            <section class="panel" style="padding:18px">
                <div class="product-gallery">
                    <a id="product-gallery-zoom" class="product-gallery-main" href="{{ $primaryImageUrl }}" target="_blank" rel="noopener" aria-label="Open enlarged image of {{ $product->name }}">
                        <img id="product-gallery-main-image" class="{{ $primaryImage ? '' : 'product-gallery-placeholder' }}" src="{{ $primaryImageUrl }}" alt="{{ $primaryImageAlt }}" width="1200" height="900" fetchpriority="high">
                    </a>
                    <div class="product-gallery-thumbs" aria-label="Product image gallery">
                        @forelse($galleryImages as $image)
                            <button class="product-gallery-thumb {{ $image->id === $primaryImage?->id ? 'active' : '' }}" type="button" data-gallery-src="{{ $image->publicUrl() }}" data-gallery-alt="{{ $image->alt_text ?: $product->name.' product image '.$loop->iteration }}" aria-label="Show product image {{ $loop->iteration }}">
                                <img src="{{ $image->publicUrl() }}" alt="" loading="lazy" width="120" height="120">
                            </button>
                        @empty
                            <span class="product-gallery-thumb active" aria-label="NeoGiga product image placeholder"><img src="{{ $primaryImageUrl }}" alt="" width="120" height="120"></span>
                        @endforelse
                    </div>
                </div>
            </section>
            <section class="panel" style="padding:22px">
                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px"><span class="badge b-info">{{ $product->category->name ?? 'Engineering part' }}</span><span class="badge {{ ($product->stock_quantity ?? 0) > 0 ? 'b-ok' : 'b-warn' }}">{{ ($product->stock_quantity ?? 0) > 0 ? 'In stock' : 'RFQ only' }}</span></div>
                <h1 style="font-size:clamp(1.8rem,4vw,3.1rem);line-height:1.05;margin:0 0 10px">{{ $product->name }}</h1>
                <p class="sub">{{ implode(' · ', $productMeta) }}</p>
                <div style="display:flex;gap:10px;flex-wrap:wrap;margin:12px 0 18px">
                    @if($product->brand)
                        <a class="badge b-info" href="{{ $brandUrl }}">Brand: {{ $product->brand->name }}</a>
                    @endif
                    @if($manufacturerName)
                        <a class="badge b-muted" href="{{ $manufacturerUrl }}">Manufacturer: {{ $manufacturerName }}</a>
                    @endif
                    @if($product->mpn)
                        <a class="badge b-muted" href="/mpn/{{ urlencode($product->mpn) }}">MPN: {{ $product->mpn }}</a>
                    @endif
                </div>
                @if($product->short_description || $product->description)<p>{{ strip_tags($product->short_description ?: \Illuminate\Support\Str::limit(strip_tags($product->description), 540)) }}</p>@endif
                <h2 style="font-size:1.2rem;margin-top:24px">Technical Specifications</h2>
                <table class="spec-table">
                    @foreach($advancedSpecs->groupBy(fn($s) => $s->group_name ?: ($s->template_name ?: 'Advanced Specifications')) as $groupName => $rows)
                        <tr class="spec-group"><th colspan="2">{{ $groupName }}</th></tr>
                        @foreach($rows as $s)
                            <tr><th>{{ $s->field_label }}</th><td>{{ $s->value }}{{ $s->unit_override ? ' '.$s->unit_override : ($s->unit ? ' '.$s->unit : '') }}</td></tr>
                        @endforeach
                    @endforeach
                    @forelse($product->specs->sortBy('sort_order') as $s)
                        <tr><th>{{ $s->name }}</th><td>{{ $s->value }}{{ $s->unit ? ' '.$s->unit : '' }}</td></tr>
                    @empty
                        @if($product->mpn)<tr><th>Manufacturer Part Number</th><td>{{ $product->mpn }}</td></tr>@endif
                        @if($manufacturerName)<tr><th>Manufacturer</th><td><a href="{{ $manufacturerUrl }}">{{ $manufacturerName }}</a></td></tr>@endif
                        @if($product->brand)<tr><th>Brand</th><td><a href="{{ $brandUrl }}">{{ $product->brand->name }}</a></td></tr>@endif
                        @if($product->category)<tr><th>Category</th><td>{{ $product->category->name }}</td></tr>@endif
                        <tr><th>Datasheet</th><td>Available on request</td></tr>
                    @endforelse
                </table>
            </section>
            <aside class="panel" style="padding:18px">
                <h2 style="margin-top:0">Get this part</h2>
                <div class="product-price-card">
                    <div class="sub">Regional price</div>
                    @if($displayPrice)
                        <strong style="font-size:1.7rem">{{ $displayCurrency }} {{ number_format((float) $displayPrice, 2) }}</strong>
                        <div class="sub">{{ $marketplacePrice?->marketplace_name ?: ($marketplaceContext['current']?->name ?? 'Current marketplace') }} @if($marketplacePrice?->is_tax_inclusive) · tax inclusive @endif</div>
                    @else
                        <strong>RFQ pricing</strong>
                        <div class="sub">Regional price is not published yet. Request quote for contract, bulk, or import pricing.</div>
                    @endif
                </div>
                <div class="grid">
                    <a class="btn btn-primary" href="/rfq?product={{ $product->slug }}">Request Bulk Quote</a>
                    <a class="btn btn-gold" href="/ai-commerce?part={{ urlencode($product->mpn ?: $product->sku ?: $product->name) }}">Ask AI Engineer</a>
                    <form method="post" action="/cart/items" style="display:grid;grid-template-columns:86px 1fr;gap:8px">@csrf<input type="hidden" name="product_id" value="{{ $product->id }}"><input class="control" type="number" name="quantity" min="1" max="500" value="1" aria-label="Quantity"><button class="btn btn-ghost" type="submit">Add to Cart</button></form>
                    <a class="btn btn-ghost" href="/sell-on-neogiga">Chat with Seller Soon</a>
                </div>
                <p class="sub">B2B pricing, contract offers, regional warehouse stock and delivery dates are handled through RFQ until checkout is fully opened.</p>
                <h3>Stock by warehouse</h3>
                <table class="spec-table">
                    @forelse($stockRows as $row)
                        <tr><th>{{ $row->warehouse_name ?? 'Warehouse' }}<br><small>{{ $row->country_name ?? 'Global' }}</small></th><td><strong>{{ number_format((int) $row->quantity_available) }}</strong><br><span class="sub">{{ $row->quote_only ? 'Quote only' : 'Available' }}</span></td></tr>
                    @empty
                        <tr><td colspan="2">Regional stock is being loaded. Use RFQ for availability.</td></tr>
                    @endforelse
                </table>
            </aside>
        </div>
    </div>
</section>

<section class="section product-detail-section">
    <div class="wrap">
        <div class="section-head"><div><p class="eyebrow">Seller offers</p><h2>Regional sourcing options</h2></div><a class="btn btn-ghost" href="/rfq?product={{ $product->slug }}">Request better quote</a></div>
        <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(260px,1fr))">
            @forelse($sellerOffers as $offer)
                @php
                    $offerCurrency = $offer->currency_native_symbol ?: ($offer->currency_symbol ?: $offer->currency_code);
                    $offerStatus = $offer->vendor_product_status ?: ($offer->vendor_status ?: 'active');
                @endphp
                <article class="info-card">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:start">
                        <h3 style="margin-top:0">{{ $offer->vendor_name ?: 'NeoGiga seller' }}</h3>
                        <span class="badge {{ $offer->is_verified ? 'b-ok' : 'b-muted' }}">{{ $offer->is_verified ? 'Verified' : $offerStatus }}</span>
                    </div>
                    <p class="sub">{{ $offer->marketplace_name ?: ($marketplaceContext['current']?->name ?? 'Global marketplace') }}</p>
                    <p style="font-size:1.35rem;margin:8px 0"><strong>{{ $offerCurrency }} {{ number_format((float) $offer->selling_price, 2) }}</strong></p>
                    @if($offer->min_price)<p class="sub">Negotiated floor from {{ $offerCurrency }} {{ number_format((float) $offer->min_price, 2) }}</p>@endif
                    <div style="display:flex;gap:8px;flex-wrap:wrap"><a class="btn btn-primary" href="/rfq?product={{ $product->slug }}&seller={{ $offer->vendor_slug }}">RFQ seller</a>@if($offer->vendor_slug)<span class="badge b-muted">{{ $offer->vendor_slug }}</span>@endif</div>
                </article>
            @empty
                <div class="panel" style="padding:24px">
                    <h3>No public seller offers yet</h3>
                    <p class="sub">NeoGiga can still source this part through RFQ. Seller-specific offers will appear here when vendor pricing is published.</p>
                    <a class="btn btn-primary" href="/rfq?product={{ $product->slug }}">Start RFQ</a>
                </div>
            @endforelse
        </div>
    </div>
</section>

<section class="section product-detail-section">
    <div class="wrap grid" style="grid-template-columns:repeat(auto-fit,minmax(280px,1fr))">
        <div class="info-card"><h2>Datasheets & Downloads</h2>@forelse($documents as $doc)<p><strong>{{ $doc->title ?? ucfirst($doc->document_type ?? 'Document') }}</strong><br><a href="{{ $doc->file_url ?: $doc->source_url }}" rel="nofollow">Download {{ $doc->document_type ?? 'file' }}</a></p>@empty<p class="sub">Datasheet, CAD, firmware and compliance assets are being loaded.</p>@endforelse</div>
        <div class="info-card"><h2>Alternatives & Accessories</h2>@forelse($alternatives as $alt)<p><a href="{{ $alt->slug ? '/products/'.$alt->slug : '#' }}"><strong>{{ $alt->name ?? 'Related product' }}</strong></a><br><span class="sub">{{ $alt->relation_type }} · {{ $alt->mpn ?: $alt->sku }}</span></p>@empty<p class="sub">Alternative parts and accessories are being mapped.</p>@endforelse</div>
        <div class="info-card"><h2>Related LMS Tutorials</h2>@forelse($lmsLinks as $link)<p><a href="{{ $link->url ?: '/learn' }}"><strong>{{ $link->title }}</strong></a><br><span class="sub">{{ $link->relation_type }}</span></p>@empty<p class="sub">Related tutorials, lab kits and project usage will appear here.</p>@endforelse<a class="btn btn-ghost" href="/learn">Open Learning Hub</a></div>
    </div>
</section>

<section class="section product-detail-section">
    <div class="wrap grid" style="grid-template-columns:minmax(0,1.3fr) minmax(300px,.7fr);align-items:start">
        <div class="panel" style="padding:22px">
            <div class="section-head" style="margin-bottom:12px"><div><p class="eyebrow">Reviews & Q&A</p><h2>Engineering feedback</h2></div><span class="badge b-info">{{ $reviewBadge }}</span></div>
            @forelse($reviews as $review)
                <article class="product-review">
                    <div style="display:flex;justify-content:space-between;gap:10px;align-items:start"><strong>{{ $review->title ?: 'Product review' }}</strong><span class="badge b-ok">{{ $review->rating }}/5</span></div>
                    <p>{{ $review->body }}</p>
                    <p class="sub">{{ $review->reviewer_name ?: 'NeoGiga customer' }}{{ $review->use_case ? ' · Use case: '.$review->use_case : '' }}</p>
                </article>
            @empty
                <p class="sub">Approved customer reviews will appear here after moderation.</p>
            @endforelse
        </div>
        <form class="panel" style="padding:22px;display:grid;gap:10px" method="post" action="{{ route('products.reviews.store', $product->slug) }}">@csrf
            <h2 style="margin:0">Submit a review</h2>
            <select class="control" name="rating" required><option value="">Rating</option><option value="5">5 - Excellent</option><option value="4">4 - Good</option><option value="3">3 - Average</option><option value="2">2 - Limited</option><option value="1">1 - Poor</option></select>
            <input class="control" name="title" maxlength="180" placeholder="Short title">
            <textarea class="control" name="body" rows="5" required minlength="10" maxlength="2500" placeholder="Share build quality, compatibility, packaging, or sourcing feedback"></textarea>
            <input class="control" name="use_case" maxlength="160" placeholder="Use case, e.g. ESP32 gateway prototype">
            @if(! auth()->check())
                <input class="control" name="reviewer_name" maxlength="120" placeholder="Name">
                <input class="control" type="email" name="reviewer_email" maxlength="190" placeholder="Email">
            @endif
            <button class="btn btn-primary" type="submit">Submit for moderation</button>
            <p class="sub" style="margin:0">Reviews are checked before publication.</p>
        </form>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div class="section-head"><div><p class="eyebrow">Related products</p><h2>Similar engineering parts</h2></div></div>
        <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(230px,1fr))">
            @forelse($related as $r)
                @php($relatedImage = $r->images->first())
                <article class="product-card"><a href="{{ $publicBase }}/products/{{ $r->slug }}"><div class="product-img"><img src="{{ $relatedImage?->publicUrl() ?: url('/images/products/neogiga-product-placeholder-2026.png') }}" alt="{{ $relatedImage?->alt_text ?: $r->name.' product image' }}" width="480" height="360" loading="lazy" style="width:100%;height:100%;object-fit:contain;background:#081527"></div></a><h3><a href="{{ $publicBase }}/products/{{ $r->slug }}">{{ $r->name }}</a></h3><p class="sub">{{ $r->mpn ?: $r->sku }}</p><a class="btn btn-ghost" href="{{ $publicBase }}/products/{{ $r->slug }}">View</a></article>
            @empty
                <div class="panel" style="padding:24px"><p class="sub">Related products are being indexed.</p></div>
            @endforelse
        </div>
    </div>
</section>
<script>
(function(){var main=document.getElementById('product-gallery-main-image'),zoom=document.getElementById('product-gallery-zoom');if(!main||!zoom)return;document.querySelectorAll('[data-gallery-src]').forEach(function(button){button.addEventListener('click',function(){document.querySelectorAll('[data-gallery-src]').forEach(function(item){item.classList.remove('active')});button.classList.add('active');main.src=button.dataset.gallerySrc;main.alt=button.dataset.galleryAlt;zoom.href=button.dataset.gallerySrc;zoom.setAttribute('aria-label','Open enlarged image: '+button.dataset.galleryAlt)})})})();
</script>
@endsection
