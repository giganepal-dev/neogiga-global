@extends('seller.layout')
@section('title', 'Opportunity: ' . ($insight['mpn'] ?? ''))

@section('content')
<div class="page-intro page-intro--row">
    <div>
        <h1>Opportunity: {{ $insight['mpn'] ?? '' }}</h1>
        <p>{{ $history->product_name ?? 'No product data available for this MPN.' }}</p>
    </div>
    <div>
        @if($history && $history->is_active)
            <span class="badge b-ok">Active Opportunity</span>
        @else
            <span class="badge b-muted">Inactive</span>
        @endif
    </div>
</div>

<div style="display:grid;grid-template-columns:2fr 1fr;gap:16px;align-items:start">
    <div>
        {{-- Opportunity Analysis --}}
        <div class="card" style="margin-bottom:16px">
            <div class="card-h"><h2>Opportunity Analysis</h2></div>
            <div class="card-body">
                @if(!$insight['found'])
                    <p style="color:var(--muted)">No demand data available for this MPN yet. Market intelligence data is gathered from search patterns, BOM usage, and RFQ activity.</p>
                @else
                    <div class="kpis" style="margin-bottom:16px">
                        <div class="kpi">
                            <div class="t">Demand Score</div>
                            <div class="v" style="color:{{ ($insight['demand_score'] ?? 0) > 70 ? 'var(--bad)' : (($insight['demand_score'] ?? 0) > 40 ? 'var(--warn)' : 'var(--ok)') }}">{{ number_format($insight['demand_score'] ?? 0, 1) }}</div>
                        </div>
                        @if($history)
                        <div class="kpi">
                            <div class="t">Search Volume</div>
                            <div class="v">{{ number_format($history->search_volume) }}</div>
                        </div>
                        <div class="kpi">
                            <div class="t">Current Supply</div>
                            <div class="v" style="color:{{ $history->current_supply > 0 ? 'var(--ok)' : 'var(--bad)' }}">{{ $history->current_supply }}</div>
                        </div>
                        @endif
                    </div>

                    @if(!empty($insight['insights']))
                        <h3 style="font-size:.92rem;font-weight:700;margin:0 0 10px">Key Insights</h3>
                        @foreach($insight['insights'] as $item)
                        <div style="padding:10px 14px;background:var(--bg);border-radius:10px;margin-bottom:8px;border-left:3px solid var(--accent)">
                            <strong style="font-size:.85rem">{{ ucfirst(str_replace('_', ' ', $item['type'])) }}</strong>
                            <p style="margin:4px 0 0;font-size:.85rem;color:var(--muted)">{{ $item['message'] }}</p>
                        </div>
                        @endforeach
                    @endif

                    @if(!empty($insight['recommended_action']))
                        <div style="margin-top:14px;padding:12px 14px;background:rgba(249,189,44,.08);border-radius:10px;border:1px solid rgba(249,189,44,.2)">
                            <strong style="font-size:.88rem;color:#92400e">Recommended Action</strong>
                            <p style="margin:4px 0 0;font-size:.88rem;color:#78350f">{{ $insight['recommended_action'] }}</p>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        {{-- Regional Demand --}}
        @if($history && $history->regional_demand && count($history->regional_demand))
        <div class="card">
            <div class="card-h"><h2>Regional Demand</h2></div>
            <div class="card-body">
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    @foreach($history->regional_demand as $region => $demand)
                        <div style="padding:10px 14px;background:var(--bg);border-radius:10px;text-align:center;min-width:100px">
                            <div style="font-size:.76rem;color:var(--muted);text-transform:uppercase">{{ $region }}</div>
                            <div style="font-size:1.1rem;font-weight:700;margin-top:2px">{{ number_format($demand) }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Sidebar --}}
    <div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-h"><h2>MPN Details</h2></div>
            <div class="card-body">
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem">
                    <span class="sub">MPN</span>
                    <span class="mono" style="font-weight:600">{{ $insight['mpn'] ?? '' }}</span>
                </div>
                @if($history)
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem">
                    <span class="sub">Product</span>
                    <span>{{ $history->product_name ?? '-' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem">
                    <span class="sub">Brand</span>
                    <span>{{ $history->brand ?? '-' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem">
                    <span class="sub">Category</span>
                    <span><span class="badge b-muted">{{ $history->category ?? '-' }}</span></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:.88rem">
                    <span class="sub">Status</span>
                    <span class="badge {{ $history->is_active ? 'b-ok' : 'b-muted' }}">{{ $history->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                @endif
            </div>
        </div>

        <div class="card" style="margin-bottom:16px">
            <div class="card-h"><h2>Market Signals</h2></div>
            <div class="card-body">
                @if($history)
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem">
                    <span class="sub">Orders (30d)</span>
                    <span style="font-weight:600">{{ number_format($history->order_count) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem">
                    <span class="sub">RFQs</span>
                    <span style="font-weight:600">{{ number_format($history->rfq_count) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem">
                    <span class="sub">BOM Occurrences</span>
                    <span style="font-weight:600">{{ number_format($history->bom_occurrence) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:.88rem">
                    <span class="sub">Search Growth</span>
                    <span style="font-weight:600;{{ $history->search_growth > 0 ? 'color:var(--ok)' : ($history->search_growth < 0 ? 'color:var(--bad)' : '') }}">
                        {{ $history->search_growth > 0 ? '+' : '' }}{{ number_format($history->search_growth, 1) }}%
                    </span>
                </div>
                @else
                <p class="sub">No historical data available.</p>
                @endif
            </div>
        </div>

        <div class="card">
            <div class="card-h"><h2>Take Action</h2></div>
            <div class="card-body" style="display:grid;gap:8px">
                <a href="/seller/products/match?mpn={{ urlencode($insight['mpn'] ?? '') }}" class="btn" style="width:100%;justify-content:center;background:var(--accent);color:#fff;border-color:transparent">
                    List This Product
                </a>
                <a href="/seller/products/add?mpn={{ urlencode($insight['mpn'] ?? '') }}" class="btn" style="width:100%;justify-content:center">
                    Add to My Inventory
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
