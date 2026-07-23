@extends('admin.layout')
@section('title', 'Trending MPNs')
@section('crumb', 'Admin / Seller Intelligence / Trending MPNs')

@section('content')
<div class="page-head">
    <div>
        <h2>Trending MPNs</h2>
        <p>{{ $opportunities->total() }} MPN opportunities tracked</p>
    </div>
</div>

<form class="filters" method="GET" action="/admin/seller-intelligence/trending">
    <div class="field">
        <label>Search</label>
        <input class="control" name="q" value="{{ request('q') }}" placeholder="MPN, product, brand...">
    </div>
    <div class="field">
        <label>Category</label>
        <select class="control" name="category">
            <option value="">All Categories</option>
            @foreach($categories as $cat)
                <option value="{{ $cat['category'] }}" {{ request('category') === $cat['category'] ? 'selected' : '' }}>
                    {{ ucfirst($cat['category'] ?? 'Uncategorized') }} ({{ $cat['count'] }})
                </option>
            @endforeach
        </select>
    </div>
    <div class="field" style="align-self:end">
        <button class="btn btn-primary" type="submit">Filter</button>
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
                    <th class="num">Demand</th>
                    <th class="num">Search Vol</th>
                    <th class="num">Growth %</th>
                    <th class="num">Orders</th>
                    <th class="num">RFQs</th>
                    <th class="num">BOMs</th>
                    <th class="num">Supply</th>
                    <th>Reason</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($opportunities as $opp)
                <tr>
                    <td><a href="/admin/seller-intelligence/opportunity/{{ urlencode($opp->mpn) }}" class="mono" style="color:var(--primary);font-weight:600">{{ $opp->mpn }}</a></td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $opp->product_name ?? '-' }}</td>
                    <td>{{ $opp->brand ?? '-' }}</td>
                    <td><span class="badge b-info">{{ ucfirst($opp->category ?? '-') }}</span></td>
                    <td class="num">
                        <span class="badge {{ $opp->demand_score > 70 ? 'b-danger' : ($opp->demand_score > 40 ? 'b-warn' : 'b-muted') }}">
                            {{ number_format($opp->demand_score, 1) }}
                        </span>
                    </td>
                    <td class="num">{{ number_format($opp->search_volume) }}</td>
                    <td class="num">
                        @if($opp->search_growth > 0)
                            <span style="color:var(--ok)">+{{ number_format($opp->search_growth, 1) }}%</span>
                        @elseif($opp->search_growth < 0)
                            <span style="color:var(--danger)">{{ number_format($opp->search_growth, 1) }}%</span>
                        @else
                            <span style="color:var(--muted)">0%</span>
                        @endif
                    </td>
                    <td class="num">{{ number_format($opp->order_count) }}</td>
                    <td class="num">{{ number_format($opp->rfq_count) }}</td>
                    <td class="num">{{ number_format($opp->bom_occurrence) }}</td>
                    <td class="num">
                        <span class="badge {{ $opp->current_supply > 0 ? 'b-ok' : 'b-danger' }}">{{ $opp->current_supply }}</span>
                    </td>
                    <td style="font-size:.82rem;color:var(--muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $opp->opportunity_reason ?? '-' }}</td>
                    <td>
                        <div class="actions">
                            <a href="/admin/seller-intelligence/opportunity/{{ urlencode($opp->mpn) }}" class="btn btn-ghost icon-btn" title="View Detail">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </a>
                            <form method="POST" action="/admin/seller-intelligence/opportunity/{{ urlencode($opp->mpn) }}/deactivate" style="display:inline" onsubmit="return confirm('Deactivate this opportunity?')">
                                @csrf <button class="btn btn-ghost icon-btn danger" type="submit" title="Deactivate">✕</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="13" class="empty"><h3>No trending MPNs found</h3><p>Adjust filters or check back later.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px">{{ $opportunities->links() }}</div>
@endsection
