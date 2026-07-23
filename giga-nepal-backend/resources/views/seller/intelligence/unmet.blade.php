@extends('seller.layout')
@section('title', 'Unmet Demand')

@section('content')
<div class="page-intro page-intro--row">
    <div>
        <h1>Unmet Demand</h1>
        <p>Products customers are searching for but cannot find. Stocking these fills market gaps and captures sales.</p>
    </div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="filters">
        <form method="GET" action="/seller/intelligence/unmet" style="display:flex;gap:8px;flex:1">
            <input class="control" name="q" value="{{ request('q') }}" placeholder="Search MPN or product name..." style="flex:1;min-width:200px">
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
                    <th class="num">RFQs</th>
                    <th class="num">BOMs</th>
                    <th class="num">Current Supply</th>
                </tr>
            </thead>
            <tbody>
                @forelse($opportunities as $opp)
                <tr>
                    <td><a href="/seller/intelligence/opportunity/{{ urlencode($opp->mpn) }}" class="mono" style="font-weight:600;color:var(--info)">{{ $opp->mpn }}</a></td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $opp->product_name ?? '-' }}</td>
                    <td>{{ $opp->brand ?? '-' }}</td>
                    <td><span class="badge b-muted">{{ ucfirst($opp->category ?? '-') }}</span></td>
                    <td class="num tnum"><span class="badge b-bad">{{ number_format($opp->demand_score, 1) }}</span></td>
                    <td class="num tnum">{{ number_format($opp->search_volume) }}</td>
                    <td class="num tnum">{{ number_format($opp->rfq_count) }}</td>
                    <td class="num tnum">{{ number_format($opp->bom_occurrence) }}</td>
                    <td class="num"><span class="badge b-bad">{{ $opp->current_supply }}</span></td>
                </tr>
                @empty
                <tr><td colspan="9" class="empty-card"><p>No unmet demand found matching your search.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px">{{ $opportunities->links() }}</div>
@endsection
