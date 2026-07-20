@extends('manufacturer.layout')
@section('title','Dashboard')
@section('content')
<div class="page-intro"><h1>{{ $manufacturer->name }}</h1><p>{{ $manufacturer->legal_name ?? 'Manufacturer account' }}</p></div>
<div class="kpi-grid">
    <div class="kpi"><div class="t">Catalog SKUs</div><div class="v">{{ number_format($stats['product_count']) }}</div></div>
    <div class="kpi"><div class="t">Active</div><div class="v">{{ number_format($stats['active_products']) }}</div></div>
    <div class="kpi"><div class="t">Global on-hand</div><div class="v">{{ number_format($inventorySummary['quantity_on_hand'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Available</div><div class="v">{{ number_format($inventorySummary['quantity_available'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Allocations</div><div class="v">{{ number_format($allocationSummary['total'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Brands</div><div class="v">{{ number_format($stats['brand_count']) }}</div></div>
</div>
<div class="card"><div class="card-h"><h2>Quick Actions</h2></div><div class="card-body actions-row">
    <a href="/manufacturer/inventory" class="btn btn-primary">Global inventory</a>
    <a href="/manufacturer/allocations" class="btn btn-ghost">Allocate to region</a>
    <form method="post" action="/manufacturer/inventory/sync" style="display:inline">@csrf<button type="submit" class="btn btn-ghost">Sync from catalog</button></form>
</div></div>
@endsection
