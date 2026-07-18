@extends('seller.layout')
@section('title', 'My Products')
@section('content')

<div class="card">
    <div class="card-h"><h2>My Products</h2><span class="badge b-muted">{{ number_format($products->total()) }} listed</span></div>
    <form class="filters" method="get">
        <input class="control" name="q" value="{{ $filters['q'] }}" placeholder="Search name, SKU, MPN">
        <select class="control" name="status"><option value="">All statuses</option>@foreach(['draft','pending','approved','rejected','archived'] as $s)<option value="{{ $s }}" @selected($filters['status']===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
        <button class="btn" type="submit"><x-icon name="filter" :size="16" /> Filter</button>
    </form>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Product</th><th>SKU / MPN</th><th class="num">Price</th><th class="num">Stock</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($products as $p)
            <tr>
                <td><strong>{{ $p->name }}</strong></td>
                <td class="mono">{{ $p->sku }}<div class="sub">{{ $p->mpn ?: '—' }}</div></td>
                <td class="num tnum">{{ $p->base_price !== null ? number_format($p->base_price, 2) : '—' }}</td>
                <td class="num tnum">{{ number_format($p->stock_quantity ?? 0) }}</td>
                <td><span class="badge {{ in_array($p->status, ['approved','active']) ? 'b-ok' : ($p->status === 'rejected' ? 'b-bad' : 'b-warn') }}">{{ ucfirst($p->status) }}</span></td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty"><h3>No products yet</h3><p>Submit products through your NeoGiga account manager or the seller API.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if ($products->hasPages())<div style="padding:12px 16px">{{ $products->links() }}</div>@endif
</div>

@endsection
