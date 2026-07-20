@extends('b2b.layout')
@section('title','New Quote Request')
@section('content')
<div class="page-intro">
    <h1>New Quote Request</h1>
    <p>Add items to your cart or list them here to receive an official quotation with your institutional pricing.</p>
</div>

<form method="post" action="/b2b/rfqs" class="card form-card">
    @csrf
    <div class="card-h"><h2>Line Items</h2></div>
    <div class="card-body" id="rfq-items">
        <div class="rfq-row" data-index="0">
            <div class="field"><label>Product / Part name</label><input class="control" name="items[0][name]" required maxlength="190" placeholder="e.g. Arduino Uno R3"></div>
            <div class="field"><label>SKU / MPN</label><input class="control" name="items[0][sku]" maxlength="120" placeholder="Optional"></div>
            <div class="field field--sm"><label>Qty</label><input class="control" type="number" name="items[0][quantity]" value="1" min="0.001" step="any" required></div>
            <div class="field field--sm"><label>Target price</label><input class="control" type="number" name="items[0][target_price]" min="0" step="0.01" placeholder="Optional"></div>
        </div>
    </div>
    <div class="card-body">
        <button type="button" class="btn btn-ghost" id="add-rfq-row">+ Add another item</button>
    </div>
    <div class="card-h"><h2>Notes</h2></div>
    <div class="card-body">
        <div class="field"><label>Additional requirements</label><textarea class="control" name="notes" rows="4" maxlength="3000" placeholder="Delivery timeline, institutional discount type (government, school, etc.), PO reference…"></textarea></div>
    </div>
    <div class="card-body actions-row">
        <a href="/b2b/rfqs" class="btn btn-ghost">Cancel</a>
        <button type="submit" class="btn btn-primary">Submit Quote Request</button>
    </div>
</form>

<script>
(function () {
    let index = 1;
    document.getElementById('add-rfq-row')?.addEventListener('click', function () {
        const wrap = document.getElementById('rfq-items');
        const row = document.createElement('div');
        row.className = 'rfq-row';
        row.dataset.index = String(index);
        row.innerHTML = `
            <div class="field"><label>Product / Part name</label><input class="control" name="items[${index}][name]" required maxlength="190"></div>
            <div class="field"><label>SKU / MPN</label><input class="control" name="items[${index}][sku]" maxlength="120"></div>
            <div class="field field--sm"><label>Qty</label><input class="control" type="number" name="items[${index}][quantity]" value="1" min="0.001" step="any" required></div>
            <div class="field field--sm"><label>Target price</label><input class="control" type="number" name="items[${index}][target_price]" min="0" step="0.01"></div>`;
        wrap.appendChild(row);
        index++;
    });
})();
</script>
@endsection
