@extends('admin.layout')
@section('title','Region Stock & Territory')
@section('crumb','Stock visibility, territory allocations, reservations, alerts')
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Visibility rules</div><div class="v tnum">{{ number_format($stats['rules']) }}</div><div class="s">defined</div></div>
    <div class="kpi"><div class="t">Territory allocations</div><div class="v tnum">{{ number_format($stats['allocations']) }}</div><div class="s">active</div></div>
    <div class="kpi"><div class="t">Pending reservations</div><div class="v tnum">{{ number_format($stats['reservations']) }}</div><div class="s">holding stock</div></div>
    <div class="kpi"><div class="t">Low-stock alerts</div><div class="v tnum">{{ number_format($stats['alerts']) }}</div><div class="s">active</div></div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-h"><h2>Visibility Rules</h2><span class="sub">Latest 20 · rules created via the region-stock API</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>#</th><th>Stock</th><th>Scope</th><th class="num">Priority</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        @forelse ($rules as $r)
            <tr>
                <td class="mono">{{ $r->id }}</td>
                <td>#{{ $r->stock_id }}</td>
                <td><span class="badge b-info">{{ str_replace('_',' ',$r->visibility_scope) }}</span></td>
                <td class="num tnum">{{ $r->priority }}</td>
                <td>@if($r->is_visible)<span class="badge b-ok">Visible</span>@else<span class="badge b-muted">Hidden</span>@endif</td>
                <td><form method="post" action="/admin/region-stock/rules/{{ $r->id }}/toggle">@csrf<button class="btn" type="submit">{{ $r->is_visible ? 'Hide' : 'Show' }}</button></form></td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty"><h3>No visibility rules yet</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="grid dashboard-split">
    <div class="card">
        <div class="card-h"><h2>Territory Allocations</h2><span class="sub">Latest 20</span></div>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Stock</th><th>Territory</th><th class="num">Alloc</th><th class="num">Reserved</th><th class="num">Sold</th><th>Excl.</th></tr></thead>
            <tbody>
            @forelse ($allocations as $a)
                <tr>
                    <td>#{{ $a->stock_id }}</td>
                    <td>#{{ $a->territory_id }}</td>
                    <td class="num tnum">{{ number_format($a->allocated_quantity) }}</td>
                    <td class="num tnum">{{ number_format($a->reserved_quantity) }}</td>
                    <td class="num tnum">{{ number_format($a->sold_quantity) }}</td>
                    <td>{{ $a->is_exclusive ? 'yes' : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty"><h3>No allocations yet</h3></div></td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="card">
        <div class="card-h"><h2>Stock Reservations</h2><span class="sub">Latest 20</span></div>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Stock</th><th class="num">Qty</th><th>Status</th><th>Expires</th></tr></thead>
            <tbody>
            @forelse ($reservations as $rv)
                <tr>
                    <td>#{{ $rv->stock_id }}</td>
                    <td class="num tnum">{{ number_format($rv->quantity) }}</td>
                    <td><span class="badge {{ $rv->status === 'confirmed' ? 'b-ok' : ($rv->status === 'pending' ? 'b-info' : 'b-muted') }}">{{ $rv->status }}</span></td>
                    <td class="sub">{{ $rv->expires_at ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4"><div class="empty"><h3>No reservations</h3></div></td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-h"><h2>Low-Stock Alerts</h2><span class="sub">Inventory module</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Product</th><th class="num">Available</th><th class="num">Threshold</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($alerts as $al)
            <tr>
                <td>#{{ $al->product_id ?? $al->stock_id ?? '—' }}</td>
                <td class="num tnum">{{ number_format($al->available_quantity ?? 0) }}</td>
                <td class="num tnum">{{ number_format($al->threshold ?? $al->threshold_quantity ?? 0) }}</td>
                <td><span class="badge {{ ($al->status ?? '') === 'active' ? 'b-info' : 'b-muted' }}">{{ $al->status ?? '—' }}</span></td>
            </tr>
        @empty
            <tr><td colspan="4"><div class="empty"><h3>No low-stock alerts</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="note" style="margin-top:16px">Rules, allocations, and reservations are managed through the region-stock admin API (<span class="mono">/api/v1/admin/region-stock-visibilities</span>, <span class="mono">/territory-allocations</span>, <span class="mono">/stock-reservations</span>). This console reviews them and toggles rule visibility.</div>

@endsection
