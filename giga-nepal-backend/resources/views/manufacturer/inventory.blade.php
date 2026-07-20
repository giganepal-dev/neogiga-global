@extends('manufacturer.layout')
@section('title','Global Inventory')
@section('content')
<div class="page-intro"><h1>Global Inventory</h1><p>Master stock pool before regional warehouse distribution.</p></div>
<div class="kpi-grid">
    <div class="kpi"><div class="t">SKUs</div><div class="v">{{ number_format($summary['sku_count'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">On hand</div><div class="v">{{ number_format($summary['quantity_on_hand'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Reserved</div><div class="v">{{ number_format($summary['quantity_reserved'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Available</div><div class="v">{{ number_format($summary['quantity_available'] ?? 0) }}</div></div>
</div>
<div class="card"><div class="card-h"><h2>Inventory rows</h2><form method="post" action="/manufacturer/inventory/sync">@csrf<button class="btn btn-ghost" type="submit">Sync catalog</button></form></div>
<div class="table-wrap"><table class="table">
    <thead><tr><th>Product</th><th>SKU</th><th>On hand</th><th>Reserved</th><th>Unit cost</th></tr></thead>
    <tbody>@forelse($rows as $row)<tr>
        <td>{{ $row->product_name ?? '—' }}</td>
        <td class="mono">{{ $row->sku ?? $row->product_sku ?? '—' }}</td>
        <td>{{ number_format($row->quantity_on_hand ?? 0) }}</td>
        <td>{{ number_format($row->quantity_reserved ?? 0) }}</td>
        <td>@if($row->unit_cost)${{ number_format($row->unit_cost, 2) }}@else—@endif</td>
    </tr>@empty<tr><td colspan="5" class="sub">No inventory rows. Sync from catalog to initialize SKUs.</td></tr>@endforelse</tbody>
</table></div>{{ $rows->links() }}</div>
@endsection
