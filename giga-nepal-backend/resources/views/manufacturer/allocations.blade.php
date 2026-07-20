@extends('manufacturer.layout')
@section('title','Regional Allocations')
@section('content')
<div class="page-intro"><h1>Regional Allocations</h1><p>Push stock from global inventory to regional marketplaces and warehouses.</p></div>
<div class="card"><div class="card-h"><h2>Allocation history</h2></div><div class="table-wrap"><table class="table">
    <thead><tr><th>Product</th><th>Marketplace</th><th>Warehouse</th><th>Qty</th><th>Status</th><th>Date</th></tr></thead>
    <tbody>@forelse($allocations as $a)<tr>
        <td>{{ $a->product_name ?? '—' }}</td>
        <td>{{ $a->marketplace_id ?? '—' }}</td>
        <td>{{ $a->warehouse_id ?? '—' }}</td>
        <td>{{ number_format($a->quantity_allocated ?? 0) }}</td>
        <td><span class="badge b-info">{{ $a->status }}</span></td>
        <td>{{ $a->allocated_at ?? $a->created_at ?? '—' }}</td>
    </tr>@empty<tr><td colspan="6" class="sub">No allocations yet.</td></tr>@endforelse</tbody>
</table></div>{{ $allocations->links() }}</div>
<div class="card"><div class="card-h"><h2>New allocation</h2></div>
<form method="post" action="/manufacturer/allocations" class="card-body">@csrf
    <div class="field"><label>Product</label><select class="control" name="product_id" required>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>@endforeach</select></div>
    <div class="field"><label>Marketplace</label><select class="control" name="marketplace_id"><option value="">—</option>@foreach($marketplaces as $mp)<option value="{{ $mp->id }}">{{ $mp->name }}</option>@endforeach</select></div>
    <div class="field"><label>Warehouse</label><select class="control" name="warehouse_id" required>@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }} ({{ $w->code }})</option>@endforeach</select><div class="sub">Required — stock is transferred to this warehouse on submit.</div></div>
    <div class="field"><label>Quantity</label><input class="control" type="number" step="0.0001" min="0.0001" name="quantity_allocated" required></div>
    <div class="field"><label>Notes</label><textarea class="control" name="notes" rows="3"></textarea></div>
    <button type="submit" class="btn btn-primary">Submit allocation</button>
</form></div>
@endsection
