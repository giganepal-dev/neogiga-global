@extends('frontend.layout')
@section('title', $pageSeo['title'] ?? html_entity_decode($displayName).' - NeoGiga')
@section('description', $pageSeo['description'] ?? \Illuminate\Support\Str::limit(strip_tags($product->short_description ?: ($product->description ?: 'Datasheet, technical specifications, stock and RFQ for '.$displayName.' on NeoGiga.')), 155))
@section('og_type','product')

@php
    // Clean names — used in both head (schema) and content
    $cleanName = function(string $s): string { return trim(html_entity_decode($s, ENT_QUOTES|ENT_HTML5, 'UTF-8'), " \t\n\r\0\x0B\"'"); };
    $displayName = $cleanName($product->name);
@endphp
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
    $productSchema = app(\App\Services\Seo\ProductSchemaService::class)->build($product, [
        'canonical' => $productCanonical, 'origin' => $canonicalOrigin, 'base' => $publicBase,
        'images' => $productImages->isNotEmpty()
            ? (collect($productImages->map(fn ($image) => $image->publicUrl())->all())->filter()->values()->all() ?: [$ogImage])
            : [$ogImage],
        'price' => $schemaPrice, 'currency' => $schemaCurrency,
        'country' => $marketplaceContext['country_code'] ?? null,
        'marketplace' => $marketplaceContext['current'] ?? null,
        'manufacturer' => $schemaManufacturer,
        'reviewSummary' => $reviewSummary ?? null, 'reviews' => $reviews ?? [],
    ]);
    $schemaBreadcrumb = [
        ['name' => 'Home', 'item' => $canonicalOrigin.$publicBase],
        ['name' => 'Products', 'item' => $canonicalOrigin.$publicBase.'/products'],
    ];
    if ($product->category) {
        $schemaBreadcrumb[] = ['name' => $product->category->name, 'item' => $canonicalOrigin.$publicBase.'/categories/'.$product->category->slug];
    }
    $schemaBreadcrumb[] = ['name' => $displayName, 'item' => $productCanonical];
