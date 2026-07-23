@extends('seller.layout')
@section('title', 'Market Intelligence')

@section('content')
<div class="page-intro page-intro--row">
    <div>
        <h1>Market Intelligence</h1>
        <p>Discover trending products, fast-selling categories, and unmet demand opportunities.</p>
    </div>
</div>

<div class="kpis">
    <div class="kpi">
        <div class="t">My Products</div>
        <div class="v">{{ number_format($stats['my_products']) }}</div>
        <div class="s">{{ $stats['my_active'] }} active</div>
    </div>
    <div class="kpi">
        <div class="t">My Views (30d)</div>
        <div class="v">{{ number_format($stats['my_views']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">My Sales (30d)</div>
        <div class="v">{{ number_format($stats['my_sales']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Trending MPNs</div>
        <div class="v" style="color:var(--info)">{{ number_format($stats['trending_count']) }}</div>
        <div class="s">{{ $stats['high_demand'] }} high demand</div>
    </div>
    <div class="kpi">
        <div class="t">Unmet Demand</div>
        <div class="v" style="color:var(--warn)">{{ number_format($stats['unmet_count']) }}</div>
        <div class="s">Products with no supply</div>
    </div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-h">
        <h2>Trending MPNs</h2>
        <a href="/seller/intelligence/trending" class="btn" style="height:30px;font-size:.78rem">View All</a>
    </div>
    <div style="overflow-x:auto">
        <table class="tbl">
            <thead><tr><th>MPN</th><th>Product</th><th>Brand</th><th class="num">Demand</th><th class="num">Search Vol</th><th>Growth</th><th></th></tr></thead>
            <tbody>
                @forelse($trending as $opp)
                <tr>
                    <td><a href="/seller/intelligence/opportunity/{{ urlencode($opp->mpn) }}" class="mono" style="font-weight:600;color:var(--info)">{{ $opp->mpn }}</a></td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $opp->product_name ?? '-' }}</td>
                    <td>{{ $opp->brand ?? '-' }}</td>
                    <td class="num tnum"><span class="badge {{ $opp->demand_score > 70 ? 'b-bad' : ($opp->demand_score > 40 ? 'b-warn' : 'b-muted') }}">{{ number_format($opp->demand_score, 1) }}</span></td>
                    <td class="num tnum">{{ number_format($opp->search_volume) }}</td>
                    <td>
                        @if($opp->search_growth > 0)
                            <span style="color:var(--ok)">+{{ number_format($opp->search_growth, 1) }}%</span>
                        @elseif($opp->search_growth < 0)
                            <span style="color:var(--bad)">{{ number_format($opp->search_growth, 1) }}%</span>
                        @else
                            <span class="sub">0%</span>
                        @endif
                    </td>
                    <td><a href="/seller/intelligence/opportunity/{{ urlencode($opp->mpn) }}" class="btn" style="height:30px;font-size:.78rem">Details</a></td>
                </tr>
                @empty
                <tr><td colspan="7" class="empty-card"><p>No trending data available yet.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    <div class="card">
        <div class="card-h">
            <h2>Fast-Selling Categories</h2>
            <a href="/seller/intelligence/categories" class="btn" style="height:30px;font-size:.78rem">View All</a>
        </div>
        <div style="overflow-x:auto">
            <table class="tbl">
                <thead><tr><th>Category</th><th class="num">Demand</th><th class="num">Products</th></tr></thead>
                <tbody>
                    @forelse($fastSelling as $cat)
                    <tr>
                        <td style="font-weight:600">{{ ucfirst($cat['category'] ?? '-') }}</td>
                        <td class="num tnum"><span class="badge b-info">{{ number_format($cat['total_demand'] ?? 0, 1) }}</span></td>
                        <td class="num">{{ $cat['product_count'] ?? 0 }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="empty-card"><p>No category data yet.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-h">
            <h2>Unmet Demand</h2>
            <a href="/seller/intelligence/unmet" class="btn" style="height:30px;font-size:.78rem">View All</a>
        </div>
        <div style="overflow-x:auto">
            <table class="tbl">
                <thead><tr><th>MPN</th><th>Product</th><th class="num">Demand</th><th>Supply</th></tr></thead>
                <tbody>
                    @forelse($unmet as $opp)
                    <tr>
                        <td><span class="mono">{{ $opp->mpn }}</span></td>
                        <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $opp->product_name ?? '-' }}</td>
                        <td class="num tnum"><span class="badge b-bad">{{ number_format($opp->demand_score, 1) }}</span></td>
                        <td><span class="badge b-bad">{{ $opp->current_supply }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="empty-card"><p>No unmet demand.</p></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
