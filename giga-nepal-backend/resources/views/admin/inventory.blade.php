@extends('admin.layout')
@section('title','Inventory')
@section('crumb','Warehouses, stock ledger and reservations')
@section('content')
<div class="note"><strong>Ledger mode:</strong> Stock changes are written as movements and reflected in available/reserved balances.</div>
<div class="grid kpis">
    <div class="kpi"><div class="t">Warehouses</div><div class="v tnum">{{ number_format($stats['warehouses']) }}</div><div class="s">active and inactive</div></div>
    <div class="kpi"><div class="t">Stock Rows</div><div class="v tnum">{{ number_format($stats['stockRows']) }}</div><div class="s">product locations</div></div>
    <div class="kpi"><div class="t">Available</div><div class="v tnum">{{ number_format($stats['availableUnits']) }}</div><div class="s">units</div></div>
    <div class="kpi"><div class="t">Reserved</div><div class="v tnum">{{ number_format($stats['reservedUnits']) }}</div><div class="s">units</div></div>
    <div class="kpi"><div class="t">Low Stock</div><div class="v tnum">{{ number_format($stats['lowStockRows']) }}</div><div class="s">rows at reorder point</div></div>
</div>
<div class="grid" style="grid-template-columns:1fr 1fr;align-items:start">
<div class="card"><div class="card-h"><h2>Recent Stock</h2><span class="sub">API: /api/v1/admin/inventory/stocks</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Product</th><th>Warehouse</th><th>Available</th><th>Reserved</th></tr></thead><tbody>@forelse($stocks as $s)<tr><td>{{ $s->product_name ?? ('#'.$s->product_id) }}<div class="sub mono">{{ $s->product_sku ?? $s->sku }}</div></td><td>{{ $s->warehouse_name ?? ('#'.$s->warehouse_id) }}</td><td class="tnum">{{ $s->quantity_available }}</td><td class="tnum">{{ $s->quantity_reserved }}</td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No stock rows yet</h3></div></td></tr>@endforelse</tbody></table></div></div>
<div class="card"><div class="card-h"><h2>Recent Movements</h2><span class="sub">Append-only audit</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Type</th><th>Product</th><th>Qty</th><th>After</th></tr></thead><tbody>@forelse($movements as $m)<tr><td><span class="badge b-muted">{{ $m->movement_type }}</span></td><td>#{{ $m->product_id }}</td><td class="tnum">{{ $m->quantity_change }}</td><td class="tnum">{{ $m->quantity_after }}</td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No movements yet</h3></div></td></tr>@endforelse</tbody></table></div></div>
</div>
@endsection
