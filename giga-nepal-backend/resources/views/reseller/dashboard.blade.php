@extends('reseller.layout')
@section('title','Dashboard')
@section('content')
<h1 style="margin:0 0 24px">Reseller Dashboard</h1>
<div class="kpi-grid">
    <div class="kpi"><div class="t">Products</div><div class="v">{{ number_format($overview['product_count'] ?? 0) }}</div><div class="s">in catalog</div></div>
    <div class="kpi"><div class="t">Orders</div><div class="v">{{ number_format($overview['order_count'] ?? 0) }}</div><div class="s">total</div></div>
    <div class="kpi"><div class="t">Revenue</div><div class="v">{{ $overview['revenue'] ?? '$0' }}</div><div class="s">this month</div></div>
    <div class="kpi"><div class="t">Status</div><div class="v"><span class="badge {{ ($reseller->is_active ?? true) ? 'b-ok' : 'b-muted' }}">{{ ($reseller->is_active ?? true) ? 'Active' : 'Inactive' }}</span></div><div class="s">account</div></div>
</div>
<div class="card"><h2 style="margin:0 0 12px;font-size:1rem">Quick Actions</h2>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="/reseller/products" class="btn btn-ghost">Manage Products</a>
        <a href="/reseller/orders" class="btn btn-ghost">View Orders</a>
    </div>
</div>
@endsection
