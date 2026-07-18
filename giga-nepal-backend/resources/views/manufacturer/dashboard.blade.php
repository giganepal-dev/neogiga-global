@extends('manufacturer.layout')
@section('title','Dashboard')
@section('content')
<h1 style="margin:0 0 8px">{{ $mfr->name }}</h1>
<p style="color:var(--muted);margin:0 0 24px">{{ $mfr->legal_name ?? '' }}</p>
<div class="kpi-grid">
    <div class="kpi"><div class="t">Total Products</div><div class="v">{{ number_format($stats['product_count']) }}</div><div class="s">in catalog</div></div>
    <div class="kpi"><div class="t">Active</div><div class="v">{{ number_format($stats['active_products']) }}</div><div class="s">published</div></div>
    <div class="kpi"><div class="t">Brands</div><div class="v">{{ number_format($stats['brand_count']) }}</div><div class="s">registered</div></div>
    <div class="kpi"><div class="t">Status</div><div class="v"><span class="badge {{ $mfr->is_active ? 'b-ok' : 'b-muted' }}">{{ $mfr->is_active ? 'Active' : 'Inactive' }}</span></div><div class="s">account</div></div>
</div>
<div class="card"><h2 style="margin:0 0 12px;font-size:1rem">Quick Actions</h2>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <a href="/manufacturer/products" class="btn btn-ghost">Manage Products</a>
        <a href="/manufacturer/profile" class="btn btn-ghost">Edit Profile</a>
    </div>
</div>
@endsection
