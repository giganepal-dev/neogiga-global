@extends('frontend.layout')
@section('title','Checkout - NeoGiga')
@section('description','Manual-confirmation checkout for NeoGiga engineering marketplace cart.')
@section('content')
<section class="hero" style="padding:34px 0"><div class="wrap"><nav class="crumbs"><a href="/">Home</a><span>/</span><a href="/cart">Cart</a><span>/</span><strong>Checkout</strong></nav><p class="eyebrow">Manual checkout</p><h1 class="page-title" style="font-size:clamp(2rem,5vw,4rem)">Confirm sourcing request</h1><p>No live payment gateway is called. NeoGiga staff verifies stock, quote-only items and payment instructions.</p></div></section>
<section class="section">
    <div class="wrap layout-2" style="grid-template-columns:minmax(0,1fr) 360px">
        @if($checkoutEnabled)
        <form class="panel" style="padding:20px" method="post" action="/checkout">@csrf
            @if($errors->any())<div class="badge b-warn">Please fix highlighted fields.</div>@endif
            <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
                <div class="field"><label>Name</label><input class="control" name="name" value="{{ old('name') }}" required></div>
                <div class="field"><label>Email</label><input class="control" type="email" name="email" value="{{ old('email') }}" required></div>
                <div class="field"><label>Phone</label><input class="control" name="phone" value="{{ old('phone') }}"></div>
                <div class="field"><label>Company</label><input class="control" name="company" value="{{ old('company') }}"></div>
                <div class="field"><label>Country</label><select class="control" name="country_id"><option value="">Global / not set</option>@foreach($countries as $country)<option value="{{ $country->id }}" @selected(old('country_id')==$country->id)>{{ $country->name }}</option>@endforeach</select></div>
                <div class="field"><label>Payment method</label><select class="control" name="payment_method"><option value="manual">Manual invoice</option><option value="bank_transfer">Bank transfer</option><option value="cod">COD where available</option></select></div>
            </div>
            <div class="field"><label>Address / delivery notes</label><textarea class="control" name="address">{{ old('address') }}</textarea></div>
            <div class="field"><label>Customer notes</label><textarea class="control" name="customer_notes" placeholder="Required date, target price, substitution rules, compliance needs...">{{ old('customer_notes') }}</textarea></div>
            <label style="display:flex;gap:8px;align-items:start"><input type="checkbox" name="confirm" value="1" required> I confirm this is a manual checkout request. NeoGiga will verify stock, price and payment instructions before fulfillment.</label>
            <button class="btn btn-primary" style="margin-top:14px" type="submit">Place Manual Order</button>
        </form>
        @else
        <section class="panel" style="padding:20px">
            <p class="eyebrow">RFQ required</p>
            <h2 style="margin-top:0">{{ $marketplaceName }} does not have checkout enabled</h2>
            <p>Submit an RFQ so NeoGiga can confirm regional price, stock, tax, logistics, and payment options before an order is created.</p>
            <a class="btn btn-primary" href="/rfq">Create RFQ</a>
            <a class="btn btn-ghost" href="/cart">Return to cart</a>
        </section>
        @endif
        <aside class="panel" style="padding:18px"><h2>Order Summary</h2>@forelse($cart->items as $item)<p><strong>{{ $item->product?->name }}</strong><br><span class="sub">{{ $item->quantity }} x {{ $cart->currency_code }} {{ number_format((float)$item->unit_price,2) }}</span></p>@empty<p class="sub">Cart is empty.</p>@endforelse<table class="spec-table"><tr><th>Subtotal</th><td>{{ $cart->currency_code }} {{ number_format((float)$cart->subtotal,2) }}</td></tr><tr><th>Tax estimate</th><td>{{ $cart->currency_code }} {{ number_format((float)$cart->tax_total,2) }}</td></tr><tr><th>Shipping estimate</th><td>{{ $cart->currency_code }} {{ number_format((float)$cart->shipping_total,2) }}</td></tr><tr><th>Total estimate</th><td><strong>{{ $cart->currency_code }} {{ number_format((float)$cart->grand_total,2) }}</strong></td></tr></table><h3 style="margin:16px 0 8px">Routing</h3>@forelse($routes as $route)<p class="sub" style="margin:8px 0">{{ $route['product_name'] }} · {{ $route['warehouse_name'] ?: 'Quote required' }} · {{ str_replace('_',' ', $route['status']) }}</p>@empty<p class="sub">No routing yet.</p>@endforelse<p class="sub">Advisory only. Final invoice may change after stock, tax and logistics confirmation.</p><a class="btn btn-ghost" style="margin-top:14px" href="/cart">Back to cart</a></aside>
    </div>
</section>
@endsection
