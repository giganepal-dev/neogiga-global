@extends('reseller.layout')
@section('title','Add Product')
@section('content')
<div class="page-intro"><h1>Add product listing</h1><p>Enter MPN to match NeoGiga catalog, or create a new listing.</p></div>
<form method="post" action="/reseller/products" class="card form-card">@csrf
    <div class="card-body">
        <div class="field"><label>Name</label><input class="control" name="name" required></div>
        <div class="field"><label>MPN</label><input class="control" name="mpn" placeholder="Manufacturer part number"></div>
        <div class="field"><label>SKU</label><input class="control" name="sku"></div>
        <div class="field"><label>Base price</label><input class="control" type="number" step="0.01" min="0" name="base_price"></div>
        <div class="field"><label>Stock quantity</label><input class="control" type="number" min="0" name="stock_quantity" value="0"></div>
        <label><input type="checkbox" name="link_existing" value="1"> Link to existing catalog product if MPN matches</label>
    </div>
    <div class="card-body actions-row"><a href="/reseller/products" class="btn btn-ghost">Cancel</a><button type="submit" class="btn btn-primary">Save listing</button></div>
</form>
@endsection
