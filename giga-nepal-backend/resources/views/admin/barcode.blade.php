@extends('admin.layout')
@section('title','Barcode System')
@section('crumb','Operations / Barcode Definitions & Generation')

@section('page_actions')
<details class="modal"><summary class="btn btn-primary">Add Definition</summary>
<div class="modal-panel"><div class="modal-h"><h3>Add Barcode Definition</h3></div>
<form class="modal-b form-stack" method="post" action="/admin/barcode/definitions">@csrf
<div class="field"><label>Name</label><input class="control" name="name" required placeholder="e.g. Standard EAN-13"></div>
<div class="field"><label>Type</label><select class="control" name="type" required>
<option value="ean13">EAN-13</option><option value="code128">Code 128</option><option value="qr">QR Code</option><option value="datamatrix">Data Matrix</option><option value="upc">UPC</option></select></div>
<div class="field"><label>Prefix</label><input class="control" name="prefix" placeholder="e.g. NG"></div>
<button class="btn btn-primary" type="submit">Save Definition</button></form></div></details>

<details class="modal"><summary class="btn btn-ghost">Generate Barcodes</summary>
<div class="modal-panel"><div class="modal-h"><h3>Generate Product Barcodes</h3></div>
<form class="modal-b form-stack" method="post" action="/admin/barcode/generate">@csrf
<div class="field"><label>Product</label><select class="control" name="product_id" required>@foreach($products as $p)<option value="{{$p->id}}">{{$p->name}} @if($p->sku)({{$p->sku}})@endif</option>@endforeach</select></div>
<div class="field"><label>Barcode Definition</label><select class="control" name="barcode_definition_id" required>@foreach($definitions as $d)<option value="{{$d->id}}">{{$d->name}} ({{$d->type}})</option>@endforeach</select></div>
<div class="field"><label>Quantity</label><input class="control" name="quantity" type="number" value="10" min="1" max="1000" required></div>
<button class="btn btn-primary" type="submit">Generate</button></form></div></details>
@endsection

@section('content')

<div class="grid split">
{{-- Definitions --}}
<div class="card"><div class="card-h"><h2>Barcode Definitions ({{$definitions->count()}})</h2></div>
<div class="scroll-x"><table class="tbl"><thead><tr><th>Name</th><th>Type</th><th>Prefix</th><th>Status</th><th></th></tr></thead>
<tbody>@foreach($definitions as $d)<tr>
<td><strong>{{$d->name}}</strong></td><td class="mono">{{$d->type}}</td><td class="mono">{{$d->prefix ?? '—'}}</td>
<td><span class="badge {{($d->is_active ?? true) ? 'b-ok':'b-muted'}}">{{($d->is_active ?? true) ? 'Active':'Inactive'}}</span></td>
<td><form method="post" action="/admin/barcode/definitions/{{$d->id}}/toggle">@csrf<button class="btn btn-ghost" type="submit" style="font-size:.72rem">Toggle</button></form></td>
</tr>@endforeach</tbody></table></div></div>

{{-- Stats --}}
<div class="card"><div class="card-h"><h2>System Info</h2></div>
<div class="card-body">
<div class="kpis"><div class="kpi"><span class="t">Definitions</span><span class="v">{{$definitions->count()}}</span></div>
<div class="kpi"><span class="t">Generated</span><span class="v">{{$recentBarcodes->count()}}</span><span class="s">recent</span></div></div>
</div></div>
</div>

{{-- Recent barcodes --}}
<div class="card stack-gap"><div class="card-h"><h2>Recent Barcodes</h2></div>
<div class="scroll-x"><table class="tbl"><thead><tr><th>Code</th><th>Product</th><th>Barcode Def</th><th>Created</th></tr></thead>
<tbody>@foreach($recentBarcodes as $b)<tr>
<td class="mono">{{$b->barcode_value}}</td><td>{{$b->product_name}}</td><td>{{$b->barcode_definition_id}}</td><td>{{$b->created_at}}</td>
</tr>@endforeach</tbody></table></div>
@if($recentBarcodes->isEmpty())<div class="empty"><h3>No barcodes yet</h3><p>Add a barcode definition and generate barcodes for products.</p></div>@endif
</div>

@endsection
