@extends('admin.layout')
@section('title', 'Opportunity: ' . ($insight['mpn'] ?? ''))
@section('crumb', 'Admin / Seller Intelligence / Opportunity / ' . ($insight['mpn'] ?? ''))

@section('content')
<div class="page-head">
    <div>
        <h2>Opportunity: {{ $insight['mpn'] ?? '' }}</h2>
        <p>{{ $history->product_name ?? 'No product data' }}</p>
    </div>
    <div class="page-actions">
        @if($history && $history->is_active)
        <form method="POST" action="/admin/seller-intelligence/opportunity/{{ urlencode($insight['mpn']) }}/deactivate" style="display:inline">
            @csrf <button class="btn btn-ghost danger" type="submit">Deactivate</button>
        </form>
        @else
        <form method="POST" action="/admin/seller-intelligence/opportunity/{{ urlencode($insight['mpn']) }}/activate" style="display:inline">
            @csrf <button class="btn btn-primary" type="submit">Activate</button>
        </form>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('success') }}</div>
@endif

<div class="grid split" style="align-items:start">
    <div>
        {{-- Insight Card --}}
        <div class="card" style="margin-bottom:16px">
            <div class="card-h"><h2>Opportunity Analysis</h2></div>
            <div style="padding:16px">
                @if(!$insight['found'])
                    <div class="note">No demand data available for this MPN.</div>
                @else
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem">
                        <span style="color:var(--muted)">Demand Score</span>
                        <span class="badge {{ ($insight['demand_score'] ?? 0) > 70 ? 'b-danger' : (($insight['demand_score'] ?? 0) > 40 ? 'b-warn' : 'b-muted') }}">
                            {{ number_format($insight['demand_score'] ?? 0, 1) }}
                        </span>
                    </div>
                    @if(!empty($insight['insights']))
                        <h3 style="font-size:.88rem;font-weight:700;margin:16px 0 8px">Insights</h3>
                        @foreach($insight['insights'] as $item)
                        <div style="padding:8px 12px;background:var(--bg);border-radius:8px;margin-bottom:6px;font-size:.85rem">
                            <strong>{{ ucfirst(str_replace('_', ' ', $item['type'])) }}</strong>
                            <br>{{ $item['message'] }}
                        </div>
                        @endforeach
                    @endif
                    @if(!empty($insight['recommended_action']))
                        <h3 style="font-size:.88rem;font-weight:700;margin:16px 0 8px">Recommended Action</h3>
                        <p style="font-size:.88rem;color:var(--muted)">{{ $insight['recommended_action'] }}</p>
                    @endif
                @endif
            </div>
        </div>

        {{-- History Data --}}
        @if($history)
        <div class="card">
            <div class="card-h"><h2>Historical Data</h2></div>
            <div style="padding:16px">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div style="padding:10px;background:var(--bg);border-radius:8px">
                        <div style="font-size:.76rem;color:var(--muted);text-transform:uppercase">Search Volume</div>
                        <div style="font-size:1.2rem;font-weight:700">{{ number_format($history->search_volume) }}</div>
                    </div>
                    <div style="padding:10px;background:var(--bg);border-radius:8px">
                        <div style="font-size:.76rem;color:var(--muted);text-transform:uppercase">Search Growth</div>
                        <div style="font-size:1.2rem;font-weight:700;{{ $history->search_growth > 0 ? 'color:var(--ok)' : ($history->search_growth < 0 ? 'color:var(--danger)' : '') }}">
                            {{ $history->search_growth > 0 ? '+' : '' }}{{ number_format($history->search_growth, 1) }}%
                        </div>
                    </div>
                    <div style="padding:10px;background:var(--bg);border-radius:8px">
                        <div style="font-size:.76rem;color:var(--muted);text-transform:uppercase">Order Count</div>
                        <div style="font-size:1.2rem;font-weight:700">{{ number_format($history->order_count) }}</div>
                    </div>
                    <div style="padding:10px;background:var(--bg);border-radius:8px">
                        <div style="font-size:.76rem;color:var(--muted);text-transform:uppercase">RFQ Count</div>
                        <div style="font-size:1.2rem;font-weight:700">{{ number_format($history->rfq_count) }}</div>
                    </div>
                    <div style="padding:10px;background:var(--bg);border-radius:8px">
                        <div style="font-size:.76rem;color:var(--muted);text-transform:uppercase">BOM Occurrences</div>
                        <div style="font-size:1.2rem;font-weight:700">{{ number_format($history->bom_occurrence) }}</div>
                    </div>
                    <div style="padding:10px;background:var(--bg);border-radius:8px">
                        <div style="font-size:.76rem;color:var(--muted);text-transform:uppercase">Current Supply</div>
                        <div style="font-size:1.2rem;font-weight:700;{{ $history->current_supply > 0 ? 'color:var(--ok)' : 'color:var(--danger)' }}">
                            {{ $history->current_supply }}
                        </div>
                    </div>
                </div>
                @if($history->regional_demand && count($history->regional_demand))
                    <h3 style="font-size:.88rem;font-weight:700;margin:16px 0 8px">Regional Demand</h3>
                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                        @foreach($history->regional_demand as $region => $demand)
                            <span class="badge b-info">{{ $region }}: {{ $demand }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>

    {{-- Sidebar --}}
    <div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-h"><h2>MPN Details</h2></div>
            <div style="padding:16px">
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem">
                    <span style="color:var(--muted)">MPN</span>
                    <span class="mono">{{ $insight['mpn'] ?? '' }}</span>
                </div>
                @if($history)
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem">
                    <span style="color:var(--muted)">Product</span>
                    <span>{{ $history->product_name ?? '-' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem">
                    <span style="color:var(--muted)">Brand</span>
                    <span>{{ $history->brand ?? '-' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem">
                    <span style="color:var(--muted)">Category</span>
                    <span><span class="badge b-info">{{ $history->category ?? '-' }}</span></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:8px 0;font-size:.88rem">
                    <span style="color:var(--muted)">Status</span>
                    <span class="badge {{ $history->is_active ? 'b-ok' : 'b-danger' }}">{{ $history->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Create/Edit Opportunity --}}
        <div class="card">
            <div class="card-h"><h2>Create / Update Opportunity</h2></div>
            <div style="padding:16px">
                <form method="POST" action="/admin/seller-intelligence/opportunity/{{ urlencode($insight['mpn']) }}/create">
                    @csrf
                    <div class="form-stack">
                        <div class="field"><label>Product Name</label><input class="control" name="product_name" value="{{ $history->product_name ?? '' }}"></div>
                        <div class="field"><label>Brand</label><input class="control" name="brand" value="{{ $history->brand ?? '' }}"></div>
                        <div class="field"><label>Category</label><input class="control" name="category" value="{{ $history->category ?? '' }}"></div>
                        <div class="field"><label>Demand Score</label><input class="control" name="demand_score" type="number" step="0.1" min="0" max="100" value="{{ $history->demand_score ?? 0 }}"></div>
                        <div class="field"><label>Opportunity Reason</label><textarea class="control" name="opportunity_reason" rows="2">{{ $history->opportunity_reason ?? '' }}</textarea></div>
                        <button class="btn btn-primary" type="submit">Save Opportunity</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
