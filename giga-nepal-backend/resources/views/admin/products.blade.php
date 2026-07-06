@extends('admin.layout')
@section('title','Products')
@section('crumb','Catalog · '.number_format($products->total()).' items')
@section('content')

<div class="card">
    <div class="card-h">
        <div><h2>Products</h2><div class="sub">{{ number_format($products->total()) }} in catalog</div></div>
    </div>
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th>Name</th><th>SKU</th><th>Type</th><th class="num">Base price</th><th class="num">Stock</th><th>Status</th></tr></thead>
            <tbody>
            @forelse ($products as $p)
                <tr>
                    <td><strong>{{ $p->name }}</strong></td>
                    <td class="mono">{{ $p->sku }}</td>
                    <td>{{ $p->type ?? '—' }}</td>
                    <td class="num tnum">{{ $p->base_price !== null ? number_format($p->base_price, 2) : '—' }}</td>
                    <td class="num tnum">{{ number_format($p->stock_quantity ?? 0) }}</td>
                    <td>
                        @php $s = $p->status ?? 'draft'; @endphp
                        <span class="badge {{ $s==='approved'?'b-ok':($s==='draft'?'b-muted':'b-warn') }}">{{ ucfirst($s) }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">
                    <div class="empty">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M21 8l-9-5-9 5 9 5 9-5zM3 8v8l9 5 9-5V8" stroke-linejoin="round"/></svg>
                        <h3>No products yet</h3>
                        <p>The catalog is empty. Load products through the admin import pipeline, or enable demo data with <span class="mono">SEED_DEMO=true</span> in a non-production environment.</p>
                    </div>
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($products->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $products->links() }}</div>
    @endif
</div>

@endsection
