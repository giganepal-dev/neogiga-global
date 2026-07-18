@props(['product'])

@php
    $metadata = is_array($product->metadata ?? null) ? $product->metadata : [];
    $authenticityVerified = (bool) data_get($metadata, 'authenticity_verified', false);
    $qualifiedSource = data_get($metadata, 'jlcpcb_qualified_publication_v1.confidence_level') === 'high_gate_confidence';
    $stock = (int) ($product->stock_quantity ?? 0);
    $threshold = max(0, (int) ($product->low_stock_threshold ?? 0));
    $recentSales = (int) (data_get($metadata, 'commerce.sales_last_30_days') ?? data_get($metadata, 'sales_last_30_days') ?? 0);
    $tags = [];

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
