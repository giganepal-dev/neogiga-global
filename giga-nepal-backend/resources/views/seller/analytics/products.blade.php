@extends('seller.layout')
@section('title', 'Product Analytics')

@section('content')
<div class="page-intro page-intro--row">
    <div>
        <h1>Product Analytics</h1>
        <p>Detailed performance breakdown for all your products.</p>
    </div>
    <div>
        <a href="/seller/analytics" class="btn" style="background:var(--accent);color:#fff;border-color:transparent">Back to Dashboard</a>
    </div>
</div>

@if(!$vendor)
<div class="card">
    <div class="empty-card">
        <p>You do not have an active seller account yet.</p>
    </div>
</div>
@else

{{-- Top Products by Category --}}
@if(!empty($topByCategory))
<div class="card" style="margin-bottom:16px">
    <div class="card-h"><h2>Performance by Category</h2></div>
    <div style="overflow-x:auto">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Category</th>
                    <th class="num">Products</th>
                    <th class="num">Total Views</th>
                    <th class="num">Avg Rating</th>
                    <th class="num">Views/Product</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topByCategory as $cat)
                <tr>
                    <td style="font-weight:600">Category #{{ $cat->category_id ?? '—' }}</td>
                    <td class="num tnum">{{ number_format($cat->product_count) }}</td>
                    <td class="num tnum">{{ number_format($cat->total_views) }}</td>
                    <td class="num tnum">{{ $cat->avg_rating ? '★ ' . round($cat->avg_rating, 1) : '—' }}</td>
                    <td class="num tnum">{{ $cat->product_count > 0 ? number_format(round($cat->total_views / $cat->product_count)) : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- All Products --}}
<div class="card">
    <div class="card-h">
        <h2>All Products ({{ count($products) }})</h2>
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
                    <th class="num">Reviews</th>
                    <th class="num">Days Listed</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $prod)
                <tr>
                    <td style="font-weight:600;max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        {{ $prod['name'] }}
                    </td>
                    <td class="mono sub">{{ $prod['mpn'] ?? '—' }}</td>
                    <td class="mono sub">{{ $prod['sku'] ?? '—' }}</td>
                    <td>
                        <span class="badge {{ $prod['status'] === 'approved' ? 'b-ok' : ($prod['status'] === 'pending' ? 'b-warn' : 'b-muted') }}">
                            {{ ucfirst($prod['status']) }}
                        </span>
                    </td>
                    <td class="num tnum" style="font-weight:600">{{ number_format($prod['views']) }}</td>
                    <td class="num tnum">
                        @if($prod['rating'])
                            <span style="color:var(--warn)">★</span> {{ $prod['rating'] }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="num tnum">{{ $prod['rating_count'] ?? 0 }}</td>
                    <td class="num tnum">{{ $prod['days_listed'] }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="empty-card">
                        <p>No products found. <a href="/seller/products/add">Add your first product</a></p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endif
@endsection
