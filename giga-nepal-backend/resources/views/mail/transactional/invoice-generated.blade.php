@extends('mail.transactional.layout')
@section('content')
<h2>Invoice Generated — {{ $invoiceNumber ?? '' }}</h2>

<p>Hello {{ $userName ?? 'there' }},</p>

<p>Your invoice has been generated and is ready for review.</p>

<table class="order-table">
  <tr><th>Invoice Number</th><td>{{ $invoiceNumber ?? '—' }}</td></tr>
  @if(!empty($orderNumber))
  <tr><th>Order</th><td>#{{ $orderNumber }}</td></tr>
  @endif
  <tr><th>Amount</th><td><strong>{{ $currency ?? 'USD' }} {{ $orderTotal ?? '0.00' }}</strong></td></tr>
  <tr><th>Status</th><td><span class="badge badge-ok">Issued</span></td></tr>
</table>

@if(!empty($invoiceUrl))
<a class="btn" href="{{ $invoiceUrl }}">View & pay invoice</a>
@endif

<p class="muted">Payment is due within 30 days of issue date.</p>
@endsection