@endphp
<script nonce="{{ $csp_nonce ?? '' }}" type="application/ld+json">
{!! json_encode($productSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
<script nonce="{{ $csp_nonce ?? '' }}" type="application/ld+json">
{!! json_encode([
    '@'.'context' => 'https://schema.org', '@type' => 'BreadcrumbList',
    'itemListElement' => collect($schemaBreadcrumb)->values()->map(fn ($item, $index) => [
        '@type' => 'ListItem', 'position' => $index + 1, 'name' => $item['name'], 'item' => $item['item'],
    ])->all(),
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
@php
    $reviewCount = (int) ($reviewSummary->count ?? 0);
    $reviewAverage = $reviewSummary->average ?? null;
    $reviewBadge = $reviewCount > 0 ? number_format((float) $reviewAverage, 1).'/5' : 'No reviews yet';
    $manufacturerRecord = $product->relationLoaded('manufacturer') ? $product->manufacturer : null;
    $brandManufacturer = $product->brand && $product->brand->relationLoaded('manufacturer') ? $product->brand->manufacturer : null;
    $manufacturerRecord ??= $brandManufacturer;
    $rawMfrName = $manufacturerRecord?->name ?: ($product->manufacturer_name ?: null);
    $displayMfrName = $rawMfrName ? $cleanName($rawMfrName) : null;
    $brandUrl = $product->brand ? $publicBase.'/brand/'.$product->brand->slug : null;
    $manufacturerUrl = $manufacturerRecord ? $publicBase.'/manufacturer/'.$manufacturerRecord->slug : ($displayMfrName ? $publicBase.'/manufacturer/'.\Illuminate\Support\Str::slug($displayMfrName) : null);
    $priceCurrency = $marketplacePrice?->currency_native_symbol ?: ($marketplacePrice?->currency_symbol ?: ($marketplacePrice?->currency_code ?: ($marketplaceContext['currency_code'] ?? 'USD')));
    $displayPrice = $marketplacePrice ? ($marketplacePrice->sale_price ?: $marketplacePrice->base_price) : ($product->sale_price ?: $product->base_price);
    $displayCurrency = $marketplacePrice ? $priceCurrency : ($marketplaceContext['currency_code'] ?? 'USD');
    $galleryImages = $productImages->filter(fn ($image) => $image->is_active)->values();
    $primaryImage = $galleryImages->firstWhere('is_primary', true) ?: $galleryImages->first();
    $primaryImageUrl = $primaryImage?->publicUrl() ?: url('/images/products/neogiga-product-placeholder-2026.png');
    $primaryImageAlt = $primaryImage?->alt_text ?: $displayName.' product image';
    $skuSearchUrl = $publicBase.'/products?q='.urlencode((string) $product->sku);
    $mpnUrl = $product->mpn ? '/mpn/'.str_replace('/','--', urlencode($product->mpn)) : null;
    $categoryUrl = $product->category ? $publicBase.'/categories/'.$product->category->slug : null;
    $inStock = ($product->stock_quantity ?? 0) > 0;
    $shownSpecificationKeys = collect();
    $certMarks = $certifications ?? collect();
    $hasCertifications = $certMarks->isNotEmpty();
@endphp

{{-- ===== MOBILE STICKY BAR ===== --}}
<div class="prod-mobile-bar" id="prod-mobile-bar">
    <div class="prod-mobile-bar-inner">
        <div>
            <strong>{{ $displayCurrency }} {{ number_format((float) $displayPrice, 2) }}</strong>
            <span class="prod-mobile-stock {{ $inStock ? 'in-stock' : '' }}">{{ $inStock ? 'In Stock' : 'RFQ' }}</span>
        </div>
        <button class="btn btn-primary" onclick="document.getElementById('prod-sidebar-add').scrollIntoView({behavior:'smooth'});">Add to Cart</button>
    </div>
</div>

{{-- ===== BREADCRUMB ===== --}}
<div class="prod-wrap">
    <nav class="prod-breadcrumb" aria-label="Breadcrumb">
        <a href="{{ $publicBase }}">Home</a><span>/</span>
        <a href="{{ $publicBase }}/products">Products</a>
        @if($product->category)<span>/</span><a href="{{ $publicBase }}/categories/{{ $product->category->slug }}">{{ $product->category->name }}</a>@endif
        <span>/</span><strong>{{ \Illuminate\Support\Str::limit($displayName, 60) }}</strong>
    </nav>
</div>

{{-- ===== MAIN TWO-COLUMN LAYOUT ===== --}}
<div class="prod-wrap">
<div class="prod-layout">

{{-- ===== LEFT COLUMN (70%) ===== --}}
<div class="prod-main">

{{-- TOP SECTION: Gallery + Identity Summary --}}
<div class="prod-top panel">
    <div class="prod-top-grid">
        {{-- Gallery --}}
        <div class="prod-gallery">
            <div class="prod-gallery-main-wrap">
                <a id="prod-gallery-zoom" class="prod-gallery-main" href="{{ $primaryImageUrl }}" target="_blank" rel="noopener" aria-label="Enlarge product image">
                    @if($galleryImages->count() > 1)
                    <button class="prod-gallery-prev" type="button" aria-label="Previous image" data-gallery-nav="-1"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6" stroke-linecap="round"/></svg></button>
                    <button class="prod-gallery-next" type="button" aria-label="Next image" data-gallery-nav="1"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6" stroke-linecap="round"/></svg></button>
                    @endif
                    <img id="prod-gallery-main-img" src="{{ $primaryImageUrl }}" @if($primaryImage?->srcset()) srcset="{{ $primaryImage->srcset() }}" sizes="(max-width: 768px) 100vw, 50vw" @endif alt="{{ $primaryImageAlt }}" width="1200" height="1200" fetchpriority="high">
                </a>
                @if($galleryImages->count() > 0)<span class="prod-gallery-count">{{ $galleryImages->count() }} image{{ $galleryImages->count() > 1 ? 's' : '' }}</span>@endif
            </div>
            @if($galleryImages->count() > 1)
            <div class="prod-gallery-thumbs" aria-label="Product image gallery">
                @foreach($galleryImages as $image)
                    <button class="prod-gallery-thumb {{ $image->id === $primaryImage?->id ? 'active' : '' }}" type="button" data-gallery-src="{{ $image->publicUrl() }}" data-gallery-alt="{{ $image->alt_text ?: $displayName.' image '.$loop->iteration }}" aria-label="View image {{ $loop->iteration }}" data-index="{{ $loop->index }}">
                        <img src="{{ $image->publicUrl() }}" alt="" loading="lazy" width="80" height="80">
                    </button>
                @endforeach
            </div>
            @endif
        </div>

        {{-- Identity Summary --}}
        <div class="prod-identity">
            <div class="prod-badges">
                @if($product->category)<a class="badge b-info" href="{{ $categoryUrl }}">{{ $product->category->name }}</a>@endif
                <span class="badge {{ $inStock ? 'b-ok' : 'b-warn' }}">{{ $inStock ? 'In Stock' : 'RFQ Only' }}</span>
                @if($product->status)<span class="badge b-muted">{{ ucfirst($product->status) }}</span>@endif
            </div>
            <h1 class="prod-title">{{ $displayName }}</h1>
            <div class="prod-meta-line">
                @if($product->brand)<span>Brand: <a href="{{ $brandUrl }}">{{ $product->brand->name }}</a></span>@endif
                @if($displayMfrName && $displayMfrName !== ($product->brand->name ?? ''))<span>Mfr: <a href="{{ $manufacturerUrl }}">{{ $displayMfrName }}</a></span>@endif
                @if($product->mpn)<span class="mono">MPN: {{ $product->mpn }}</span>@endif
                <span class="mono">SKU: {{ $product->sku ?? 'TBA' }}</span>
            </div>
            @if($reviewCount > 0)
            <div class="prod-rating">
                @for($i=1; $i<=5; $i++)<span class="star {{ $i <= round($reviewAverage) ? 'filled' : '' }}">★</span>@endfor
                <span>{{ number_format((float)$reviewAverage, 1) }} ({{ $reviewCount }} review{{ $reviewCount > 1 ? 's' : '' }})</span>
            </div>
            @endif
            @if($product->short_description || $product->description)
            <p class="prod-excerpt">{{ strip_tags($product->short_description ?: \Illuminate\Support\Str::limit(strip_tags($product->description), 280)) }}</p>
            @endif
        </div>
    </div>
</div>

{{-- PRODUCT IDENTITY TABLE --}}
<div class="panel prod-section">
    <h3 class="prod-section-title">Product Identity</h3>
    <table class="prod-id-table">
        <tbody>
            @if($product->mpn)<tr><th>Manufacturer Part Number</th><td>@if($mpnUrl)<a class="mono" href="{{ $mpnUrl }}">{{ $product->mpn }}</a>@else<span class="mono">{{ $product->mpn }}</span>@endif</td></tr>
            @else<tr><th>Manufacturer Part Number</th><td class="prod-na">Not provided</td></tr>@endif
            <tr><th>NeoGiga SKU</th><td><a class="mono" href="{{ $skuSearchUrl }}">{{ $product->sku ?? 'TBA' }}</a></td></tr>
            @if($product->brand)<tr><th>Brand</th><td><a href="{{ $brandUrl }}">{{ $product->brand->name }}</a></td></tr>@endif
            @if($displayMfrName)<tr><th>Manufacturer</th><td>@if($manufacturerUrl)<a href="{{ $manufacturerUrl }}">{{ $displayMfrName }}</a>@else{{ $displayMfrName }}@endif</td></tr>@endif
            @if($product->category)<tr><th>Category</th><td><a href="{{ $categoryUrl }}">{{ $product->category->name }}</a>@if($product->category->parent)<span class="prod-sub"> / {{ $product->category->parent->name }}</span>@endif</td></tr>@endif
            @if($product->manufacturer_name && $displayMfrName && strtolower($product->manufacturer_name) !== strtolower($displayMfrName))<tr><th>Source Manufacturer</th><td>{{ $product->manufacturer_name }}</td></tr>@endif
            @php $lifecycle = data_get($product->metadata, 'lifecycle_status') ?: data_get($product->metadata, 'product_lifecycle'); @endphp
            @if($lifecycle)<tr><th>Lifecycle Status</th><td><span class="badge {{ in_array(strtolower($lifecycle), ['active','production','available']) ? 'b-ok' : 'b-warn' }}">{{ $lifecycle }}</span></td></tr>@endif
            @if(data_get($product->metadata, 'country_of_origin'))<tr><th>Country of Origin</th><td>{{ data_get($product->metadata, 'country_of_origin') }}</td></tr>@endif
            @if(data_get($product->metadata, 'eccn'))<tr><th>ECCN</th><td class="mono">{{ data_get($product->metadata, 'eccn') }}</td></tr>@endif
            @if(data_get($product->metadata, 'hts_code') || data_get($product->metadata, 'hs_code'))<tr><th>HTS / HS Code</th><td class="mono">{{ data_get($product->metadata, 'hts_code') ?: data_get($product->metadata, 'hs_code') }}</td></tr>@endif
            @if(data_get($product->metadata, 'rohs_status'))<tr><th>RoHS Status</th><td><span class="badge {{ strtolower(data_get($product->metadata, 'rohs_status')) === 'compliant' ? 'b-ok' : 'b-muted' }}">{{ data_get($product->metadata, 'rohs_status') }}</span></td></tr>@endif
            @if(data_get($product->metadata, 'reach_status'))<tr><th>REACH Status</th><td><span class="badge {{ strtolower(data_get($product->metadata, 'reach_status')) === 'compliant' ? 'b-ok' : 'b-muted' }}">{{ data_get($product->metadata, 'reach_status') }}</span></td></tr>@endif
        </tbody>
    </table>
</div>

{{-- TECHNICAL SPECIFICATIONS --}}
@php
    $specGroups = collect();
    $identityKeys = ['manufacturer part number','neogiga sku','brand','manufacturer','category','mpn','sku','lifecycle','country of origin','eccn','hts','hs code','rohs','reach'];
    // Group advanced specs
    $advGrouped = $advancedSpecs->groupBy(fn($s) => $s->group_name ?: ($s->template_name ?: 'General'));
    foreach($advGrouped as $groupName => $rows) {
        foreach($rows as $s) {
            $key = strtolower(preg_replace('/[^a-z0-9]+/i', '', $s->field_label ?? ''));
            if(!in_array($key, $identityKeys)) {
                if(!isset($specGroups[$groupName])) $specGroups[$groupName] = collect();
                $specGroups[$groupName]->push($s);
            }
            $shownSpecificationKeys->push($key);
        }
    }
    // Product specs
    $prodSpecs = $product->specs->sortBy('sort_order');
    foreach($prodSpecs as $s) {
        $key = strtolower(preg_replace('/[^a-z0-9]+/i', '', $s->name ?? ''));
        if(!$shownSpecificationKeys->contains($key) && !in_array($key, $identityKeys)) {
            if(!isset($specGroups['General'])) $specGroups['General'] = collect();
            $specGroups['General']->push((object)['field_label'=>$s->name,'value'=>$s->value,'unit'=>$s->unit,'unit_override'=>null]);
            $shownSpecificationKeys->push($key);
        }
    }
    // Source specs
    $sourceRows = ($sourceSpecs ?? collect())->reject(fn($s) => $shownSpecificationKeys->contains(strtolower(preg_replace('/[^a-z0-9]+/i', '', $s['label'] ?? ''))));
    if($sourceRows->isNotEmpty()) {
        if(!isset($specGroups['Source Data'])) $specGroups['Source Data'] = collect();
        foreach($sourceRows as $s) {
            $specGroups['Source Data']->push((object)['field_label'=>$s['label'],'value'=>$s['value'],'unit'=>null,'unit_override'=>null,'url'=>$s['url'] ?? null]);
        }
    }
@endphp
@if(count($specGroups) > 0)
<div class="panel prod-section">
    <h3 class="prod-section-title">Technical Specifications</h3>
    @foreach($specGroups as $groupName => $rows)
        @if($rows->isNotEmpty())
        <h4 class="prod-spec-group">{{ $groupName }}</h4>
        <table class="prod-spec-table">
            <tbody>
            @foreach($rows as $s)
                <tr>
                    <th>{{ $s->field_label }}</th>
                    <td>
                        @if(isset($s->url) && $s->url)<a href="{{ $s->url }}" target="_blank" rel="noopener">{{ $s->value }}</a>
                        @else{{ $s->value }}@endif
                        @if($s->unit_override) <span class="prod-unit">{{ $s->unit_override }}</span>
                        @elseif($s->unit) <span class="prod-unit">{{ $s->unit }}</span>@endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    @endforeach
</div>
@endif

{{-- FULL DESCRIPTION --}}
@if($product->description)
<div class="panel prod-section">
    <h3 class="prod-section-title">Description</h3>
    <div class="prod-description">
        {!! $product->description !!}
    </div>
</div>
@endif

{{-- CERTIFICATIONS & COMPLIANCE --}}
<div class="panel prod-section">
    <h3 class="prod-section-title">Certifications &amp; Compliance</h3>
    @if($hasCertifications)
    <div class="prod-cert-grid">
        @foreach($certMarks as $mark)
        @php $label = is_array($mark) ? ($mark['label'] ?? '') : (is_object($mark) ? ($mark->label ?? '') : (string)$mark); @endphp
        <div class="prod-cert-badge">
            <div class="prod-cert-icon">
                @php $lbl = strtolower($label); @endphp
                @if(str_contains($lbl, 'rohs'))<img src="{{ url('/assets/compliance/rohs.svg') }}" alt="RoHS" width="48" height="48">
                @elseif(str_contains($lbl, 'reach'))<img src="{{ url('/assets/compliance/reach.svg') }}" alt="REACH" width="48" height="48">
                @elseif(str_contains($lbl, 'ce'))<img src="{{ url('/assets/compliance/ce.svg') }}" alt="CE" width="48" height="48">
                @elseif(str_contains($lbl, 'fcc'))<img src="{{ url('/assets/compliance/fcc.svg') }}" alt="FCC" width="48" height="48">
                @elseif(str_contains($lbl, 'ul'))<img src="{{ url('/assets/compliance/ul.svg') }}" alt="UL" width="48" height="48">
                @elseif(str_contains($lbl, 'iso'))<img src="{{ url('/assets/compliance/iso.svg') }}" alt="ISO" width="48" height="48">
                @elseif(str_contains($lbl, 'weee'))<img src="{{ url('/assets/compliance/weee.svg') }}" alt="WEEE" width="48" height="48">
                @else<span class="prod-cert-text-badge">{{ $label }}</span>@endif
            </div>
            <div class="prod-cert-info">
                <strong>{{ $label }}</strong>
                <span class="sub">{{ is_array($mark) ? ($mark['source'] ?? 'Verified') : 'Verified' }}</span>
                @php $certUrl = is_array($mark) ? ($mark['url'] ?? null) : (is_object($mark) ? ($mark->url ?? null) : null); @endphp
                @if($certUrl)<a href="{{ $certUrl }}" class="btn btn-ghost" style="font-size:.72rem;margin-top:4px" target="_blank" rel="noopener">View Document</a>@endif
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="prod-empty">
        <p class="sub">Documentation not yet verified.</p>
        <p class="sub" style="font-size:.78rem">Certification marks appear only when NeoGiga holds verified, current product compliance records. <a href="#chat-modal-{{ $product->id }}" onclick="document.getElementById('chat-modal-{{ $product->id }}').style.display='flex'">Request certification documentation</a>.</p>
    </div>
    @endif
</div>

{{-- DATASHEETS & DOWNLOADS --}}
<div class="panel prod-section">
    <h3 class="prod-section-title">Datasheets &amp; Downloads</h3>
    @if($documents->isNotEmpty())
    <div class="prod-downloads">
        @foreach($documents as $doc)
        @php
            $docType = $doc->document_type ?? 'document';
            $docTitle = $doc->title ?? ucfirst($docType);
            $docUrl = $doc->file_url ?: ($doc->source_url ?? null);
            $docIcon = match(strtolower($docType)) {
                'datasheet' => '📄', 'manual' => '📘', 'cad' => '📐',
                'step' => '🔧', 'firmware' => '💾', 'certificate' => '✅',
                'declaration' => '📋', 'test_report' => '📊', 'brochure' => '📰',
                'safety_data_sheet' => '⚠️', default => '📎'
            };
        @endphp
        <div class="prod-download-item">
            <span class="prod-download-icon">{{ $docIcon }}</span>
            <div class="prod-download-info">
                <strong>{{ $docTitle }}</strong>
                <span class="sub">{{ ucfirst($docType) }}{{ isset($doc->version) ? ' · v'.$doc->version : '' }}{{ isset($doc->language) ? ' · '.$doc->language : '' }}{{ isset($doc->file_size) ? ' · '.$doc->file_size : '' }}</span>
            </div>
            @if($docUrl)<a href="{{ $docUrl }}" class="btn btn-ghost" style="font-size:.78rem" target="_blank" rel="noopener" download>Download</a>@endif
        </div>
        @endforeach
    </div>
    @else
    <div class="prod-empty">
        <p class="sub">No verified technical documents are currently available.</p>
        <button type="button" class="btn btn-ghost" style="margin-top:6px" onclick="document.getElementById('chat-modal-{{ $product->id }}').style.display='flex'">Request Documentation</button>
    </div>
    @endif
</div>

{{-- SHIPPING & PACKAGING --}}
@php $pkg = data_get($product->metadata, 'packaging', []); @endphp
@if(!empty($pkg) || data_get($product->metadata, 'package_type'))
<div class="panel prod-section">
    <h3 class="prod-section-title">Shipping &amp; Packaging</h3>
    <table class="prod-id-table">
        @if(data_get($product->metadata, 'package_type'))<tr><th>Package Type</th><td>{{ data_get($product->metadata, 'package_type') }}</td></tr>@endif
        @if(data_get($product->metadata, 'weight_grams'))<tr><th>Weight</th><td>{{ data_get($product->metadata, 'weight_grams') }} g</td></tr>@endif
        @if(data_get($product->metadata, 'dimensions_mm'))<tr><th>Dimensions</th><td>{{ data_get($product->metadata, 'dimensions_mm') }} mm</td></tr>@endif
        @if(data_get($product->metadata, 'lead_time_days'))<tr><th>Lead Time</th><td>{{ data_get($product->metadata, 'lead_time_days') }} days</td></tr>@endif
    </table>
</div>
@endif

{{-- ALTERNATIVES & ACCESSORIES --}}
@if($alternatives->isNotEmpty())
<div class="panel prod-section">
    <h3 class="prod-section-title">Alternatives &amp; Accessories</h3>
    <div class="prod-alt-grid">
        @foreach($alternatives as $alt)
        <div class="prod-alt-item">
            <strong><a href="{{ $alt->slug ? $publicBase.'/products/'.$alt->slug : '#' }}">{{ $alt->name ?? 'Related product' }}</a></strong>
            <span class="sub">{{ $alt->relation_type ?? 'Alternative' }}{{ ($alt->mpn ?: $alt->sku) ? ' · '.($alt->mpn ?: $alt->sku) : '' }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- REVIEWS --}}
<div class="panel prod-section">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
        <h3 class="prod-section-title" style="margin:0">Reviews &amp; Feedback</h3>
        @if($reviewCount > 0)<span class="badge b-info">★ {{ $reviewBadge }}</span>@endif
    </div>
    @forelse($reviews as $review)
        <div class="prod-review">
            <div style="display:flex;justify-content:space-between;gap:10px;align-items:start">
                <strong>{{ $review->title ?: 'Product review' }}</strong>
                <span class="badge b-ok">★ {{ $review->rating }}/5</span>
            </div>
            <p style="margin:6px 0">{{ $review->body }}</p>
            <span class="sub">{{ $review->reviewer_name ?: 'Customer' }}{{ $review->use_case ? ' · '.$review->use_case : '' }}</span>
        </div>
    @empty
        <p class="sub" style="margin-top:8px">Approved reviews appear after moderation.</p>
    @endforelse
</div>

{{-- REVIEW FORM --}}
<details class="panel prod-section" style="cursor:default">
    <summary class="prod-section-title" style="cursor:pointer">Submit a Review ▾</summary>
    <form class="prod-review-form" method="post" action="{{ route('products.reviews.store', $product->slug) }}" style="margin-top:12px">@csrf
        <div class="prod-form-grid">
            <select class="control" name="rating" required><option value="">Rating</option><option value="5">5 - Excellent</option><option value="4">4 - Good</option><option value="3">3 - Average</option><option value="2">2 - Limited</option><option value="1">1 - Poor</option></select>
            <input class="control" name="title" maxlength="180" placeholder="Short title">
            <textarea class="control" name="body" rows="4" required minlength="10" maxlength="2500" placeholder="Share build quality, compatibility, or sourcing feedback"></textarea>
            <input class="control" name="use_case" maxlength="160" placeholder="Use case (optional)">
            @if(!auth()->check())
            <input class="control" name="reviewer_name" maxlength="120" placeholder="Name">
            <input class="control" type="email" name="reviewer_email" maxlength="190" placeholder="Email">
            @endif
            <button class="btn btn-primary" type="submit">Submit for Moderation</button>
        </div>
    </form>
</details>

</div>{{-- /prod-main --}}

{{-- ===== RIGHT SIDEBAR (30%) ===== --}}
<aside class="prod-sidebar" id="prod-sidebar">
<div class="panel prod-sidebar-card">
    <div class="prod-price-block">
        @if($displayPrice)
            <div class="sub">{{ $marketplacePrice?->marketplace_name ?: ($marketplaceContext['current']?->name ?? 'Regional Price') }}</div>
            <strong class="prod-price">{{ $displayCurrency }} {{ number_format((float) $displayPrice, 2) }}</strong>
            @if($marketplacePrice?->is_tax_inclusive)<div class="sub">Tax inclusive</div>@endif
            @if($marketplacePrice?->sale_price && $marketplacePrice->base_price > $marketplacePrice->sale_price)
                <div class="sub" style="text-decoration:line-through">{{ $priceCurrency }} {{ number_format((float) $marketplacePrice->base_price, 2) }}</div>
            @endif
        @else
            <strong class="prod-price">RFQ Pricing</strong>
            <div class="sub">Regional price not published. Request a quote below.</div>
        @endif
    </div>

    <div class="prod-stock-status {{ $inStock ? 'in-stock' : '' }}">
        <span class="prod-stock-dot"></span>
        {{ $inStock ? number_format((int)($product->stock_quantity ?? 0)).' in stock' : 'Request availability' }}
    </div>

    {{-- Warehouse stock summary --}}
    @if($stockRows->isNotEmpty())
    <div class="prod-warehouse-pills">
        @foreach($stockRows->take(4) as $row)
        <div class="prod-wh-pill {{ $row->quantity_available > 0 ? '' : 'empty' }}">
            <strong>{{ $row->warehouse_name ?? 'Warehouse' }}</strong>
            <span>{{ $row->country_name ?? 'Global' }} · {{ number_format((int)$row->quantity_available) }}</span>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Quantity --}}
    <div class="prod-qty-row" id="prod-sidebar-add">
        <label class="prod-qty-label">Quantity</label>
        <div class="prod-qty-input">
            <button type="button" class="prod-qty-btn" data-qty-change="-1" aria-label="Decrease quantity">−</button>
            <input type="number" id="prod-qty" class="control" value="1" min="1" max="{{ max(1, (int)($product->stock_quantity ?: 500)) }}" aria-label="Quantity">
            <button type="button" class="prod-qty-btn" data-qty-change="1" aria-label="Increase quantity">+</button>
        </div>
    </div>

    {{-- Actions --}}
    <div class="prod-actions">
        <form method="post" action="/cart/items" class="prod-cart-form" onsubmit="return validateCartAdd(this, {{ max(1, (int)($product->stock_quantity ?: 500)) }})">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <input type="hidden" name="quantity" id="prod-cart-qty" value="1">
            <button class="btn btn-primary" type="submit" style="width:100%">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2l1.5 3M18 2l-1.5 3M3 6h18l-2 12H5L3 6z" stroke-linejoin="round"/></svg>
                Add to Cart
            </button>
        </form>
        @if($inStock && $displayPrice)
        <button class="btn btn-gold" type="button" style="width:100%" onclick="buyNow({{ $product->id }})">Buy Now</button>
        @endif
        <a class="btn btn-ghost" href="/rfq?product={{ $product->slug }}" style="width:100%;justify-content:center">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            Request Bulk Quote
        </a>
        <div class="prod-action-row">
            <button class="btn btn-ghost prod-action-sm" onclick="addToBom({{ $product->id }}, '{{ $product->slug }}', '{{ addslashes($displayName) }}')">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2v0a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Add to BOM
            </button>
            <a href="/en/compare?p={{ $product->slug }}" class="btn btn-ghost prod-action-sm">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 00-2 2v14c0 1.1.9 2 2 2h3m8-18h3a2 2 0 012 2v14a2 2 0 01-2 2h-3M3 12h18"/></svg>
                Compare
            </button>
            <button class="btn btn-ghost prod-action-sm saved-btn" data-product="{{ $product->id }}" data-toggle-save="1">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.7l-1-1a5.5 5.5 0 00-7.8 7.8l1 1L12 21l7.8-7.8 1-1a5.5 5.5 0 000-7.8z"/></svg>
                Save
            </button>
        </div>
        <button type="button" class="btn btn-ghost" style="width:100%;justify-content:center" onclick="document.getElementById('chat-modal-{{ $product->id }}').style.display='flex'">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
            Chat with Seller
        </button>
        <a class="btn btn-ghost" href="/ai-commerce?part={{ urlencode($product->mpn ?: $product->sku ?: $displayName) }}" style="width:100%;justify-content:center">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a10 10 0 0110 10M12 2a10 10 0 000 20M12 2A10 10 0 002 12M12 2a10 10 0 000 20"/></svg>
            Ask AI Engineer
        </a>
    </div>

    <p class="prod-secure-note">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
        Secure transaction · B2B pricing available via RFQ
    </p>
</div>

{{-- Seller offers --}}
@if($sellerOffers->isNotEmpty())
<div class="panel prod-sidebar-card">
    <h4 style="margin:0 0 10px;font-size:.9rem">Seller Offers</h4>
    @foreach($sellerOffers->take(3) as $offer)
    <div class="prod-seller-row">
        @php $offerCurrency = $offer->currency_native_symbol ?: ($offer->currency_symbol ?: $offer->currency_code); @endphp
        <div>
            <strong>{{ $offer->vendor_name ?? 'Seller' }}</strong>
            @if($offer->is_verified)<span class="badge b-ok" style="font-size:.65rem">Verified</span>@endif
        </div>
        <div class="sub">{{ $offerCurrency }} {{ number_format((float)$offer->selling_price, 2) }}{{ $offer->moq ? ' · MOQ: '.$offer->moq : '' }}</div>
    </div>
    @endforeach
    @if($sellerOffers->count() > 3)<a class="sub" href="/rfq?product={{ $product->slug }}" style="font-size:.78rem">View {{ $sellerOffers->count() - 3 }} more offers →</a>@endif
</div>
@endif
</aside>

</div>{{-- /prod-layout --}}
</div>{{-- /prod-wrap --}}

{{-- RELATED PRODUCTS --}}
@if($related->isNotEmpty())
<div class="prod-wrap">
    <div class="prod-section">
        <div class="prod-related-head">
            <h3>Related Products</h3>
            @if($product->category)<a class="btn btn-ghost" href="{{ $publicBase }}/categories/{{ $product->category->slug }}">View all in {{ $product->category->name }}</a>@endif
        </div>
        <div class="prod-related-grid">
            @foreach($related as $r)
            @php $relImg = $r->images->first(); @endphp
            <a class="prod-related-card" href="{{ $publicBase }}/products/{{ $r->slug }}">
                <div class="prod-related-img">
                    <img src="{{ $relImg?->publicUrl() ?: url('/images/products/neogiga-product-placeholder-2026.png') }}" alt="{{ $r->name }}" width="200" height="150" loading="lazy" decoding="async">
                </div>
                <div class="prod-related-info">
                    <strong>{{ \Illuminate\Support\Str::limit($r->name, 52) }}</strong>
                    <span class="sub">@if($r->brand){{ $r->brand->name }}@endif{{ $r->mpn ? ' · '.$r->mpn : '' }}{{ !$r->brand && !$r->mpn && $r->sku ? 'SKU: '.$r->sku : '' }}</span>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endif

{{-- Include chat modal --}}
@include('components.chat-seller-modal', ['product' => $product])

<script nonce="{{ $csp_nonce ?? '' }}">
// Gallery navigation
var galleryImages = @json($galleryImages->map(fn($i) => ['src'=>$i->publicUrl(),'alt'=>$i->alt_text ?: $displayName.' image'])->values()->all());
var galleryIndex = 0;

function setGalleryImage(idx) {
    if(!galleryImages.length) return;
    galleryIndex = ((idx % galleryImages.length) + galleryImages.length) % galleryImages.length;
    var img = galleryImages[galleryIndex];
    document.getElementById('prod-gallery-main-img').src = img.src;
    document.getElementById('prod-gallery-main-img').alt = img.alt;
    document.getElementById('prod-gallery-zoom').href = img.src;
    document.querySelectorAll('.prod-gallery-thumb').forEach(function(t,i){ t.classList.toggle('active', i === galleryIndex); });
}
function galleryNav(dir) { setGalleryImage(galleryIndex + dir); }
document.querySelectorAll('.prod-gallery-thumb').forEach(function(btn){
    btn.addEventListener('click',function(){ setGalleryImage(parseInt(this.dataset.index)); });
});

// Quantity
function qtyChange(delta) {
    var input = document.getElementById('prod-qty');
    var val = parseInt(input.value) || 1;
    input.value = Math.max(1, Math.min(parseInt(input.max), val + delta));
    document.getElementById('prod-cart-qty').value = input.value;
}
document.getElementById('prod-qty').addEventListener('change',function(){
    var v = parseInt(this.value) || 1;
    this.value = Math.max(1, Math.min(parseInt(this.max), v));
    document.getElementById('prod-cart-qty').value = this.value;
});

function validateCartAdd(form, maxQty) {
    var qty = parseInt(document.getElementById('prod-qty').value) || 1;
    if(qty > maxQty) { alert('Maximum available: '+maxQty); return false; }
    form.querySelector('[name=quantity]').value = qty;
    var btn = form.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.textContent = 'Adding...';
    setTimeout(function(){ btn.disabled = false; btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2l1.5 3M18 2l-1.5 3M3 6h18l-2 12H5L3 6z" stroke-linejoin="round"/></svg> Add to Cart'; }, 2000);
    return true;
}

function buyNow(pid) {
    var qty = document.getElementById('prod-qty').value;
    var form = document.createElement('form');
    form.method = 'post'; form.action = '/cart/items';
    form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="product_id" value="'+pid+'"><input type="hidden" name="quantity" value="'+qty+'"><input type="hidden" name="redirect" value="checkout">';
    document.body.appendChild(form); form.submit();
}

function addToBom(id, slug, name) {
    var qty = document.getElementById('prod-qty').value;
    try {
        var bom = JSON.parse(localStorage.getItem('neogiga_bom') || '[]');
        var exists = bom.find(function(i){ return i.id === id; });
        if(exists) { exists.qty += parseInt(qty); }
        else { bom.push({id:id, slug:slug, name:name, qty:parseInt(qty)}); }
        localStorage.setItem('neogiga_bom', JSON.stringify(bom));
        var btn = event.target;
        btn.textContent = '✓ Added!';
        setTimeout(function(){ btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2v0a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg> Add to BOM'; }, 2000);
    } catch(e) { alert('BOM storage unavailable.'); }
}

function toggleSave(btn) {
    var pid = btn.dataset.product;
    try {
        var saved = JSON.parse(localStorage.getItem('neogiga_saved') || '[]');
        var idx = saved.indexOf(pid);
        if(idx > -1) { saved.splice(idx,1); btn.classList.remove('saved'); btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.7l-1-1a5.5 5.5 0 00-7.8 7.8l1 1L12 21l7.8-7.8 1-1a5.5 5.5 0 000-7.8z"/></svg> Save'; }
        else { saved.push(pid); btn.classList.add('saved'); btn.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.7l-1-1a5.5 5.5 0 00-7.8 7.8l1 1L12 21l7.8-7.8 1-1a5.5 5.5 0 000-7.8z"/></svg> Saved'; }
        localStorage.setItem('neogiga_saved', JSON.stringify(saved));
    } catch(e) {}
}
document.addEventListener('DOMContentLoaded',function(){
    try{ if(JSON.parse(localStorage.getItem('neogiga_saved')||'[]').indexOf('{{ $product->id }}')>-1){ var b=document.querySelector('.saved-btn'); if(b){b.classList.add('saved');b.innerHTML='<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 00-7.8 0L12 5.7l-1-1a5.5 5.5 0 00-7.8 7.8l1 1L12 21l7.8-7.8 1-1a5.5 5.5 0 000-7.8z"/></svg> Saved';}} }catch(e){}
});

// Mobile sticky bar
var mobileBar = document.getElementById('prod-mobile-bar');
if(mobileBar && window.innerWidth < 768) {
    var lastScroll = 0;
    window.addEventListener('scroll',function(){
        var s = window.pageYOffset;
        mobileBar.classList.toggle('visible', s > 400 && s < lastScroll);
        lastScroll = s;
    });
}
</script>

<style nonce="{{ $csp_nonce ?? '' }}">
/* ===== PROFESSIONAL PRODUCT PAGE — ENGINEERING MARKETPLACE ===== */
.prod-wrap{width:min(1280px,calc(100% - 36px));margin-inline:auto}
.prod-layout{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:16px;align-items:start;padding:14px 0}
.prod-main{min-width:0;display:grid;gap:14px}
.prod-breadcrumb{display:flex;gap:4px;align-items:center;flex-wrap:wrap;font-size:.76rem;color:var(--muted);padding:6px 0 2px}
.prod-breadcrumb a{color:var(--muted);transition:color .15s}.prod-breadcrumb a:hover{color:var(--cyan)}
.prod-breadcrumb strong{color:var(--on);font-weight:600}

/* Top section — tighter, balanced */
.prod-top{padding:16px 18px}
.prod-top-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.35fr);gap:18px;align-items:start}

/* Gallery — compact square */
.prod-gallery{min-width:0}
.prod-gallery-main-wrap{position:relative;aspect-ratio:1/1;border-radius:8px;overflow:hidden;background:var(--bg2,#f8fafc);border:1px solid var(--line)}
.prod-gallery-main{display:grid;place-items:center;width:100%;height:100%;position:relative}
.prod-gallery-main img{width:100%;height:100%;object-fit:contain;background:transparent}
.prod-gallery-prev,.prod-gallery-next{position:absolute;top:50%;transform:translateY(-50%);z-index:5;background:var(--s1,#fff);border:1px solid var(--line);border-radius:50%;width:32px;height:32px;display:grid;place-items:center;cursor:pointer;color:var(--slate,#334155);box-shadow:0 1px 4px rgba(0,0,0,.06);transition:all .15s;opacity:.9}
.prod-gallery-prev:hover,.prod-gallery-next:hover{border-color:var(--cyan);color:var(--cyan);opacity:1}
.prod-gallery-prev{left:6px}.prod-gallery-next{right:6px}
.prod-gallery-count{position:absolute;bottom:6px;right:6px;background:rgba(15,23,42,.75);color:#fff;font-size:.66rem;padding:2px 7px;border-radius:999px;font-weight:600}
.prod-gallery-thumbs{display:flex;gap:5px;margin-top:7px;overflow-x:auto;padding:1px 0}
.prod-gallery-thumb{flex:none;width:56px;height:56px;border:2px solid var(--line);border-radius:6px;overflow:hidden;cursor:pointer;background:var(--s1,#fff);padding:0;transition:border-color .15s}
.prod-gallery-thumb.active{border-color:var(--cyan);box-shadow:0 0 0 1px rgba(15,98,230,.15)}
.prod-gallery-thumb img{width:100%;height:100%;object-fit:contain}

/* Identity — refined typography */
.prod-badges{display:flex;gap:5px;flex-wrap:wrap;margin-bottom:8px}
.prod-title{font-size:clamp(1.25rem,2.2vw,1.6rem);font-weight:700;line-height:1.18;margin:0 0 8px;letter-spacing:-.01em;color:var(--ink,var(--on))}
.prod-meta-line{display:flex;gap:6px 14px;flex-wrap:wrap;font-size:.8rem;color:var(--slate,var(--muted));margin-bottom:8px;line-height:1.5}
.prod-meta-line a{color:var(--cyan);font-weight:500}.prod-meta-line .mono{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:.76rem;color:var(--muted)}
.prod-rating{display:flex;align-items:center;gap:6px;font-size:.82rem;margin-bottom:8px;color:var(--slate)}
.prod-rating .star{color:#d1d5db;font-size:.95rem}.prod-rating .star.filled{color:#f59e0b}
.prod-excerpt{font-size:.88rem;color:var(--slate,var(--soft));line-height:1.58;margin:0}

/* Sections — tighter */
.prod-section{padding:14px 18px}
.prod-section-title{font-size:.95rem;font-weight:700;margin:0 0 10px;letter-spacing:-.01em;color:var(--ink,var(--on))}
.prod-section-title:only-child{margin-bottom:0}

/* Identity table — engineering spec style */
.prod-id-table{width:100%;border-collapse:collapse;font-size:.84rem}
.prod-id-table th{text-align:left;color:var(--muted);font-weight:600;font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;padding:5px 12px 5px 0;width:190px;vertical-align:top;border-bottom:1px solid #f1f5f9}
.prod-id-table td{padding:5px 0;border-bottom:1px solid #f1f5f9;word-break:break-word;color:var(--on)}
.prod-id-table .prod-na{color:var(--faint);font-style:italic}
.prod-id-table .prod-sub{color:var(--faint);font-size:.74rem}

/* Specs table */
.prod-spec-group{font-size:.76rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:var(--slate);margin:14px 0 6px;padding-bottom:4px;border-bottom:1px solid var(--line)}
.prod-spec-table{width:100%;border-collapse:collapse;font-size:.84rem}
.prod-spec-table tr:nth-child(even){background:rgba(0,0,0,.012)}
.prod-spec-table th{text-align:left;color:var(--muted);font-weight:500;padding:5px 12px 5px 0;width:210px;vertical-align:top;font-size:.78rem}
.prod-spec-table td{padding:5px 0;vertical-align:top;color:var(--on)}
.prod-unit{color:var(--faint);font-size:.74rem;margin-left:3px}

/* Description — strong contrast, readable */
.prod-description{font-size:.9rem;line-height:1.68;color:var(--on,#1e2a36)}
.prod-description p{margin:0 0 10px}.prod-description ul,.prod-description ol{padding-left:20px;margin:8px 0}
.prod-description li{margin-bottom:4px}

/* Certifications */
.prod-cert-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:8px}
.prod-cert-badge{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1px solid var(--line);border-radius:8px;background:var(--s1,#fff)}
.prod-cert-icon{flex:none;width:44px;height:44px;display:grid;place-items:center;background:var(--s1,#fff);border-radius:6px;border:1px solid #eef2f6}
.prod-cert-icon img{width:36px;height:36px;object-fit:contain}
.prod-cert-text-badge{font-size:.7rem;font-weight:700;color:var(--slate);text-align:center;line-height:1.2}
.prod-cert-info strong{display:block;font-size:.82rem;color:var(--on)}.prod-cert-info .sub{font-size:.7rem}
.prod-cert-info a{font-size:.72rem}

/* Downloads — professional file rows */
.prod-downloads{display:grid;gap:4px}
.prod-download-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border:1px solid var(--line);border-radius:7px;transition:border-color .15s}
.prod-download-item:hover{border-color:var(--cyan)}
.prod-download-icon{font-size:1.2rem;flex:none;opacity:.8}
.prod-download-info{flex:1;min-width:0}.prod-download-info strong{display:block;font-size:.82rem;color:var(--on)}.prod-download-info .sub{font-size:.7rem}

/* Alternatives */
.prod-alt-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:6px}
.prod-alt-item{padding:9px 12px;border:1px solid var(--line);border-radius:7px}
.prod-alt-item strong{display:block;font-size:.82rem}.prod-alt-item strong a{color:var(--cyan)}
.prod-alt-item .sub{font-size:.72rem}

/* Reviews */
.prod-review{padding:10px 0;border-bottom:1px solid #f1f5f9}
.prod-review:last-child{border-bottom:0}
.prod-review p{margin:4px 0;color:var(--on);font-size:.88rem;line-height:1.55}
.prod-review-form{display:grid;gap:6px}
.prod-form-grid{display:grid;gap:6px}

/* Empty state */
.prod-empty{padding:14px 18px;text-align:center}
.prod-empty p{color:var(--muted);font-size:.86rem;margin:0 0 6px}
.prod-empty .btn{margin-top:4px}

/* ===== SIDEBAR — POLISHED ===== */
.prod-sidebar{position:sticky;top:80px;display:grid;gap:12px}
.prod-sidebar-card{padding:14px 16px;background:var(--s1,#fff)}
.prod-price-block{margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid var(--line)}
.prod-price{font-size:1.5rem;font-weight:800;display:block;margin:1px 0;color:var(--ink,var(--on))}
.prod-price-block .sub{color:var(--muted);font-size:.76rem}
.prod-stock-status{display:flex;align-items:center;gap:6px;font-size:.82rem;font-weight:600;color:var(--muted);margin-bottom:10px}
.prod-stock-dot{width:8px;height:8px;border-radius:50%;background:#d1d5db;flex:none}
.prod-stock-status.in-stock{color:#059669}.prod-stock-status.in-stock .prod-stock-dot{background:#059669}

.prod-warehouse-pills{display:grid;gap:3px;margin-bottom:12px}
.prod-wh-pill{display:flex;justify-content:space-between;align-items:center;padding:5px 9px;background:var(--bg2,#f8fafc);border-radius:5px;font-size:.76rem}
.prod-wh-pill.empty{opacity:.45}

.prod-qty-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;gap:8px}
.prod-qty-label{font-size:.74rem;font-weight:700;text-transform:uppercase;color:var(--muted);letter-spacing:.04em}
.prod-qty-input{display:flex;align-items:center;gap:0}
.prod-qty-input input{width:60px;text-align:center;min-height:36px;border-radius:0;border-left:0;border-right:0;font-weight:700;font-size:.9rem}
.prod-qty-btn{width:36px;height:36px;border:1px solid var(--line);background:var(--s1,#fff);font-size:1rem;font-weight:700;cursor:pointer;display:grid;place-items:center;transition:all .12s;color:var(--slate)}
.prod-qty-btn:first-child{border-radius:6px 0 0 6px}
.prod-qty-btn:last-child{border-radius:0 6px 6px 0}
.prod-qty-btn:hover{border-color:var(--cyan);color:var(--cyan);background:rgba(15,98,230,.04)}

.prod-actions{display:grid;gap:5px}
.prod-actions .btn{min-height:40px;font-size:.86rem;font-weight:600;border-radius:7px}
.prod-actions .btn-primary{background:var(--cyan,#0f62e6);color:#fff;font-weight:700}
.prod-actions .btn-gold{background:var(--gold,#f59e0b);color:#3b2300;font-weight:700}
.prod-actions .btn-ghost{background:var(--s1,#fff);border-color:var(--line);color:var(--slate)}
.prod-actions .btn-ghost:hover{border-color:var(--cyan);color:var(--cyan)}
.prod-cart-form{display:contents}
.prod-action-row{display:grid;grid-template-columns:1fr 1fr;gap:5px}
.prod-action-sm{font-size:.76rem !important;justify-content:center !important;padding:0 8px !important;min-height:34px !important;border-radius:6px !important}
.saved-btn.saved{color:#ef4444 !important;border-color:#fecaca !important}
.prod-secure-note{display:flex;align-items:center;gap:5px;font-size:.68rem;color:var(--faint);margin:8px 0 0;text-align:center;justify-content:center}

.prod-seller-row{padding:7px 0;border-bottom:1px solid #f1f5f9}
.prod-seller-row:last-child{border-bottom:0}
.prod-seller-row strong{font-size:.8rem}.prod-seller-row .sub{font-size:.72rem}

/* Related products — compact professional cards */
.prod-related-head{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:12px}
.prod-related-head h3{font-size:1.05rem;margin:0;font-weight:700;color:var(--ink,var(--on))}
.prod-related-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:10px}
.prod-related-card{display:block;background:var(--s1,#fff);border:1px solid var(--line);border-radius:8px;overflow:hidden;transition:box-shadow .15s,border-color .15s}
.prod-related-card:hover{border-color:rgba(15,98,230,.35);box-shadow:0 6px 20px rgba(0,0,0,.06)}
.prod-related-img{aspect-ratio:4/3;background:var(--bg2,#f8fafc);display:grid;place-items:center;padding:10px}
.prod-related-img img{width:100%;height:100%;object-fit:contain}
.prod-related-info{padding:9px 10px}
.prod-related-info strong{display:block;font-size:.8rem;line-height:1.3;margin-bottom:2px;color:var(--on);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.prod-related-info .sub{font-size:.7rem;color:var(--faint)}

/* Mobile sticky bar */
.prod-mobile-bar{display:none;position:fixed;bottom:0;left:0;right:0;background:var(--s1,#fff);border-top:1px solid var(--line);z-index:100;padding:10px 14px;box-shadow:0 -4px 24px rgba(0,0,0,.1)}
.prod-mobile-bar-inner{display:flex;justify-content:space-between;align-items:center;gap:10px}
.prod-mobile-bar-inner strong{font-size:.92rem;color:var(--on)}
.prod-mobile-stock{font-size:.7rem;color:var(--muted);margin-left:6px}
.prod-mobile-stock.in-stock{color:#059669}
.prod-mobile-bar .btn{min-height:38px;padding:0 16px;font-size:.84rem}

/* Tablet */
@media (max-width:1024px){
    .prod-layout{grid-template-columns:1fr;gap:12px}
    .prod-sidebar{position:static;grid-template-columns:repeat(auto-fit,minmax(280px,1fr))}
}
@media (max-width:768px){
    .prod-top-grid{grid-template-columns:1fr;gap:12px}
    .prod-mobile-bar{display:block}
    .prod-id-table th{width:130px}
    .prod-spec-table th{width:130px}
    .prod-cert-grid{grid-template-columns:1fr}
    .prod-related-grid{grid-template-columns:repeat(auto-fill,minmax(140px,1fr))}
}
@media (max-width:480px){
    .prod-wrap{width:calc(100% - 18px)}
    .prod-layout{padding:8px 0}
    .prod-top{padding:10px 12px}
    .prod-section{padding:10px 12px}
    .prod-title{font-size:1.15rem}
    .prod-meta-line{font-size:.72rem}
    .prod-action-row{grid-template-columns:1fr}
    .prod-related-grid{grid-template-columns:repeat(2,1fr)}
}
</style>
@endsection
