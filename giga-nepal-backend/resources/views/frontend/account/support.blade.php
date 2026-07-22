@extends('frontend.account.layout')
@section('title','Support — NeoGiga')
@section('account-content')
<header class="account-topbar"><div><h1>Support tickets</h1><p>Product, order, RFQ and account support in one place.</p></div></header>
<section class="account-panel">
    <div class="account-panel-head"><div><h2>Your tickets</h2><p>Replies from NeoGiga appear in the ticket conversation.</p></div></div>
    @if($tickets->isEmpty())<div class="account-empty">No support tickets yet.</div>@else
    <div class="account-table-wrap"><table class="account-table"><thead><tr><th>Ticket</th><th>Subject</th><th>Category</th><th>Priority</th><th>Status</th><th>Updated</th></tr></thead><tbody>
        @foreach($tickets as $ticket)<tr><td><a href="/account/support/{{ $ticket->id }}" class="mono">{{ $ticket->ticket_number }}</a></td><td>{{ $ticket->subject }}</td><td>{{ str_replace('_',' ',$ticket->category) }}</td><td><span class="account-badge {{ $ticket->priority }}">{{ $ticket->priority }}</span></td><td><span class="account-badge {{ $ticket->status }}">{{ str_replace('_',' ',$ticket->status) }}</span></td><td>{{ \Carbon\Carbon::parse($ticket->updated_at)->format('d M Y, H:i') }}</td></tr>@endforeach
    </tbody></table></div>@endif
</section>
<section class="account-panel">
    <div class="account-panel-head"><div><h2>Open a ticket</h2><p>Give the team enough detail to route and resolve your request.</p></div></div>
    <form class="account-form" method="post" action="/account/support">@csrf<div class="account-form-grid">
        <div class="account-field full"><label for="subject">Subject</label><input id="subject" name="subject" value="{{ old('subject') }}" required></div>
        <div class="account-field"><label for="category">Category</label><select id="category" name="category" required>@foreach($categories as $category)<option value="{{ $category }}">{{ ucwords(str_replace('_',' ',$category)) }}</option>@endforeach</select></div>
        <div class="account-field"><label for="priority">Priority</label><select id="priority" name="priority" required><option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option></select></div>
        <div class="account-field full"><label for="message">How can we help?</label><textarea id="message" name="message" required>{{ old('message') }}</textarea></div>
    </div><div><button class="account-button" type="submit">Create ticket</button></div></form>
</section>
@endsection
