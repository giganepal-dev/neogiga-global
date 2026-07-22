@extends('seller.layout')
@section('title','Inventory')
@section('content')
<div class="page-intro"><h1>Inventory</h1><p>Warehouse stock assigned to your seller account.</p></div>
<div class="card"><div class="card-h"><h2>Stock positions</h2><span class="badge b-muted">{{ number_format($inventory->total()) }} rows</span></div><div class="table-wrap"><table class="table">
    <thead><tr><th>Product</th><th>Warehouse</th><th>Vendor SKU</th><th class="num">Available</th><th class="num">Reserved</th><th class="num">Incoming</th></tr></thead>
    <tbody>@forelse($inventory as $row)<tr><td>{{ $row->product_name ?? 'Product #'.$row->product_id }}<div class="sub mono">{{ $row->product_sku ?? '' }}</div></td><td>{{ $row->warehouse_name ?? 'Warehouse #'.$row->warehouse_id }}</td><td class="mono">{{ $row->vendor_sku ?? '—' }}</td><td class="num">{{ number_format($row->quantity_available ?? 0) }}</td><td class="num">{{ number_format($row->quantity_reserved ?? 0) }}</td><td class="num">{{ number_format($row->quantity_incoming ?? 0) }}</td></tr>@empty<tr><td colspan="6" class="empty">No inventory has been assigned yet.</td></tr>@endforelse</tbody>
</table></div>@if($inventory->hasPages())<div class="card-body">{{ $inventory->links() }}</div>@endif</div>
@endsection
