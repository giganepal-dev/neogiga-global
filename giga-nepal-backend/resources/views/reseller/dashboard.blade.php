@extends('reseller.layout')
@section('title','Dashboard')
@section('content')
<h1 style="margin:0 0 8px">{{ $r2->company_name }}</h1>
<p style="color:var(--muted);margin:0 0 24px">{{ $r2->trading_name ?? '' }} · {{ $r2->status ?? 'active' }}</p>
<div class="kpi-grid">
    <div class="kpi"><div class="t">Products</div><div class="v">{{ number_format($stats['product_count']) }}</div><div class="s">in catalog</div></div>
    <div class="kpi"><div class="t">Orders</div><div class="v">{{ number_format($stats['order_count']) }}</div><div class="s">total</div></div>
    <div class="kpi"><div class="t">Status</div><div class="v"><span class="badge {{ ($r2->is_active??true) ? 'b-ok' : 'b-muted' }}">{{ ($r2->is_active??true) ? 'Active' : 'Inactive' }}</span></div><div class="s">account</div></div>
</div>
<div class="card"><h2 style="margin:0 0 12px;font-size:1rem">Quick Actions</h2>
    <div style="display:flex;gap:10px;flex-wrap:wrap"><a href="/reseller/products" class="btn btn-ghost">Products</a><a href="/reseller/orders" class="btn btn-ghost">Orders</a><a href="/reseller/profile" class="btn btn-ghost">Edit Profile</a></div></div>
@endsection
