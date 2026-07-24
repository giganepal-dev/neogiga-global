@extends('seller.layout')
@section('title', 'Seller Offers')
@section('content')

<div class="page-intro">
    <h1>Seller Offers</h1>
    <p>Manage your product offers across marketplaces.</p>
</div>

<div class="card">
    <div class="card-h">
        <h2>My Offers</h2>
        <span class="badge b-muted">{{ number_format($offers->total()) }} offers</span>
    </div>
    <form class="filters" method="get">
        <select class="control" name="status">
            <option value="">All statuses</option>
            @foreach(['active', 'inactive', 'out_of_stock', 'discontinued'] as $s)
                <option value="{{ $s }}" @selected($filters['status'] === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
            @endforeach
        </select>
        <button class="btn" type="submit"><x-icon name="filter" :size="16" /> Filter</button>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>MPN / Brand</th>
                    <th class="num">Price</th>
                    <th class="num">Stock</th>
                    <th>Warehouse</th>
                    <th>Marketplace</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($offers as $offer)
                <tr>
                    <td>
                        <strong>{{ $offer->product_name ?? 'Product #' . $offer->canonical_product_id }}</strong>
                        @if($offer->condition_grade && $offer->condition_grade !== 'new')
                            <div class="sub" style="font-size:.78rem">{{ ucfirst($offer->condition_grade) }} condition</div>
                        @endif
                    </td>
                    <td>
                        <span class="mono">{{ $offer->product_mpn ?? '—' }}</span>
                        @if($offer->product_brand)<div class="sub" style="font-size:.8rem">{{ $offer->product_brand }}</div>@endif
                    </td>
                    <td class="num tnum">
                        {{ number_format($offer->base_price, 2) }}
                        <span class="sub" style="font-size:.75rem">{{ $offer->currency_code }}</span>
                        @if($offer->sale_price)
                            <div class="sub" style="font-size:.78rem;color:var(--ok)">Sale: {{ number_format($offer->sale_price, 2) }}</div>
                        @endif
                    </td>
                    <td class="num tnum">
                        {{ number_format($offer->stock_quantity) }}
                        @if($offer->reserved_quantity > 0)
                            <div class="sub" style="font-size:.78rem">{{ $offer->reserved_quantity }} reserved</div>
                        @endif
                    </td>
                    <td class="sub">{{ $offer->warehouse_name ?? '—' }}</td>
                    <td class="sub">{{ $offer->marketplace_name ?? '—' }}</td>
                    <td>
                        <span class="badge {{ in_array($offer->status, ['active']) ? 'b-ok' : ($offer->status === 'discontinued' ? 'b-bad' : 'b-warn') }}">
                            {{ ucfirst(str_replace('_', ' ', $offer->status)) }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty">
                            <h3>No offers yet</h3>
                            <p>Create offers to list your products on NeoGiga marketplaces.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($offers->hasPages())
    <div style="padding:12px 16px">{{ $offers->links() }}</div>
    @endif
</div>

@endsection
