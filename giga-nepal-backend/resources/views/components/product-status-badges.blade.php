@php
    $stockRows = collect($stockRows ?? []);
    $documents = collect($documents ?? []);
    $referencePrice = $referencePrice ?? null;
    $salePrice = $salePrice ?? null;
    $placement = $placement ?? 'inline';
    $limit = $limit ?? 3;
    $lifecycle = strtoupper(trim((string) ($product->lifecycle_status ?? '')));
    $lifecycleBadges = [
        'ACTIVE' => ['Active', 'ok'],
        'NEW_PRODUCT' => ['New Product', 'info'],
        'NOT_RECOMMENDED_FOR_NEW_DESIGNS' => ['Not Recommended for New Designs', 'warn'],
        'NRND' => ['Not Recommended for New Designs', 'warn'],
        'LAST_TIME_BUY' => ['Last Time Buy', 'danger'],
        'DISCONTINUED' => ['Discontinued', 'danger'],
        'OBSOLETE' => ['Obsolete', 'danger'],
        'NEEDS_VERIFICATION' => ['Status Unverified', 'muted'],
        'UNKNOWN' => ['Status Unverified', 'muted'],
    ];
    $badges = [];
    if (isset($lifecycleBadges[$lifecycle])) {
        [$label, $tone] = $lifecycleBadges[$lifecycle];
        $badges[] = compact('label', 'tone') + ['kind' => 'lifecycle', 'priority' => 10];
    }

    $verifiedQuantity = $stockRows->sum(fn ($row) => max(0, (int) data_get($row, 'quantity_available', 0)));
    if ($stockRows->isNotEmpty()) {
        $badges[] = $verifiedQuantity > 0
            ? ['label' => 'Regional Stock', 'tone' => 'info', 'kind' => 'stock', 'priority' => 20]
            : ['label' => 'Out of Stock', 'tone' => 'danger', 'kind' => 'stock', 'priority' => 20];
    } elseif ((bool) ($product->track_inventory ?? false)) {
        $quantity = max(0, (int) ($product->regional_available ?? $product->stock_quantity ?? 0));
        $threshold = max(1, (int) ($product->low_stock_threshold ?? 0));
        $badges[] = $quantity <= 0
            ? ['label' => 'Out of Stock', 'tone' => 'danger', 'kind' => 'stock', 'priority' => 20]
            : ($quantity <= $threshold
                ? ['label' => 'Limited Stock', 'tone' => 'warn', 'kind' => 'stock', 'priority' => 20]
                : ['label' => 'In Stock', 'tone' => 'ok', 'kind' => 'stock', 'priority' => 20]);
    }

    $referencePrice = is_numeric($referencePrice) ? (float) $referencePrice : 0.0;
    $salePrice = is_numeric($salePrice) ? (float) $salePrice : 0.0;
    if ($referencePrice > 0 && $salePrice > 0 && $salePrice < $referencePrice) {
        $badges[] = ['label' => round((1 - ($salePrice / $referencePrice)) * 100).'% Off', 'tone' => 'gold', 'kind' => 'discount', 'priority' => 30];
    } elseif ($salePrice <= 0) {
        $badges[] = ['label' => 'RFQ Pricing', 'tone' => 'gold', 'kind' => 'rfq', 'priority' => 31];
    }

    $hasDatasheet = $documents->contains(fn ($document) => strtolower((string) data_get($document, 'document_type')) === 'datasheet'
        && (data_get($document, 'file_url') || data_get($document, 'source_url')));
    if ($hasDatasheet) {
        $badges[] = ['label' => 'Datasheet Available', 'tone' => 'teal', 'kind' => 'datasheet', 'priority' => 40];
    }
    $badges = collect($badges)->sortBy('priority')->take(max(0, (int) $limit));
@endphp

@if($badges->isNotEmpty())
    <div class="product-status-badges product-status-badges--{{ $placement }}" aria-label="Product status">
        @foreach($badges as $badge)
            <span class="product-status-badge product-status-badge--{{ $badge['tone'] }} product-status-badge--{{ $badge['kind'] }}">{{ $badge['label'] }}</span>
        @endforeach
    </div>
@endif
