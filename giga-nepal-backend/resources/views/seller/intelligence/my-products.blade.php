@extends('seller.layout')
@section('title', 'My Product Performance')

@section('content')
<div class="page-intro page-intro--row">
    <div>
        <h1>My Product Performance</h1>
        <p>Track how your listed products are performing on NeoGiga.</p>
    </div>
</div>

@if(!$vendor)
<div class="card">
    <div class="empty-card">
        <p>You do not have an active seller account yet.</p>
        <a href="/seller/applications" class="btn" style="background:var(--accent);color:#fff;border-color:transparent">Apply to Sell</a>
    </div>
</div>
@else
<div class="card" style="margin-bottom:16px">
    <div class="filters">
        <form method="GET" action="/seller/intelligence/my-products" style="display:flex;gap:8px;flex:1">
            <input class="control" name="q" value="{{ request('q') }}" placeholder="Search by name, MPN, or SKU..." style="flex:1;min-width:200px">
            <button class="btn" type="submit">Search</button>
        </form>
    </div>

    <div style="overflow-x:auto">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>MPN</th>
                    <th>SKU</th>
                    <th>Status</th>
                    <th class="num">Views</th>
                    <th class="num">Rating</th>
                    <th class="num">Created</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                <tr>
                    <td style="font-weight:600;max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $product->name }}</td>
                    <td class="mono">{{ $product->mpn ?? '-' }}</td>
                    <td class="mono">{{ $product->sku ?? '-' }}</td>
                    <td><span class="badge {{ $product->status === 'approved' ? 'b-ok' : ($product->status === 'pending' ? 'b-warn' : 'b-muted') }}">{{ ucfirst($product->status) }}</span></td>
                    <td class="num tnum">{{ number_format($product->view_count) }}</td>
                    <td class="num tnum">{{ $product->rating_avg ? number_format($product->rating_avg, 1) : '-' }}</td>
                    <td class="sub">{{ $product->created_at->format('M d, Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-card"><p>No products found. <a href="/seller/products/add" style="color:var(--accent);font-weight:600">Add your first product</a>.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px">{{ $products->links() }}</div>
@endif
@endsection
