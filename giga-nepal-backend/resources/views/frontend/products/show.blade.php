@extends('frontend.layout')
@section('title', $product->name.' — NeoGiga')
@section('description', \Illuminate\Support\Str::limit(strip_tags($product->short_description ?: ($product->description ?: 'Technical specifications, stock and RFQ for '.$product->name.' on NeoGiga.')), 155))

@push('head')
<link rel="canonical" href="{{ url('/products/'.$product->slug) }}">
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product->name,
    'sku' => $product->sku,
    'mpn' => $product->mpn,
    'brand' => $product->brand?->name ? ['@type' => 'Brand', 'name' => $product->brand->name] : null,
    'category' => $product->category?->name,
    'url' => url('/products/'.$product->slug),
], JSON_UNESCAPED_SLASHES) !!}
</script>
@endpush

@section('content')
<style>
    .pd{display:grid;grid-template-columns:2fr 1fr;gap:24px;margin:24px 0}
    @media(max-width:820px){.pd{grid-template-columns:1fr}}
    .pd-card{border:1px solid rgba(15,23,42,.12);border-radius:12px;background:#fff;padding:24px}
    .pd h1{margin:0 0 6px;font-size:1.45rem}
    .pd-meta{color:#64748B;font-size:.9rem;margin-bottom:16px}
    .pd-meta strong{color:#0F172A}
    .spec-tbl{width:100%;border-collapse:collapse;margin-top:8px}
    .spec-tbl th,.spec-tbl td{text-align:left;padding:9px 12px;border-bottom:1px solid rgba(15,23,42,.08);font-size:.92rem}
    .spec-tbl th{width:40%;color:#475569;font-weight:600;background:#F8FAFC}
    .pstock{font-weight:700}.in{color:#065F46}.out{color:#991B1B}
    .cta-stack{display:flex;flex-direction:column;gap:10px}
    .cta-note{color:#64748B;font-size:.82rem}
    .rel{margin:8px 0 32px}
    .rel-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-top:12px}
    .rel-card{border:1px solid rgba(15,23,42,.12);border-radius:10px;padding:14px;background:#fff}
    .rel-card a{color:#0F172A;text-decoration:none;font-weight:600}.rel-card a:hover{color:#0369A1}
    .badge-soft{display:inline-block;background:#FEF9C3;color:#854D0E;border-radius:999px;padding:2px 10px;font-size:.75rem;font-weight:700}
</style>

<div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb">
        <a href="/">Home</a> › <a href="/products">Products</a>
        @if($product->category) › <a href="/products?category={{ $product->category->slug }}">{{ $product->category->name }}</a>@endif
        › <span>{{ $product->name }}</span>
    </nav>

    <div class="pd">
        <div class="pd-card">
            <h1>{{ $product->name }}</h1>
            <div class="pd-meta">
                @if($product->brand)Brand: <strong>{{ $product->brand->name }}</strong> · @endif
                @if($product->mpn)MPN: <strong class="mono">{{ $product->mpn }}</strong> · @endif
                SKU: <strong class="mono">{{ $product->sku ?? '—' }}</strong>
                @if($product->track_inventory)
                    · <span class="pstock {{ $product->stock_quantity > 0 ? 'in' : 'out' }}">{{ $product->stock_quantity > 0 ? 'In stock ('.number_format($product->stock_quantity).')' : 'Out of stock' }}</span>
                @endif
            </div>

            @if($product->short_description || $product->description)
                <p>{{ strip_tags($product->short_description ?: \Illuminate\Support\Str::limit(strip_tags($product->description), 500)) }}</p>
            @endif

            <h2 style="font-size:1.05rem;margin:20px 0 4px">Technical specifications</h2>
            @if ($product->specs->count())
                <table class="spec-tbl">
                    @foreach ($product->specs->sortBy('sort_order') as $s)
                        <tr><th>{{ $s->name }}</th><td>{{ $s->value }}{{ $s->unit ? ' '.$s->unit : '' }}</td></tr>
                    @endforeach
                </table>
            @else
                <table class="spec-tbl">
                    @if($product->mpn)<tr><th>Manufacturer Part Number</th><td class="mono">{{ $product->mpn }}</td></tr>@endif
                    @if($product->brand)<tr><th>Brand / Manufacturer</th><td>{{ $product->brand->name }}</td></tr>@endif
                    @if($product->category)<tr><th>Category</th><td>{{ $product->category->name }}</td></tr>@endif
                    @if($product->weight)<tr><th>Weight</th><td>{{ $product->weight }} {{ $product->weight_unit }}</td></tr>@endif
                    <tr><th>Datasheet</th><td>Available on request</td></tr>
                </table>
                <p class="cta-note">Full specification sheet is being loaded for this part.</p>
            @endif
        </div>

        <div class="pd-card">
            <h2 style="font-size:1.05rem;margin:0 0 12px">Get this part</h2>
            <div class="cta-stack">
                <a class="btn btn-primary" href="mailto:sales@neogiga.com?subject=Bulk%20RFQ%3A%20{{ rawurlencode($product->name) }}%20({{ rawurlencode($product->sku ?? '') }})">Request bulk quote (RFQ)</a>
                <span class="cta-note">B2B RFQs are answered with a formal quotation (RFQ → QUO workflow).</span>
                <a class="btn btn-ghost" href="/learn">Related tutorials on NeoGiga Learn</a>
                <span class="badge-soft">Ask AI Engineer — coming soon</span>
                <span class="cta-note">Sign in for B2B/wholesale pricing and regional warehouse stock. Regional availability follows your marketplace (Global · India · Nepal).</span>
                <a class="btn btn-ghost" href="/sell-on-neogiga">Sell a compatible part</a>
            </div>
        </div>
    </div>

    @if ($related->count())
        <div class="rel">
            <h2 style="font-size:1.1rem">Related products</h2>
            <div class="rel-grid">
                @foreach ($related as $r)
                    <div class="rel-card">
                        <a href="/products/{{ $r->slug }}">{{ $r->name }}</a>
                        <div class="cta-note">{{ $r->brand->name ?? '' }} {{ $r->mpn ? '· '.$r->mpn : '' }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
