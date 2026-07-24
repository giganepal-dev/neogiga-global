@extends('mail.transactional.layout')
@section('content')
<h2>Quotation Ready — #{{ $quotationNumber ?? '' }}</h2>

<p>Hello {{ $userName ?? 'there' }},</p>

<p>A seller has submitted a quotation for your RFQ.</p>

<table class="order-table">
  <tr><th>Quotation Number</th><td>{{ $quotationNumber ?? '—' }}</td></tr>
  <tr><th>RFQ Reference</th><td>{{ $rfqNumber ?? '—' }}</td></tr>
  <tr><th>Status</th><td><span class="badge badge-ok">Ready for Review</span></td></tr>
  @if(!empty($orderTotal))
  <tr><th>Total</th><td>{{ $currency ?? 'USD' }} {{ $orderTotal ?? '0.00' }}</td></tr>
  @endif
</table>

<a class="btn" href="{{ $quotationUrl ?? '#' }}">Review quotation</a>

<p class="muted">Please review and accept or reject the quotation before it expires.</p>
@endsection
