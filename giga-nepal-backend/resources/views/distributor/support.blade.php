@extends('distributor.layout')
@section('title','Support')
@section('content')
<div class="page-intro"><h1>Support tickets</h1><p>Contact NeoGiga admin for territory, commission, or account issues.</p></div>
<div class="card"><div class="card-h"><h2>New ticket</h2></div>
<form method="post" action="/distributor/support" class="card-body">@csrf
    <div class="field"><label>Subject</label><input class="control" name="subject" required></div>
    <div class="field"><label>Message</label><textarea class="control" name="body" rows="4" required></textarea></div>
    <button type="submit" class="btn btn-primary">Open ticket</button>
</form></div>
<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>Ticket</th><th>Subject</th><th>Status</th></tr></thead>
    <tbody>@forelse($tickets as $t)<tr><td class="mono">{{ $t->ticket_number }}</td><td>{{ $t->subject }}</td><td>{{ $t->status }}</td></tr>@empty<tr><td colspan="3" class="sub">No tickets yet.</td></tr>@endforelse</tbody>
</table></div>{{ $tickets->links() }}</div>
@endsection
