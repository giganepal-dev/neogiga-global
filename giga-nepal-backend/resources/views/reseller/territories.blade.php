@extends('reseller.layout')
@section('title','Territories')
@section('content')
<div class="page-intro"><h1>Territories</h1><p>Your account is valid for approved regional marketplaces only. Request expansion to sell in additional territories.</p></div>
<div class="card"><div class="card-h"><h2>Active territories</h2></div><div class="table-wrap"><table class="table">
    <thead><tr><th>Marketplace</th><th>Primary</th><th>Status</th></tr></thead>
    <tbody>@forelse($territories as $t)<tr>
        <td>#{{ $t->marketplace_id }}</td><td>{{ $t->is_primary ? 'Yes' : 'No' }}</td><td>{{ $t->status }}</td>
    </tr>@empty<tr><td colspan="3" class="sub">No territories yet.</td></tr>@endforelse</tbody>
</table></div></div>
<div class="card"><div class="card-h"><h2>Request new territory</h2></div>
<form method="post" action="/reseller/territories/request" enctype="multipart/form-data" class="card-body">@csrf
    <div class="field"><label>Target marketplace</label><select class="control" name="marketplace_id" required>@foreach($marketplaces as $mp)<option value="{{ $mp->id }}">{{ $mp->name }}</option>@endforeach</select></div>
    <div class="field"><label>Notes</label><textarea class="control" name="notes" rows="3"></textarea></div>
    <div class="field"><label>Company registration</label><input class="control" type="file" name="document_company_reg" required></div>
    <div class="field"><label>Reseller certificate</label><input class="control" type="file" name="document_reseller_certificate" required></div>
    <div class="field"><label>Tax clearance</label><input class="control" type="file" name="document_tax_certificate" required></div>
    <button type="submit" class="btn btn-primary">Submit territory request</button>
</form></div>
@endsection
