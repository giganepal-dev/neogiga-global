@extends('distributor.layout')
@section('title','Dashboard')
@section('content')
<h1 style="margin:0 0 8px">{{ $d->name }}</h1>
<p style="color:var(--muted);margin:0 0 24px">{{ $d->type ?? 'Distributor' }} · Since {{ $d->created_at ? date('Y', strtotime($d->created_at)) : '—' }}</p>
<div class="kpi-grid">
    <div class="kpi"><div class="t">Products</div><div class="v">{{ number_format($stats['product_count']) }}</div><div class="s">in catalog</div></div>
    <div class="kpi"><div class="t">Orders</div><div class="v">{{ number_format($stats['order_count']) }}</div><div class="s">total</div></div>
    <div class="kpi"><div class="t">Revenue</div><div class="v">\${{ number_format($stats['revenue'],2) }}</div><div class="s">completed</div></div>
    <div class="kpi"><div class="t">Status</div><div class="v"><span class="badge {{ ($d->status??'') === 'approved' ? 'b-ok' : 'b-info' }}">{{ $d->status ?? 'pending' }}</span></div><div class="s">account</div></div>
</div>
<div class="card"><h2 style="margin:0 0 12px;font-size:1rem">Quick Actions</h2>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="/distributor/products" class="btn btn-ghost">Manage Products</a>
        <a href="/distributor/orders" class="btn btn-ghost">View Orders</a>
        <a href="/distributor/profile" class="btn btn-ghost">Edit Profile</a>
    </div>
</div>
@endsection
