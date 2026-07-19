@extends('mail.transactional.layout')
@section('content')
<h2>Order Confirmed — #{{ $orderNumber ?? '' }}</h2>
<p>Thank you for your order. We've received it and will begin processing shortly.</p>

<table class="order-table">
  <tr><th>Order Number</th><td>{{ $orderNumber ?? '—' }}</td></tr>
  <tr><th>Date</th><td>{{ $orderDate ?? '—' }}</td></tr>
  <tr><th>Status</th><td><span class="badge badge-ok">{{ $orderStatus ?? 'Confirmed' }}</span></td></tr>
  <tr><th>Total</th><td>{{ $currency ?? 'USD' }} {{ $orderTotal ?? '0.00' }}</td></tr>
  <tr><th>Payment</th><td>{{ $paymentStatus ?? 'Pending' }}</td></tr>
</table>

@if(!empty($products))
<h2 style="margin-top:20px">Items</h2>
<table class="order-table">
  <thead><tr><th>Product</th><th>Qty</th><th>Price</th></tr></thead>
  <tbody>
  @foreach($products as $item)
    <tr><td>{{ $item['name'] ?? '' }}<br><span class="muted">{{ $item['mpn'] ?? '' }}</span></td><td>{{ $item['quantity'] ?? 1 }}</td><td>{{ $currency ?? 'USD' }} {{ $item['price'] ?? '0.00' }}</td></tr>
  @endforeach
  </tbody>
</table>
@endif

@if(!empty($shippingAddress))
<p style="margin-top:16px"><strong>Shipping to:</strong><br>{{ $shippingAddress }}</p>
@endif

<a class="btn" href="{{ $orderUrl ?? '#' }}">View order details</a>
<p class="muted">You will receive another email when your order status changes.</p>
@endsection
