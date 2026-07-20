@extends('distributor.layout')
@section('title','Territory Stock')
@section('content')
<div class="page-intro"><h1>Territory Stock</h1><p>Inventory visible across your approved territories — vendors, SKUs, and availability.</p></div>
<div class="kpi-grid">
    <div class="kpi"><div class="t">Products</div><div class="v">{{ number_format($summary['total_products'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Vendors</div><div class="v">{{ number_format($summary['total_vendors'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Available</div><div class="v">{{ number_format($summary['available_quantity'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Reserved</div><div class="v">{{ number_format($summary['reserved_quantity'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Incoming</div><div class="v">{{ number_format($summary['incoming_quantity'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Quote-only SKUs</div><div class="v">{{ number_format($summary['quote_only_products'] ?? 0) }}</div></div>
</div>
<div class="card"><div class="card-h"><h2>Products in territory</h2></div><div class="table-wrap"><table class="table">
    <thead><tr><th>SKU</th><th>Name</th><th>Available</th><th>Incoming</th><th>Status</th></tr></thead>
    <tbody>@forelse($products as $p)<tr>
        <td class="mono">{{ $p->sku ?? '—' }}</td>
        <td>{{ $p->name ?? '—' }}</td>
        <td>{{ number_format($p->available_quantity ?? 0) }}</td>
        <td>{{ number_format($p->incoming_quantity ?? 0) }}</td>
        <td><span class="badge {{ ($p->status ?? '') === 'active' ? 'b-ok' : 'b-muted' }}">{{ $p->status ?? 'draft' }}</span>@if(!empty($p->quote_only)) <span class="badge b-info">RFQ</span>@endif</td>
    </tr>@empty<tr><td colspan="5" class="sub">No stock rows for your territories yet.</td></tr>@endforelse</tbody>
</table></div></div>
<div class="card"><div class="card-h"><h2>Vendors in territory</h2></div><div class="table-wrap"><table class="table">
    <thead><tr><th>Vendor</th><th>Type</th><th>Products</th><th>Available Qty</th><th>Status</th></tr></thead>
    <tbody>@forelse($vendors as $v)<tr>
        <td>{{ $v->name ?? '—' }}</td>
        <td>{{ $v->type ?? '—' }}</td>
        <td>{{ number_format($v->product_count ?? 0) }}</td>
        <td>{{ number_format($v->available_quantity ?? 0) }}</td>
        <td><span class="badge b-info">{{ $v->status ?? 'pending' }}</span></td>
    </tr>@empty<tr><td colspan="5" class="sub">No vendors mapped to your territories.</td></tr>@endforelse</tbody>
</table></div></div>
@endsection
