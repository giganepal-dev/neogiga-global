@extends('mail.transactional.layout')
@section('content')
<h2>Order #{{ $orderNumber ?? '' }} — {{ $statusLabel ?? 'Status Update' }}</h2>

<p>Your order status has been updated.</p>

<table class="order-table">
  <tr><th>Order Number</th><td>{{ $orderNumber ?? '—' }}</td></tr>
  <tr><th>New Status</th><td><span class="badge {{ $statusBadge ?? 'badge-ok' }}">{{ $statusLabel ?? 'Updated' }}</span></td></tr>
  <tr><th>Updated</th><td>{{ $statusDate ?? '—' }}</td></tr>
</table>

@if(!empty($statusMessage))
<p style="margin-top:12px"><strong>Details:</strong> {{ $statusMessage }}</p>
@endif

@if(!empty($nextStep))
<p><strong>Next step:</strong> {{ $nextStep }}</p>
@endif

@if(!empty($trackingNumber))
<p><strong>Tracking number:</strong> {{ $trackingNumber }}@if(!empty($carrier)) ({{ $carrier }})@endif</p>
@endif

<a class="btn" href="{{ $orderUrl ?? '#' }}">View order</a>

@if(!empty($customerAction))
<p class="muted" style="margin-top:12px">{{ $customerAction }}</p>
@endif
@endsection
