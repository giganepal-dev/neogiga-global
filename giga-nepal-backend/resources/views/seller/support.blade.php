@extends('seller.layout')
@section('title','Support')
@section('content')
<div class="page-intro"><h1>Seller support</h1><p>Contact NeoGiga about products, orders, payouts, or account verification.</p></div>
<div class="card"><div class="card-h"><h2>Open a ticket</h2></div><form method="post" action="/seller/support" class="card-body">@csrf
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px"><div class="field"><label for="category">Category</label><select id="category" class="control" name="category" required>@foreach(['general','account','products','orders','payouts','technical'] as $category)<option value="{{ $category }}">{{ ucfirst($category) }}</option>@endforeach</select></div><div class="field"><label for="priority">Priority</label><select id="priority" class="control" name="priority" required>@foreach(['low','normal','high','urgent'] as $priority)<option value="{{ $priority }}" @selected($priority === 'normal')>{{ ucfirst($priority) }}</option>@endforeach</select></div></div>
    <div class="field"><label for="subject">Subject</label><input id="subject" class="control" name="subject" value="{{ old('subject') }}" required maxlength="190"></div>
    <div class="field"><label for="message">Message</label><textarea id="message" class="control" name="message" rows="5" required maxlength="5000">{{ old('message') }}</textarea></div>
    <button class="btn btn-primary" type="submit">Open ticket</button>
</form></div>
<div class="card"><div class="card-h"><h2>Your tickets</h2></div><div class="table-wrap"><table class="table"><thead><tr><th>Ticket</th><th>Subject</th><th>Category</th><th>Priority</th><th>Status</th></tr></thead><tbody>@forelse($tickets as $ticket)<tr><td class="mono">{{ $ticket->ticket_number }}</td><td>{{ $ticket->subject }}</td><td>{{ ucfirst($ticket->category) }}</td><td>{{ ucfirst($ticket->priority) }}</td><td><span class="badge {{ $ticket->status === 'closed' ? 'b-muted' : 'b-info' }}">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span></td></tr>@empty<tr><td colspan="5" class="empty">No tickets yet.</td></tr>@endforelse</tbody></table></div>@if($tickets->hasPages())<div class="card-body">{{ $tickets->links() }}</div>@endif</div>
@endsection
