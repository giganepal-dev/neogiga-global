@extends('admin.layout')
@section('title','Warehouse Locations')
@section('crumb','Operations / Warehouse / Zones, Aisles, Racks, Shelves & Bins')

@section('page_actions')
<details class="modal"><summary class="btn btn-primary">Add Zone</summary>
<div class="modal-panel"><div class="modal-h"><h3>Add Warehouse Zone</h3></div>
<form class="modal-b form-stack" method="post" action="/admin/warehouse/zones">@csrf
<div class="field"><label>Warehouse</label><select class="control" name="warehouse_id" required>@foreach($warehouses as $w)<option value="{{$w->id}}">{{$w->name}}</option>@endforeach</select></div>
<div class="field"><label>Zone Name</label><input class="control" name="name" required></div>
<div class="field"><label>Code</label><input class="control" name="code"></div>
<button class="btn btn-primary" type="submit">Save Zone</button></form></div></details>

<details class="modal"><summary class="btn btn-ghost">Add Aisle</summary>
<div class="modal-panel"><div class="modal-h"><h3>Add Aisle</h3></div>
<form class="modal-b form-stack" method="post" action="/admin/warehouse/aisles">@csrf
<div class="field"><label>Zone</label><select class="control" name="zone_id" required>@foreach($zones as $z)<option value="{{$z->id}}">{{$z->warehouse_name}} → {{$z->name}}</option>@endforeach</select></div>
<div class="field"><label>Aisle Name</label><input class="control" name="name" required></div>
<div class="field"><label>Code</label><input class="control" name="code"></div>
<button class="btn btn-primary" type="submit">Save Aisle</button></form></div></details>

<details class="modal"><summary class="btn btn-ghost">Add Rack</summary>
<div class="modal-panel"><div class="modal-h"><h3>Add Rack</h3></div>
<form class="modal-b form-stack" method="post" action="/admin/warehouse/racks">@csrf
<div class="field"><label>Aisle</label><select class="control" name="aisle_id" required>@foreach($aisles as $a)<option value="{{$a->id}}">{{$a->zone_name}} → {{$a->name}}</option>@endforeach</select></div>
<div class="field"><label>Rack Name</label><input class="control" name="name" required></div>
<div class="field"><label>Code</label><input class="control" name="code"></div>
<button class="btn btn-primary" type="submit">Save Rack</button></form></div></details>
@endsection

@section('content')

<div class="grid split">
{{-- Zones --}}
<div class="card"><div class="card-h"><h2>Zones ({{$zones->count()}})</h2></div>
<div class="scroll-x"><table class="tbl"><thead><tr><th>Name</th><th>Code</th><th>Warehouse</th><th>Active</th></tr></thead>
<tbody>@foreach($zones as $z)<tr><td>{{$z->name}}</td><td class="mono">{{$z->code}}</td><td>{{$z->warehouse_name}}</td><td><span class="badge {{($z->is_active ?? true) ? 'b-ok':'b-muted'}}">{{($z->is_active ?? true) ? 'Active':'Inactive'}}</span></td></tr>@endforeach</tbody></table></div></div>

{{-- Aisles --}}
<div class="card"><div class="card-h"><h2>Aisles ({{$aisles->count()}})</h2></div>
<div class="scroll-x"><table class="tbl"><thead><tr><th>Name</th><th>Code</th><th>Zone</th></tr></thead>
<tbody>@foreach($aisles as $a)<tr><td>{{$a->name}}</td><td class="mono">{{$a->code}}</td><td>{{$a->zone_name}}</td></tr>@endforeach</tbody></table></div></div>
</div>

<div class="grid stack-gap">
{{-- Racks + Shelves --}}
<div class="card"><div class="card-h"><h2>Racks ({{$racks->count()}}) &amp; Shelves ({{$shelves->count()}})</h2>
<details class="modal"><summary class="btn btn-ghost" style="font-size:.78rem">Add Shelf</summary>
<div class="modal-panel"><div class="modal-h"><h3>Add Shelf</h3></div>
<form class="modal-b form-stack" method="post" action="/admin/warehouse/shelves">@csrf
<div class="field"><label>Rack</label><select class="control" name="rack_id" required>@foreach($racks as $r)<option value="{{$r->id}}">{{$r->aisle_name}} → {{$r->name}}</option>@endforeach</select></div>
<div class="field"><label>Shelf Name</label><input class="control" name="name" required></div>
<div class="field"><label>Code</label><input class="control" name="code"></div>
<button class="btn btn-primary" type="submit">Save Shelf</button></form></div></details>
</div>
<div class="scroll-x"><table class="tbl"><thead><tr><th>Rack</th><th>Code</th><th>Aisle</th></tr></thead>
<tbody>@foreach($racks as $r)<tr><td>{{$r->name}}</td><td class="mono">{{$r->code}}</td><td>{{$r->aisle_name}}</td></tr>@endforeach</tbody></table></div></div>

{{-- Bins --}}
<div class="card"><div class="card-h"><h2>Bins ({{$bins->count()}})</h2>
<details class="modal"><summary class="btn btn-ghost" style="font-size:.78rem">Add Bin</summary>
<div class="modal-panel"><div class="modal-h"><h3>Add Bin</h3></div>
<form class="modal-b form-stack" method="post" action="/admin/warehouse/bins">@csrf
<div class="field"><label>Shelf</label><select class="control" name="shelf_id" required>@foreach($shelves as $s)<option value="{{$s->id}}">{{$s->rack_name}} → {{$s->name}}</option>@endforeach</select></div>
<div class="field"><label>Bin Name</label><input class="control" name="name" required></div>
<div class="field"><label>Code</label><input class="control" name="code"></div>
<div class="field"><label>Capacity</label><input class="control" name="capacity" type="number" step="any"></div>
<button class="btn btn-primary" type="submit">Save Bin</button></form></div></details>
</div>
<div class="scroll-x"><table class="tbl"><thead><tr><th>Bin</th><th>Code</th><th>Shelf</th><th>Capacity</th></tr></thead>
<tbody>@foreach($bins as $b)<tr><td>{{$b->name}}</td><td class="mono">{{$b->code}}</td><td>{{$b->shelf_name}}</td><td>{{$b->capacity ?? '—'}}</td></tr>@endforeach</tbody></table></div></div>
</div>

<div class="card stack-gap"><div class="card-h"><h2>Warehouses</h2></div>
<div class="scroll-x"><table class="tbl"><thead><tr><th>Name</th><th>Code</th><th>Country</th><th>Address</th><th>Active</th></tr></thead>
<tbody>@foreach($warehouses as $w)<tr><td><strong>{{$w->name}}</strong></td><td class="mono">{{$w->code}}</td><td>{{$w->country_id ?? 'Global'}}</td><td>{{$w->address_line1}}</td><td><span class="badge {{($w->is_active ?? 1) ? 'b-ok':'b-muted'}}">{{($w->is_active ?? 1) ? 'Active':'Inactive'}}</span></td></tr>@endforeach</tbody></table></div></div>

@endsection
