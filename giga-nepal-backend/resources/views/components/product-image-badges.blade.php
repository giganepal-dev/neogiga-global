@props(['product'])

@once
    <style nonce="{{ $csp_nonce ?? '' }}">
        .product-img,.product-gallery-main{position:relative}
        .product-image-badges{position:absolute;z-index:2;top:8px;left:8px;display:flex;flex-wrap:wrap;gap:5px;max-width:calc(100% - 16px);pointer-events:none}
        .product-image-badge{display:inline-flex;align-items:center;min-height:22px;padding:2px 7px;border-radius:6px;font-size:.66rem;font-weight:800;letter-spacing:.02em;line-height:1;background:rgba(8,21,39,.92);border:1px solid rgba(255,255,255,.16);color:#fff;text-shadow:none;box-shadow:0 2px 9px rgba(0,0,0,.22)}
        .product-image-badge--cataloged{background:rgba(29,78,112,.94);border-color:rgba(125,211,252,.6)}
        .product-image-badge--genuine{background:rgba(5,105,70,.94);border-color:rgba(52,211,153,.65)}
        .product-image-badge--verified{background:rgba(7,89,133,.94);border-color:rgba(40,216,251,.65)}
        .product-image-badge--low{background:rgba(146,64,14,.95);border-color:rgba(249,189,44,.7)}
        .product-image-badge--hot{background:rgba(185,28,28,.95);border-color:rgba(252,165,165,.72)}
        .product-image-badge--featured{background:rgba(112,26,117,.95);border-color:rgba(232,121,249,.65)}
    </style>
@endonce

@php
    $metadata = is_array($product->metadata ?? null) ? $product->metadata : [];
    $authenticityVerified = (bool) data_get($metadata, 'authenticity_verified', false);
    $qualifiedSource = data_get($metadata, 'jlcpcb_qualified_publication_v1.confidence_level') === 'high_gate_confidence';
    $stock = (int) ($product->stock_quantity ?? 0);
    $threshold = max(0, (int) ($product->low_stock_threshold ?? 0));
    $recentSales = (int) (data_get($metadata, 'commerce.sales_last_30_days') ?? data_get($metadata, 'sales_last_30_days') ?? 0);
    // Every listed catalog part receives a factual baseline badge. Stronger claims
    // below are only shown when their supporting product data is present.
    $tags = [['label' => 'Cataloged', 'class' => 'product-image-badge--cataloged']];

    if ($authenticityVerified) {
        $tags[] = ['label' => 'Genuine', 'class' => 'product-image-badge--genuine'];
    } elseif ($qualifiedSource) {
        $tags[] = ['label' => 'Source verified', 'class' => 'product-image-badge--verified'];
    }
    if ($threshold > 0 && $stock > 0 && $stock <= $threshold) {
        $tags[] = ['label' => 'Low stock', 'class' => 'product-image-badge--low'];
    }
    if ($recentSales >= 50) {
        $tags[] = ['label' => 'Selling fast', 'class' => 'product-image-badge--hot'];
    } elseif ($product->is_featured) {
        $tags[] = ['label' => 'Featured', 'class' => 'product-image-badge--featured'];
    }
@endphp

@if ($tags)
    <span class="product-image-badges" aria-label="Product status">
        @foreach ($tags as $tag)
            <span class="product-image-badge {{ $tag['class'] }}">{{ $tag['label'] }}</span>
        @endforeach
    </span>
@endif
