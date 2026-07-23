@extends('admin.layout')
@section('title', 'Unfulfilled Demand')
@section('crumb', 'Admin / Seller Intelligence / Unfulfilled Demand')

@section('content')
<div class="page-head">
    <div>
        <h2>Unfulfilled Demand</h2>
        <p>Products with demand but no or low supply. {{ $opportunities->total() }} items.</p>
    </div>
</div>

<form class="filters" method="GET" action="/admin/seller-intelligence/unfulfilled">
    <div class="field" style="grid-column:span 2">
        <label>Search</label>
        <input class="control" name="q" value="{{ request('q') }}" placeholder="MPN, product name...">
    </div>
    <div class="field" style="align-self:end">
        <button class="btn btn-primary" type="submit">Search</button>
    </div>
</form>

<div class="card" style="margin-top:16px">
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr>
                    <th>MPN</th>
                    <th>Product</th>
                    <th>Brand</th>
                    <th>Category</th>
                    <th class="num">Demand Score</th>
                    <th class="num">Search Volume</th>
                    <th class="num">RFQ Count</th>
                    <th class="num">BOM Occurrences</th>
                    <th class="num">Current Supply</th>
                    <th>Opportunity</th>
                </tr>
            </thead>
            <tbody>
                @forelse($opportunities as $opp)
                <tr>
                    <td><a href="/admin/seller-intelligence/opportunity/{{ urlencode($opp->mpn) }}" class="mono" style="color:var(--primary);font-weight:600">{{ $opp->mpn }}</a></td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $opp->product_name ?? '-' }}</td>
                    <td>{{ $opp->brand ?? '-' }}</td>
                    <td><span class="badge b-info">{{ ucfirst($opp->category ?? '-') }}</span></td>
                    <td class="num"><span class="badge b-danger">{{ number_format($opp->demand_score, 1) }}</span></td>
                    <td class="num">{{ number_format($opp->search_volume) }}</td>
                    <td class="num">{{ number_format($opp->rfq_count) }}</td>
                    <td class="num">{{ number_format($opp->bom_occurrence) }}</td>
                    <td class="num"><span class="badge b-danger">{{ $opp->current_supply }}</span></td>
                    <td style="font-size:.82rem;color:var(--muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $opp->opportunity_reason ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="10" class="empty"><h3>No unfulfilled demand</h3><p>All demand appears to be satisfied.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px">{{ $opportunities->links() }}</div>
@endsection
