@extends('reseller.layout')
@section('title','Products')
@section('content')
<div class="page-intro page-intro--row">
    <div><h1>Catalog</h1><p>Match existing MPNs or list new products for your regional territory.</p></div>
    <div class="actions-row">
        <a href="/reseller/products/create" class="btn btn-primary">Add product</a>
    </div>
</div>
<div class="card"><div class="card-h"><h2>Bulk import (CSV)</h2></div><div class="card-body">
    <form method="post" action="/reseller/products/import" enctype="multipart/form-data" class="actions-row">@csrf
        <input type="file" name="file" accept=".csv,.txt" class="control" required>
        <button type="submit" class="btn btn-ghost">Import CSV</button>
    </form>
    <p class="sub" style="margin-top:8px">Columns: name, mpn, sku, price, quantity</p>
</div></div>
<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>Product</th><th>MPN/SKU</th><th>Status</th><th>Stock</th></tr></thead>
    <tbody>
        @forelse($products as $p)
        <tr>
            <td>{{ $p->name }}</td>
            <td class="mono sub">{{ $p->mpn ?? $p->sku }}</td>
            <td><span class="badge b-info">{{ $p->status }}</span></td>
            <td class="tnum">{{ $p->stock_quantity ?? 0 }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="sub">No products yet.</td></tr>
        @endforelse
    </tbody>
</table></div>{{ $products->links() }}</div>
@endsection
