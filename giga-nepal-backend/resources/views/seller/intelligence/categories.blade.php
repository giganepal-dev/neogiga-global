@extends('seller.layout')
@section('title', 'Fast-Selling Categories')

@section('content')
<div class="page-intro page-intro--row">
    <div>
        <h1>Fast-Selling Categories</h1>
        <p>Categories with the highest market demand. Focus your inventory on these high-growth areas.</p>
    </div>
</div>

<div class="card">
    <div style="overflow-x:auto">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Category</th>
                    <th class="num">Total Demand</th>
                    <th class="num">Products</th>
                    <th class="num">Avg Demand/Product</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $idx => $cat)
                <tr>
                    <td class="num tnum" style="font-weight:700">{{ $idx + 1 }}</td>
                    <td style="font-weight:700">{{ ucfirst($cat['category'] ?? 'Uncategorized') }}</td>
                    <td class="num tnum"><span class="badge b-info">{{ number_format($cat['total_demand'] ?? 0, 1) }}</span></td>
                    <td class="num tnum">{{ number_format($cat['product_count'] ?? 0) }}</td>
                    <td class="num tnum">{{ ($cat['product_count'] ?? 0) > 0 ? number_format(($cat['total_demand'] ?? 0) / $cat['product_count'], 1) : '0' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="empty-card"><p>No category demand data available yet.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
