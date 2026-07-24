@extends('mail.transactional.layout')
@section('content')
<h2>RFQ Received — #{{ $rfqNumber ?? '' }}</h2>

<p>Hello {{ $userName ?? 'there' }},</p>

<p>Your Request for Quotation has been received and is being reviewed.</p>

<table class="order-table">
  <tr><th>RFQ Number</th><td>{{ $rfqNumber ?? '—' }}</td></tr>
  <tr><th>Status</th><td><span class="badge badge-ok">Received</span></td></tr>
  <tr><th>Submitted</th><td>{{ $statusDate ?? '—' }}</td></tr>
</table>

@if(!empty($nextStep))
<p><strong>Next step:</strong> {{ $nextStep }}</p>
@endif

<a class="btn" href="{{ $rfqUrl ?? '#' }}">View RFQ details</a>

<p class="muted">You will receive updates as sellers respond to your RFQ.</p>
@endsection
