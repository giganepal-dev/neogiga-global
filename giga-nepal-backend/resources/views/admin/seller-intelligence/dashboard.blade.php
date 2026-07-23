@extends('admin.layout')
@section('title', 'Seller Intelligence Dashboard')
@section('crumb', 'Admin / Seller Intelligence / Dashboard')

@section('content')
<div class="page-head">
    <div>
        <h2>Seller Intelligence Dashboard</h2>
        <p>Market demand, trending MPNs, and supply gap analysis.</p>
    </div>
    <div class="page-actions">
        <a href="/admin/seller-intelligence/trending" class="btn btn-ghost">Trending MPNs</a>
        <a href="/admin/seller-intelligence/unfulfilled" class="btn btn-ghost">Unfulfilled Demand</a>
        <a href="/admin/seller-intelligence/supply-gaps" class="btn btn-ghost">Supply Gaps</a>
    </div>
</div>

<div class="grid kpis">
    <div class="kpi">
        <div class="t">Active Opportunities</div>
        <div class="v">{{ number_format($total_opportunities) }}</div>
        <div class="s">{{ $total_mpns }} unique MPNs</div>
    </div>
    <div class="kpi">
        <div class="t">High Demand</div>
        <div class="v" style="color:var(--danger)">{{ $high_demand }}</div>
        <div class="s">Score &gt; 70</div>
    </div>
    <div class="kpi">
        <div class="t">Unfulfilled</div>
        <div class="v" style="color:var(--warn)">{{ $unfulfilled }}</div>
        <div class="s">No current supply</div>
    </div>
    <div class="kpi">
        <div class="t">Avg Demand Score</div>
        <div class="v">{{ $avg_demand ? number_format($avg_demand, 1) : 'N/A' }}</div>
    </div>
</div>

<div class="grid split" style="margin-top:18px">
    <div class="card">
        <div class="card-h">
            <h2>Trending MPNs</h2>
            <a href="/admin/seller-intelligence/trending" class="btn btn-ghost" style="height:30px;font-size:.78rem">View All</a>
        </div>
        <div class="scroll-x">
            <table class="tbl">
                <thead><tr><th>MPN</th><th>Product</th><th>Brand</th><th class="num">Demand</th><th>Reason</th></tr></thead>
                <tbody>
                    @forelse($recent_trending as $opp)
                    <tr>
                        <td><a href="/admin/seller-intelligence/opportunity/{{ urlencode($opp->mpn) }}" class="mono" style="color:var(--primary);font-weight:600">{{ $opp->mpn }}</a></td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $opp->product_name ?? '-' }}</td>
                        <td>{{ $opp->brand ?? '-' }}</td>
                        <td class="num">
                            <span class="badge {{ $opp->demand_score > 70 ? 'b-danger' : ($opp->demand_score > 40 ? 'b-warn' : 'b-muted') }}">
                                {{ number_format($opp->demand_score, 1) }}
                            </span>
                        </td>
                        <td style="font-size:.82rem;color:var(--muted);max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $opp->opportunity_reason ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="empty">No trending data yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-h"><h2>Top Categories by Demand</h2></div>
            <div class="scroll-x">
                <table class="tbl">
                    <thead><tr><th>Category</th><th class="num">Demand</th><th class="num">MPNs</th></tr></thead>
                    <tbody>
                        @forelse($top_categories as $cat)
                        <tr>
                            <td>{{ ucfirst($cat['category'] ?? 'Uncategorized') }}</td>
                            <td class="num"><span class="badge b-info">{{ number_format($cat['total_demand'], 1) }}</span></td>
                            <td class="num">{{ $cat['count'] }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="empty">No category data yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-h"><h2>Unfulfilled Demand</h2>
                <a href="/admin/seller-intelligence/unfulfilled" class="btn btn-ghost" style="height:30px;font-size:.78rem">View All</a>
            </div>
            <div class="scroll-x">
                <table class="tbl">
                    <thead><tr><th>MPN</th><th>Product</th><th class="num">Demand</th><th>Supply</th></tr></thead>
                    <tbody>
                        @forelse($unfulfilled_demand as $opp)
                        <tr>
                            <td><span class="mono">{{ $opp->mpn }}</span></td>
                            <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $opp->product_name ?? '-' }}</td>
                            <td class="num"><span class="badge b-danger">{{ number_format($opp->demand_score, 1) }}</span></td>
                            <td><span class="badge {{ $opp->current_supply > 0 ? 'b-ok' : 'b-danger' }}">{{ $opp->current_supply }}</span></td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="empty">No unfulfilled demand.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
