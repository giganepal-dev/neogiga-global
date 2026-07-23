@extends('seller.layout')
@section('title', 'Analytics Dashboard')

@section('content')
<div class="page-intro page-intro--row">
    <div>
        <h1>Analytics Dashboard</h1>
        <p>Track your performance, engagement, and inventory health.</p>
    </div>
    <div>
        <form method="GET" action="/seller/analytics" style="display:flex;gap:8px;align-items:center">
            <select class="control" name="days" style="width:auto" onchange="this.form.submit()">
                <option value="7" {{ request('days') == 7 ? 'selected' : '' }}>Last 7 days</option>
                <option value="30" {{ request('days', 30) == 30 ? 'selected' : '' }}>Last 30 days</option>
                <option value="90" {{ request('days') == 90 ? 'selected' : '' }}>Last 90 days</option>
            </select>
        </form>
    </div>
</div>

@if(!$vendor)
<div class="card">
    <div class="empty-card">
        <p>You do not have an active seller account yet.</p>
        <a href="/seller/applications" class="btn" style="background:var(--accent);color:#fff;border-color:transparent">Apply to Sell</a>
    </div>
</div>
@else

<div class="kpis">
    <div class="kpi">
        <div class="t">Total Products</div>
        <div class="v">{{ number_format($metrics['products']['total'] ?? 0) }}</div>
        <div class="s">{{ $metrics['products']['active'] ?? 0 }} active</div>
    </div>
    <div class="kpi">
        <div class="t">Total Views</div>
        <div class="v">{{ number_format($metrics['engagement']['total_views'] ?? 0) }}</div>
        <div class="s">Avg {{ number_format($metrics['engagement']['avg_views_per_product'] ?? 0) }}/product</div>
    </div>
    <div class="kpi">
        <div class="t">Avg Rating</div>
        <div class="v">{{ $metrics['engagement']['avg_rating'] ? '★ ' . $metrics['engagement']['avg_rating'] : 'N/A' }}</div>
        <div class="s">{{ number_format($metrics['engagement']['total_ratings'] ?? 0) }} ratings</div>
    </div>
    <div class="kpi">
        <div class="t">Orders</div>
        <div class="v">{{ number_format($metrics['sales']['order_count'] ?? 0) }}</div>
        <div class="s">{{ number_format($metrics['sales']['order_revenue'] ?? 0, 2) }} revenue</div>
    </div>
    <div class="kpi">
        <div class="t">RFQ Response Rate</div>
        <div class="v" style="color:{{ ($metrics['rfq']['response_rate'] ?? 0) >= 80 ? 'var(--ok)' : 'var(--warn)' }}">{{ $metrics['rfq']['response_rate'] ?? 0 }}%</div>
        <div class="s">{{ $metrics['rfq']['quoted'] ?? 0 }}/{{ $metrics['rfq']['total_rfq'] ?? 0 }} quoted</div>
    </div>
    <div class="kpi">
        <div class="t">Stock Coverage</div>
        <div class="v" style="color:{{ ($inventory['stock_coverage'] ?? 0) >= 80 ? 'var(--ok)' : 'var(--warn)' }}">{{ $inventory['stock_coverage'] ?? 0 }}%</div>
        <div class="s">{{ $inventory['in_stock'] ?? 0 }}/{{ $inventory['total_active'] ?? 0 }} products</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;margin-top:20px">
    <div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-h">
                <h2>Top Products by Views</h2>
                <a href="/seller/analytics/products" class="btn" style="height:30px;font-size:.78rem">View All</a>
            </div>
            <div style="overflow-x:auto">
                <table class="tbl">
                    <thead><tr><th>Product</th><th>MPN</th><th>Status</th><th class="num">Views</th><th class="num">Rating</th></tr></thead>
                    <tbody>
                        @forelse($products as $prod)
                        <tr>
                            <td style="font-weight:600;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $prod['name'] }}</td>
                            <td class="mono sub">{{ $prod['mpn'] ?? '-' }}</td>
                            <td><span class="badge {{ $prod['status'] === 'approved' ? 'b-ok' : 'b-muted' }}">{{ ucfirst($prod['status']) }}</span></td>
                            <td class="num tnum">{{ number_format($prod['views']) }}</td>
                            <td class="num tnum">{{ $prod['rating'] ? '★ ' . $prod['rating'] : '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="empty-card"><p>No products yet.</p></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-h"><h2>Engagement Trends</h2></div>
            <div class="card-body" style="min-height:120px">
                @if(!empty($trends))
                    <div style="display:flex;align-items:end;gap:4px;height:100px">
                        @php $maxViews = max(array_column($trends, 'views') ?: [1]); @endphp
                        @foreach($trends as $day)
                            <div style="flex:1;background:var(--accent);border-radius:3px 3px 0 0;height:{{ max(4, ($day->views / $maxViews) * 100) }}%;min-width:8px" title="{{ $day->date }}: {{ $day->views }} views"></div>
                        @endforeach
                    </div>
                    <div style="display:flex;justify-content:space-between;margin-top:8px">
                        <span class="sub">{{ $trends[0]->date ?? '' }}</span>
                        <span class="sub">{{ $trends[count($trends)-1]->date ?? '' }}</span>
                    </div>
                @else
                    <p class="sub" style="text-align:center;padding:20px">No trend data available for this period.</p>
                @endif
            </div>
        </div>
    </div>

    <div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-h"><h2>Inventory Health</h2></div>
            <div class="card-body">
                <div style="display:grid;gap:12px">
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem">
                        <span class="sub">Total Active</span>
                        <span style="font-weight:700">{{ $inventory['total_active'] ?? 0 }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem">
                        <span class="sub">In Stock</span>
                        <span style="font-weight:700;color:var(--ok)">{{ $inventory['in_stock'] ?? 0 }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem">
                        <span class="sub">Low Stock</span>
                        <span style="font-weight:700;color:var(--warn)">{{ $inventory['low_stock'] ?? 0 }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:.88rem">
                        <span class="sub">Out of Stock</span>
                        <span style="font-weight:700;color:var(--bad)">{{ $inventory['out_of_stock'] ?? 0 }}</span>
                    </div>
                </div>
                @if(($inventory['total_active'] ?? 0) > 0)
                <div style="margin-top:16px;height:8px;background:var(--line);border-radius:4px;overflow:hidden;display:flex">
                    <div style="width:{{ $inventory['in_stock'] ?? 0 }}%;background:var(--ok);height:100%"></div>
                    <div style="width:{{ $inventory['low_stock'] ?? 0 }}%;background:var(--warn);height:100%"></div>
                    <div style="width:{{ $inventory['out_of_stock'] ?? 0 }}%;background:var(--bad);height:100%"></div>
                </div>
                @endif
            </div>
        </div>

        <div class="card" style="margin-bottom:16px">
            <div class="card-h"><h2>Customer Engagement</h2></div>
            <div class="card-body">
                <div style="display:grid;gap:12px">
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem">
                        <span class="sub">Search Views</span>
                        <span style="font-weight:700">{{ number_format($engagement['search_views'] ?? 0) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem">
                        <span class="sub">Support Tickets</span>
                        <span style="font-weight:700">{{ number_format($engagement['support_tickets'] ?? 0) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem">
                        <span class="sub">RFQ Submissions</span>
                        <span style="font-weight:700">{{ number_format($engagement['rfq_submissions'] ?? 0) }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:.88rem">
                        <span class="sub">Total Touchpoints</span>
                        <span style="font-weight:700;color:var(--info)">{{ number_format($engagement['total_touchpoints'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-h"><h2>Product Approval</h2></div>
            <div class="card-body">
                <div style="text-align:center;padding:12px 0">
                    <div style="font-size:2rem;font-weight:800;color:var(--ok)">{{ $metrics['products']['approval_rate'] ?? 0 }}%</div>
                    <div class="sub">Approval rate</div>
                </div>
                <div style="display:flex;gap:12px;margin-top:8px">
                    <div style="flex:1;text-align:center;padding:8px;background:var(--bg);border-radius:8px">
                        <div style="font-weight:700">{{ $metrics['products']['active'] ?? 0 }}</div>
                        <div class="sub" style="font-size:.76rem">Active</div>
                    </div>
                    <div style="flex:1;text-align:center;padding:8px;background:var(--bg);border-radius:8px">
                        <div style="font-weight:700">{{ $metrics['products']['pending'] ?? 0 }}</div>
                        <div class="sub" style="font-size:.76rem">Pending</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endif
@endsection
