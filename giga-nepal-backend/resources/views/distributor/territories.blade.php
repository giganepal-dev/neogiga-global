@extends('distributor.layout')
@section('title','Territories')
@section('content')
<div class="page-intro"><h1>Territories</h1><p>Your distribution coverage. Request new territories with updated compliance documents.</p></div>
<div class="card"><div class="card-h"><h2>Active territories</h2></div><div class="table-wrap"><table class="table">
    <thead><tr><th>Name</th><th>Country</th><th>Region</th><th>Exclusive</th><th>Downline mgmt</th></tr></thead>
    <tbody>@forelse($territories as $t)<tr>
        <td>{{ $t->territory_name }}</td>
        <td>{{ $t->country_id ?? '—' }}</td>
        <td>{{ $t->region_id ?? '—' }}</td>
        <td>{{ $t->exclusive ? 'Yes' : 'No' }}</td>
        <td>{{ $t->can_manage_downlines ? 'Yes' : 'No' }}</td>
    </tr>@empty<tr><td colspan="5" class="sub">No territories assigned yet.</td></tr>@endforelse</tbody>
</table></div></div>
@if($requests->isNotEmpty())
<div class="card"><div class="card-h"><h2>Recent requests</h2></div><div class="table-wrap"><table class="table">
    <thead><tr><th>Territory</th><th>Status</th><th>Submitted</th></tr></thead>
    <tbody>@foreach($requests as $req)<tr>
        <td>{{ $req->territory_name }}</td>
        <td><span class="badge {{ $req->status === 'approved' ? 'b-ok' : ($req->status === 'rejected' ? 'b-warn' : 'b-info') }}">{{ $req->status }}</span></td>
        <td>{{ $req->created_at?->format('M j, Y') ?? '—' }}</td>
    </tr>@endforeach</tbody>
</table></div></div>
@endif
<div class="card"><div class="card-h"><h2>Request territory expansion</h2></div>
<form method="post" action="/distributor/territories/request" enctype="multipart/form-data" class="card-body">@csrf
    <div class="field"><label>Territory name</label><input class="control" name="territory_name" required placeholder="e.g. Eastern Nepal"></div>
    <div class="field"><label>Country ID</label><input class="control" name="country_id" type="number" placeholder="Optional"></div>
    <div class="field"><label>Region ID</label><input class="control" name="region_id" type="number" placeholder="Optional"></div>
    <div class="field"><label>City ID</label><input class="control" name="city_id" type="number" placeholder="Optional"></div>
    <div class="field"><label>Notes</label><textarea class="control" name="notes" rows="3"></textarea></div>
    @foreach(config('distributor.territory_documents', []) as $field => $label)
    <div class="field"><label>{{ $label }}</label><input class="control" type="file" name="{{ $field }}" accept=".pdf,.jpg,.jpeg,.png" required></div>
    @endforeach
    <button type="submit" class="btn btn-primary">Submit for verification</button>
</form></div>
@endsection
