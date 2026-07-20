@extends('distributor.layout')
@section('title','Messages')
@section('content')
<div class="page-intro"><h1>Messages</h1><p>Chat with admin or customers. Customer PII is privacy-masked.</p></div>
<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>Subject</th><th>Preview</th><th>Status</th></tr></thead>
    <tbody>@forelse($conversations as $c)<tr>
        <td><a href="/distributor/messages/{{ $c['id'] }}">{{ $c['subject'] }}</a></td>
        <td class="sub">{{ $c['last_message_preview'] ?? '—' }}</td>
        <td>{{ $c['status'] }}</td>
    </tr>@empty<tr><td colspan="3" class="sub">No conversations yet.</td></tr>@endforelse</tbody>
</table></div></div>
@endsection
