@extends('distributor.layout')
@section('title','Products')
@section('content')
<div class="page-intro"><h1>Products</h1><p>Catalog items linked to your distributor account.</p></div>
@if($products->isEmpty())
<div class="card"><div class="card-body sub">No products assigned yet.</div></div>
@else
<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>ID</th><th>Name</th><th>SKU</th><th>Category</th><th>Status</th><th>Created</th></tr></thead>
    <tbody>@foreach($products as $p)<tr>
        <td class="mono">#{{ $p->id }}</td>
        <td><strong>{{ $p->name }}</strong></td>
        <td class="mono">{{ $p->sku }}</td>
        <td>{{ $p->category_name ?? '—' }}</td>
        <td><span class="badge {{ ($p->status ?? '') === 'active' ? 'b-ok' : 'b-muted' }}">{{ $p->status ?? 'draft' }}</span></td>
        <td class="sub">{{ $p->created_at ?? '—' }}</td>
    </tr>@endforeach</tbody>
</table></div>{{ $products->links() }}</div>
@endif
@endsection
