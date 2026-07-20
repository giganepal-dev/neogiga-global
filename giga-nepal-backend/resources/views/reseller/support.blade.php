@extends('reseller.layout')
@section('title','Support')
@section('content')
<div class="page-intro page-intro--row"><div><h1>Support tickets</h1><p>Contact NeoGiga admin for account, catalog, or territory issues.</p></div></div>
<div class="card"><div class="card-h"><h2>New ticket</h2></div>
<form method="post" action="/reseller/support" class="card-body">@csrf
    <div class="field"><label>Subject</label><input class="control" name="subject" required></div>
    <div class="field"><label>Message</label><textarea class="control" name="body" rows="4" required></textarea></div>
    <button type="submit" class="btn btn-primary">Open ticket</button>
</form></div>
<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>Ticket</th><th>Subject</th><th>Status</th></tr></thead>
    <tbody>@foreach($tickets as $t)<tr><td class="mono">{{ $t->ticket_number }}</td><td>{{ $t->subject }}</td><td>{{ $t->status }}</td></tr>@endforeach</tbody>
</table></div>{{ $tickets->links() }}</div>
@endsection
