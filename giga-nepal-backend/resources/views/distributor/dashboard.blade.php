@extends('distributor.layout')
@section('title','Dashboard')
@section('content')
<div class="page-intro"><h1>{{ $distributor->name }}</h1><p>{{ ucfirst($distributor->type ?? 'distributor') }} · {{ ucfirst($distributor->status ?? 'pending') }}</p></div>
<div class="kpi-grid">
    <div class="kpi"><div class="t">Territories</div><div class="v">{{ number_format($overview['territories'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Stock SKUs</div><div class="v">{{ number_format($stockSummary['total_products'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Available Qty</div><div class="v">{{ number_format($stockSummary['available_quantity'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Low Stock</div><div class="v">{{ number_format($stockSummary['low_stock_products'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Pending Commission</div><div class="v">${{ number_format($commissionSummary['pending'] ?? 0, 2) }}</div></div>
    <div class="kpi"><div class="t">Approved (Unpaid)</div><div class="v">${{ number_format($commissionSummary['approved'] ?? 0, 2) }}</div></div>
    <div class="kpi"><div class="t">Paid Out</div><div class="v">${{ number_format($commissionSummary['paid'] ?? 0, 2) }}</div></div>
    <div class="kpi"><div class="t">Downlines</div><div class="v">{{ number_format($downlineStats['total'] ?? 0) }}</div></div>
</div>
<div class="card"><div class="card-h"><h2>Quick Actions</h2></div><div class="card-body actions-row">
    <a href="/distributor/territory-stock" class="btn btn-primary">Territory stock</a>
    <a href="/distributor/territories" class="btn btn-ghost">Expand territory</a>
    <a href="/distributor/commissions" class="btn btn-ghost">View commissions</a>
    <a href="/distributor/downlines" class="btn btn-ghost">Manage downlines</a>
    <a href="/distributor/leads" class="btn btn-ghost">Sales leads</a>
</div></div>
@endsection
