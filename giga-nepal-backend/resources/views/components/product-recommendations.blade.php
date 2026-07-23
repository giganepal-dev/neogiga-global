@props(['productId', 'type' => 'recommendations', 'title' => null, 'limit' => 6])

@php
    $titles = [
        'recommendations' => 'Recommended for You',
        'similar' => 'Similar Products',
        'alternatives' => 'Alternatives',
        'frequently-bought-together' => 'Frequently Bought Together',
    ];
    $displayTitle = $title ?? $titles[$type] ?? 'Related Products';
@endphp

<div class="rec-section" id="rec-{{ $type }}-{{ $productId }}" data-product-id="{{ $productId }}" data-type="{{ $type }}" data-limit="{{ $limit }}">
    <h3 style="font-size:1.1rem;font-weight:700;margin:0 0 16px">{{ $displayTitle }}</h3>
    <div class="rec-grid" id="rec-grid-{{ $type }}-{{ $productId }}">
        <div style="text-align:center;padding:20px;color:var(--faint);font-size:.85rem">Loading recommendations...</div>
    </div>
</div>

<style nonce="{{ $csp_nonce ?? '' }}">
.rec-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px}
.rec-card{background:#fff;border:1px solid var(--line);border-radius:10px;overflow:hidden;transition:box-shadow .15s,transform .15s;text-decoration:none;color:inherit}
.rec-card:hover{box-shadow:0 8px 24px rgba(23,43,77,.1);transform:translateY(-1px)}
.rec-card-img{height:120px;background:var(--s2);display:grid;place-items:center;overflow:hidden}
.rec-card-img img{width:100%;height:100%;object-fit:contain;padding:8px}
.rec-card-body{padding:10px 12px}
.rec-card-body .name{font-size:.82rem;font-weight:600;line-height:1.3;margin:0 0 4px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.rec-card-body .mpn{font-family:ui-monospace,monospace;font-size:.72rem;color:var(--faint)}
.rec-card-body .price{font-weight:700;color:var(--cyan);font-size:.9rem;margin-top:6px}
.rec-card-body .explanation{font-size:.72rem;color:var(--muted);margin-top:4px;line-height:1.3}
.rec-badge{display:inline-block;font-size:.65rem;padding:2px 6px;border-radius:999px;background:#e8f0fe;color:var(--cyan);font-weight:600;margin-top:4px}
.rec-empty{grid-column:1/-1;text-align:center;padding:24px;color:var(--faint);font-size:.85rem}
</style>

<script nonce="{{ $cspNonce ?? '' }}">
(function() {
    const container = document.getElementById('rec-{{ $type }}-{{ $productId }}');
    if (!container) return;

    const productId = container.dataset.productId;
    const type = container.dataset.type;
    const limit = container.dataset.limit;
    const grid = document.getElementById('rec-grid-{{ $type }}-{{ $productId }}');

    const endpoints = {
        'recommendations': '/api/v1/products/' + productId + '/recommendations?limit=' + limit,
        'similar': '/api/v1/products/' + productId + '/similar?limit=' + limit,
        'alternatives': '/api/v1/products/' + productId + '/recommendations?mode=best_value&limit=' + limit,
        'frequently-bought-together': '/api/v1/products/' + productId + '/frequently-bought-together?limit=' + limit,
    };

    fetch(endpoints[type] || endpoints.recommendations, { headers: { Accept: 'application/json' } })
        .then(r => r.json())
        .then(j => {
            if (!j.success || !j.data || j.data.length === 0) {
                grid.innerHTML = '<div class="rec-empty">No recommendations available yet.</div>';
                return;
            }
            grid.innerHTML = j.data.map(item => {
                const slug = item.slug || '#';
                const imgUrl = item.image_url || '/images/placeholder-product.png';
                const explanation = item.explanation ? '<div class="explanation">' + item.explanation + '</div>' : '';
                const badge = item.recommendation_type ? '<span class="rec-badge">' + item.recommendation_type.replace(/_/g, ' ') + '</span>' : '';
                return '<a href="/products/' + slug + '" class="rec-card">' +
                    '<div class="rec-card-img"><img src="' + imgUrl + '" alt="' + (item.name||'') + '" loading="lazy"></div>' +
                    '<div class="rec-card-body">' +
                    '<div class="name">' + (item.name || 'Product') + '</div>' +
                    '<div class="mpn">' + (item.mpn || item.sku || '') + '</div>' +
                    (item.price ? '<div class="price">$' + parseFloat(item.price).toFixed(2) + '</div>' : '') +
                    badge + explanation +
                    '</div></a>';
            }).join('');
        })
        .catch(() => {
            grid.innerHTML = '<div class="rec-empty">Could not load recommendations.</div>';
        });
})();
</script>
