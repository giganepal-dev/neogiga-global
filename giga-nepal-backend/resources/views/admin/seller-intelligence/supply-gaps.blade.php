@extends('admin.layout')
@section('title', 'Supply Gaps')
@section('crumb', 'Admin / Seller Intelligence / Supply Gaps')

@section('content')
<div class="page-head">
    <div>
        <h2>Supply Gaps</h2>
        <p>Products where demand exceeds current supply. {{ $gaps->total() }} gaps found.</p>
    </div>
</div>

<form class="filters" method="GET" action="/admin/seller-intelligence/supply-gaps">
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
                    <th class="num">Demand</th>
                    <th class="num">Supply</th>
                    <th class="num">Gap</th>
                    <th class="num">RFQs</th>
                    <th class="num">BOMs</th>
                </tr>
            </thead>
            <tbody>
                @forelse($gaps as $gap)
                <tr>
                    <td><a href="/admin/seller-intelligence/opportunity/{{ urlencode($gap->mpn) }}" class="mono" style="color:var(--primary);font-weight:600">{{ $gap->mpn }}</a></td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $gap->product_name ?? '-' }}</td>
                    <td>{{ $gap->brand ?? '-' }}</td>
                    <td class="num"><span class="badge b-danger">{{ number_format($gap->demand_score, 1) }}</span></td>
                    <td class="num"><span class="badge {{ $gap->current_supply > 0 ? 'b-warn' : 'b-danger' }}">{{ $gap->current_supply }}</span></td>
                    <td class="num" style="font-weight:700;color:var(--danger)">{{ number_format($gap->demand_score - $gap->current_supply, 1) }}</td>
                    <td class="num">{{ number_format($gap->rfq_count) }}</td>
                    <td class="num">{{ number_format($gap->bom_occurrence) }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty"><h3>No supply gaps</h3><p>Supply meets demand for all tracked products.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px">{{ $gaps->links() }}</div>
@endsection
