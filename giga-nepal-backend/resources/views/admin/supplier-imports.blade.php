@extends('admin.layout')
@section('title','Supplier Catalog Imports')
@section('crumb','Catalog / Supplier Imports')

@section('content')
<div class="note"><strong>Supplier importers</strong> queue background jobs that pull product catalogs from supplier APIs. Ensure the queue worker is running: <code>php artisan queue:work --queue=imports,catalog-imports</code></div>

<div class="kpis">@foreach($suppliers as $key => $name)
<div class="kpi"><span class="t">{{$name}}</span><span class="v">{{$key}}</span>
<form method="post" action="/admin/imports/suppliers/{{$key}}/run" style="margin-top:8px">@csrf
<button class="btn btn-primary" type="submit" style="width:100%">Import Now</button></form>
</div>@endforeach</div>

<div class="card stack-gap"><div class="card-h"><h2>Import Log</h2></div>
<div class="scroll-x"><table class="tbl"><thead><tr><th>Supplier</th><th>Command</th><th>Status</th><th>Action</th></tr></thead>
<tbody>
@foreach($suppliers as $key => $name)
<tr>
<td><strong>{{$name}}</strong></td>
<td class="mono">import:{{$key}}</td>
<td><span class="badge b-info">Ready</span></td>
<td><form method="post" action="/admin/imports/suppliers/{{$key}}/run">@csrf<button class="btn btn-ghost" type="submit">Run</button></form></td>
</tr>
@endforeach
</tbody></table></div></div>

<div class="card stack-gap"><div class="card-h"><h2>Existing Import Sources</h2></div>
<div style="padding:14px;display:flex;gap:8px;flex-wrap:wrap">
<a class="btn btn-ghost" href="/admin/imports/jlcpcb">JLCPCB Import Review</a>
<a class="btn btn-ghost" href="/admin/imports/elecforest">ElecForest Imports</a>
</div></div>
@endsection
