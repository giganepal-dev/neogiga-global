@extends('seller.layout')
@section('title', 'Customer Engagement')

@section('content')
<div class="page-intro page-intro--row">
    <div>
        <h1>Customer Engagement</h1>
        <p>Track how customers interact with your products and services.</p>
    </div>
    <div>
        <form method="GET" action="/seller/analytics/engagement" style="display:flex;gap:8px;align-items:center">
            <select class="control" name="days" style="width:auto" onchange="this.form.submit()">
                <option value="7" {{ request('days') == 7 ? 'selected' : '' }}>Last 7 days</option>
                <option value="30" {{ request('days', 30) == 30 ? 'selected' : '' }}>Last 30 days</option>
                <option value="90" {{ request('days') == 90 ? 'selected' : '' }}>Last 90 days</option>
            </select>
        </form>
        <a href="/seller/analytics" class="btn" style="background:var(--accent);color:#fff;border-color:transparent;margin-left:8px">Back to Dashboard</a>
    </div>
</div>

@if(!$vendor)
<div class="card">
    <div class="empty-card">
        <p>You do not have an active seller account yet.</p>
    </div>
</div>
@else

{{-- KPIs --}}
<div class="kpis">
    <div class="kpi">
        <div class="t">Search Views</div>
        <div class="v">{{ number_format($engagement['search_views'] ?? 0) }}</div>
        <div class="s">Product search clicks</div>
    </div>
    <div class="kpi">
        <div class="t">Support Tickets</div>
        <div class="v">{{ number_format($engagement['support_tickets'] ?? 0) }}</div>
        <div class="s">Customer inquiries</div>
    </div>
    <div class="kpi">
        <div class="t">RFQ Submissions</div>
        <div class="v">{{ number_format($engagement['rfq_submissions'] ?? 0) }}</div>
        <div class="s">Request for quotations</div>
    </div>
    <div class="kpi">
        <div class="t">Total Touchpoints</div>
        <div class="v" style="color:var(--info)">{{ number_format($engagement['total_touchpoints'] ?? 0) }}</div>
        <div class="s">All customer interactions</div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px">
    {{-- Engagement Trends --}}
    <div class="card">
        <div class="card-h"><h2>Engagement Trends</h2></div>
        <div class="card-body" style="min-height:200px">
            @if(!empty($trends))
                <div style="display:flex;align-items:end;gap:3px;height:160px;padding:0 8px">
                    @php $maxViews = max(array_column($trends, 'views') ?: [1]); @endphp
                    @foreach($trends as $day)
                        <div style="flex:1;background:var(--accent);border-radius:3px 3px 0 0;height:{{ max(4, ($day->views / $maxViews) * 100) }}%;min-width:6px;position:relative" title="{{ $day->date }}: {{ $day->views }} views">
                            <div style="position:absolute;top:-20px;left:50%;transform:translateX(-50%);font-size:9px;color:var(--muted);white-space:nowrap;display:none" class="tooltip">{{ $day->views }}</div>
                        </div>
                    @endforeach
                </div>
                <div style="display:flex;justify-content:space-between;margin-top:8px;padding:0 8px">
                    <span class="sub">{{ $trends[0]->date ?? '' }}</span>
                    <span class="sub">{{ $trends[count($trends)-1]->date ?? '' }}</span>
                </div>
            @else
                <p class="sub" style="text-align:center;padding:40px 0">No trend data available for this period.</p>
            @endif
        </div>
    </div>

    {{-- Engagement Breakdown --}}
    <div class="card">
        <div class="card-h"><h2>Engagement Breakdown</h2></div>
        <div class="card-body">
            @php
                $total = max(1, $engagement['total_touchpoints'] ?? 1);
                $searchPct = round(($engagement['search_views'] ?? 0) / $total * 100);
                $supportPct = round(($engagement['support_tickets'] ?? 0) / $total * 100);
                $rfqPct = round(($engagement['rfq_submissions'] ?? 0) / $total * 100);
            @endphp

            {{-- Search Views --}}
            <div style="margin-bottom:20px">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:.88rem">
                    <span>Search Views</span>
                    <span style="font-weight:600">{{ number_format($engagement['search_views'] ?? 0) }} <span class="sub">({{ $searchPct }}%)</span></span>
                </div>
                <div style="height:8px;background:var(--line);border-radius:4px;overflow:hidden">
                    <div style="width:{{ $searchPct }}%;background:var(--accent);height:100%;border-radius:4px"></div>
                </div>
            </div>

            {{-- Support Tickets --}}
            <div style="margin-bottom:20px">
                <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:.88rem">
                    <span>Support Tickets</span>
                    <span style="font-weight:600">{{ number_format($engagement['support_tickets'] ?? 0) }} <span class="sub">({{ $supportPct }}%)</span></span>
                </div>
                <div style="height:8px;background:var(--line);border-radius:4px;overflow:hidden">
                    <div style="width:{{ $supportPct }}%;background:var(--warn);height:100%;border-radius:4px"></div>
                </div>
            </div>

            {{-- RFQ Submissions --}}
            <div>
                <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:.88rem">
                    <span>RFQ Submissions</span>
                    <span style="font-weight:600">{{ number_format($engagement['rfq_submissions'] ?? 0) }} <span class="sub">({{ $rfqPct }}%)</span></span>
                </div>
                <div style="height:8px;background:var(--line);border-radius:4px;overflow:hidden">
                    <div style="width:{{ $rfqPct }}%;background:var(--ok);height:100%;border-radius:4px"></div>
                </div>
            </div>

            <div style="margin-top:24px;padding-top:16px;border-top:1px solid var(--line)">
                <div style="display:flex;justify-content:space-between;font-size:.88rem">
                    <span style="font-weight:600">Total Touchpoints</span>
                    <span style="font-weight:700;color:var(--info)">{{ number_format($engagement['total_touchpoints'] ?? 0) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@endif
@endsection
