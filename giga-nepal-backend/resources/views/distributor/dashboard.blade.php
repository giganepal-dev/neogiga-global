@extends('distributor.layout')
@section('title','Dashboard')
@section('content')
<div class="page-intro"><h1>{{ $distributor->name }}</h1><p>{{ ($distributor->operating_scope ?? 'country') === 'global' ? 'Global distributor' : 'Single-country distributor' }} · Base: {{ $overview['base_country_name'] ?? 'Not assigned' }} · {{ ucfirst($distributor->status ?? 'pending') }}</p></div>
<div class="kpi-grid">
    <div class="kpi"><div class="t">Territories</div><div class="v">{{ number_format($overview['territories'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Orders</div><div class="v">{{ number_format($overview['orders'] ?? 0) }}</div><div class="s">Territory orders</div></div>
    <div class="kpi"><div class="t">Leads</div><div class="v">{{ number_format($overview['leads'] ?? 0) }}</div><div class="s">Sales opportunities</div></div>
    <div class="kpi"><div class="t">Customers</div><div class="v">{{ number_format($overview['customers'] ?? 0) }}</div><div class="s">Distributor-owned</div></div>
    <div class="kpi"><div class="t">Stock SKUs</div><div class="v">{{ number_format($stockSummary['total_products'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Available Qty</div><div class="v">{{ number_format($stockSummary['available_quantity'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Low Stock</div><div class="v">{{ number_format($stockSummary['low_stock_products'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Pending Commission</div><div class="v">${{ number_format($commissionSummary['pending'] ?? 0, 2) }}</div></div>
    <div class="kpi"><div class="t">Approved (Unpaid)</div><div class="v">${{ number_format($commissionSummary['approved'] ?? 0, 2) }}</div></div>
    <div class="kpi"><div class="t">Paid Out</div><div class="v">${{ number_format($commissionSummary['paid'] ?? 0, 2) }}</div></div>
    <div class="kpi"><div class="t">Downlines</div><div class="v">{{ number_format($downlineStats['total'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Open Support</div><div class="v">{{ number_format($openTickets) }}</div><div class="s">Tickets requiring follow-up</div></div>
</div>
<div class="card"><div class="card-h"><h2>Approved sales regions and territories</h2><a href="/distributor/territories" class="sub">Manage territories</a></div><div class="card-body">
    @forelse($overview['territory_details'] ?? [] as $territory)
        <div style="display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid var(--line)"><span>{{ $territory->country_name ?? 'Unassigned country' }} @if($territory->country_iso_code_2)({{ $territory->country_iso_code_2 }})@endif @if($territory->region_name) · {{ $territory->region_name }}@endif @if($territory->city_name) · {{ $territory->city_name }}@endif</span><span class="badge b-ok">{{ $territory->territory_name }}</span></div>
    @empty
        <p class="sub">No approved sales territory yet. Submitted targets remain subject to NeoGiga review.</p>
    @endforelse
</div></div>
<div class="card"><div class="card-h"><h2>Quick Actions</h2></div><div class="card-body actions-row">
    <a href="/distributor/territory-stock" class="btn btn-primary">Territory stock</a>
    <a href="/distributor/territories" class="btn btn-ghost">Expand territory</a>
    <a href="/distributor/commissions" class="btn btn-ghost">View commissions</a>
    <a href="/distributor/downlines" class="btn btn-ghost">Manage downlines</a>
    <a href="/distributor/leads" class="btn btn-ghost">Sales leads</a>
</div></div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px" class="dashboard-grid">
    <div class="card"><div class="card-h"><h2>Recent orders</h2><a href="/distributor/orders" class="sub">View all</a></div><div class="table-wrap"><table class="table"><thead><tr><th>Order</th><th>Status</th><th class="num">Gross</th></tr></thead><tbody>@forelse($recentOrders as $order)<tr><td class="mono">{{ $order->order_reference ?? '#'.$order->id }}</td><td><span class="badge {{ in_array($order->status, ['fulfilled','delivered','shipped']) ? 'b-ok' : 'b-warn' }}">{{ ucfirst($order->status) }}</span></td><td class="num">${{ number_format($order->gross_amount ?? 0, 2) }}</td></tr>@empty<tr><td colspan="3" class="empty">No territory orders yet.</td></tr>@endforelse</tbody></table></div></div>
    <div class="card"><div class="card-h"><h2>Recent leads</h2><a href="/distributor/leads" class="sub">View all</a></div><div class="table-wrap"><table class="table"><thead><tr><th>Lead</th><th>Company</th><th>Status</th></tr></thead><tbody>@forelse($recentLeads as $lead)<tr><td>{{ $lead->name }}</td><td>{{ $lead->company ?? '—' }}</td><td><span class="badge {{ ($lead->status ?? '') === 'converted' ? 'b-ok' : 'b-info' }}">{{ ucfirst($lead->status ?? 'new') }}</span></td></tr>@empty<tr><td colspan="3" class="empty">No sales leads yet.</td></tr>@endforelse</tbody></table></div></div>
</div>
<style>@media(max-width:880px){.dashboard-grid{grid-template-columns:1fr!important}}</style>
@endsection
