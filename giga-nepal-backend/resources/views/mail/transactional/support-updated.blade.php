@extends('mail.transactional.layout')
@section('content')
<h2>Support Ticket Updated</h2>

<p>Hello {{ $userName ?? 'there' }},</p>

<p>Your support ticket has been updated by our team.</p>

<table class="order-table">
  <tr><th>Ticket</th><td>{{ $ticketNumber ?? '—' }}</td></tr>
  <tr><th>Status</th><td><span class="badge badge-ok">{{ $statusLabel ?? 'Updated' }}</span></td></tr>
  <tr><th>Updated</th><td>{{ $statusDate ?? '—' }}</td></tr>
</table>

@if(!empty($statusMessage))
<p><strong>Message:</strong> {{ $statusMessage }}</p>
@endif

<a class="btn" href="{{ $supportUrl ?? '#' }}">View support ticket</a>
@endsection
