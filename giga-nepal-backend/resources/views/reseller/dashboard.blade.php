@extends('reseller.layout')
@section('title','Dashboard')
@section('content')
<div class="page-intro"><h1>{{ $reseller->company_name }}</h1><p>Regional reseller · {{ ucfirst($reseller->status) }}</p></div>
<div class="kpi-grid">
    <div class="kpi"><div class="t">Products</div><div class="v">{{ number_format($stats['product_count']) }}</div></div>
    <div class="kpi"><div class="t">Orders</div><div class="v">{{ number_format($stats['order_count']) }}</div></div>
    <div class="kpi"><div class="t">RFQ Invites</div><div class="v">{{ number_format($stats['rfq_count']) }}</div></div>
    <div class="kpi"><div class="t">Territories</div><div class="v">{{ number_format($stats['territory_count']) }}</div></div>
</div>
<div class="card"><div class="card-h"><h2>Quick Actions</h2></div><div class="card-body actions-row">
    <a href="/reseller/products/create" class="btn btn-primary">Add product</a>
    <a href="/reseller/rfqs" class="btn btn-ghost">RFQ bids</a>
    <a href="/reseller/territories" class="btn btn-ghost">Expand territory</a>
    <a href="/reseller/support" class="btn btn-ghost">Open support ticket</a>
</div></div>
@endsection
