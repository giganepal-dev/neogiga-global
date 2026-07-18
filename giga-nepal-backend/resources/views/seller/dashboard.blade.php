@extends('seller.layout')
@section('title','Dashboard')
@section('content')
<h1 style="margin:0 0 8px">{{ $v->name }}</h1>
<p style="color:var(--muted);margin:0 0 24px">{{ $v->email ?? '' }}</p>
<div class="kpi-grid">
    <div class="kpi"><div class="t">Products</div><div class="v">{{ number_format($stats['product_count']) }}</div><div class="s">in catalog</div></div>
    <div class="kpi"><div class="t">Orders</div><div class="v">{{ number_format($stats['order_count']) }}</div><div class="s">total</div></div>
</div>
<div class="card"><h2 style="margin:0 0 12px;font-size:1rem">Quick Actions</h2>
    <div style="display:flex;gap:10px;flex-wrap:wrap"><a href="/seller/products" class="btn btn-ghost">Products</a><a href="/seller/orders" class="btn btn-ghost">Orders</a><a href="/seller/profile" class="btn btn-ghost">Edit Profile</a></div></div>
@endsection
