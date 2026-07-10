@extends('frontend.layout')
@section('title','Order Received - NeoGiga')
@section('description','NeoGiga manual checkout request received.')
@section('content')
<section class="hero" style="padding:48px 0"><div class="wrap"><p class="eyebrow">Order received</p><h1 class="page-title" style="font-size:clamp(2rem,5vw,4rem)">Thank you</h1><p>Your manual checkout request was created. NeoGiga will confirm stock, quote-only items and payment instructions.</p></div></section>
<section class="section"><div class="wrap"><div class="panel" style="padding:24px"><h2>Order {{ $order->order_number }}</h2><p class="sub">Status: {{ $order->status }} · Payment: {{ $order->payment_status }} · Total: {{ $order->currency_code }} {{ number_format((float)$order->grand_total,2) }}</p><table class="spec-table">@foreach($order->items as $item)<tr><th>{{ $item->product_name }}</th><td>{{ $item->quantity }} x {{ $order->currency_code }} {{ number_format((float)$item->unit_price,2) }}</td></tr>@endforeach</table><div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px"><a class="btn btn-primary" href="/products">Continue shopping</a><a class="btn btn-ghost" href="/rfq">Submit RFQ</a></div></div></div></section>
@endsection
