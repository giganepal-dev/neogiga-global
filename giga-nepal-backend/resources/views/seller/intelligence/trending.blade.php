@extends('seller.layout')
@section('title', 'Trending MPNs')

@section('content')
<div class="page-intro page-intro--row">
    <div>
        <h1>Trending MPNs</h1>
        <p>High-demand products with growing search volume. Consider stocking these to capture market opportunity.</p>
    </div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="filters">
        <form method="GET" action="/seller/intelligence/trending" style="display:flex;gap:8px;flex:1">
            <input class="control" name="q" value="{{ request('q') }}" placeholder="Search MPN, product, or brand..." style="flex:1;min-width:200px">
            <button class="btn" type="submit">Search</button>
        </form>
    </div>

    <div style="overflow-x:auto">
        <table class="tbl">
            <thead>
                <tr>
                    <th>MPN</th>
                    <th>Product</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th class="num">Demand Score</th>
                    <th class="num">Search Volume</th>
                    <th class="num">Growth</th>
                    <th class="num">Orders</th>
                    <th class="num">RFQs</th>
                    <th class="num">BOMs</th>
                    <th class="num">Supply</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($opportunities as $opp)
                <tr>
                    <td><a href="/seller/intelligence/opportunity/{{ urlencode($opp->mpn) }}" class="mono" style="font-weight:600;color:var(--info)">{{ $opp->mpn }}</a></td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $opp->product_name ?? '-' }}</td>
                    <td>{{ $opp->brand ?? '-' }}</td>
                    <td><span class="badge b-muted">{{ ucfirst($opp->category ?? '-') }}</span></td>
                    <td class="num tnum"><span class="badge {{ $opp->demand_score > 70 ? 'b-bad' : ($opp->demand_score > 40 ? 'b-warn' : 'b-muted') }}">{{ number_format($opp->demand_score, 1) }}</span></td>
                    <td class="num tnum">{{ number_format($opp->search_volume) }}</td>
                    <td class="num">
                        @if($opp->search_growth > 0)
                            <span style="color:var(--ok)">+{{ number_format($opp->search_growth, 1) }}%</span>
                        @elseif($opp->search_growth < 0)
                            <span style="color:var(--bad)">{{ number_format($opp->search_growth, 1) }}%</span>
                        @else
                            <span class="sub">0%</span>
                        @endif
                    </td>
                    <td class="num tnum">{{ number_format($opp->order_count) }}</td>
                    <td class="num tnum">{{ number_format($opp->rfq_count) }}</td>
                    <td class="num tnum">{{ number_format($opp->bom_occurrence) }}</td>
                    <td class="num"><span class="badge {{ $opp->current_supply > 0 ? 'b-ok' : 'b-bad' }}">{{ $opp->current_supply }}</span></td>
                    <td><a href="/seller/intelligence/opportunity/{{ urlencode($opp->mpn) }}" class="btn" style="height:30px;font-size:.78rem">View</a></td>
                </tr>
                @empty
                <tr><td colspan="12" class="empty-card"><p>No trending MPNs found matching your search.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px">{{ $opportunities->links() }}</div>
@endsection
