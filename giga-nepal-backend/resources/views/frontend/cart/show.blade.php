@extends('frontend.layout')
@section('title','Cart - NeoGiga')
@section('description','Review your NeoGiga engineering cart, update quantities, request RFQ or proceed to manual checkout.')
@section('content')
<section class="hero" style="padding:34px 0">
    <div class="wrap">
        <nav class="crumbs"><a href="/">Home</a><span>/</span><strong>Cart</strong></nav>
        <p class="eyebrow">Engineering cart</p>
        <h1 class="page-title" style="font-size:clamp(2rem,5vw,4rem)">Review your parts</h1>
        <p>Cart checkout is manual-confirmation only. For B2B pricing or unavailable prices, use RFQ.</p>
    </div>
</section>
<section class="section">
    <div class="wrap layout-2" style="grid-template-columns:minmax(0,1fr) 340px">
        <div class="panel" style="padding:18px">
            @if(session('status'))<p class="badge b-ok">{{ session('status') }}</p>@endif
            @if(session('error'))<p class="badge b-warn">{{ session('error') }}</p>@endif
            @forelse($cart->items as $item)
                <div style="display:grid;grid-template-columns:90px 1fr auto;gap:14px;align-items:center;border-bottom:1px solid #e5edf5;padding:14px 0">
                    <div class="product-img" style="aspect-ratio:1">{{ $item->product?->brand?->name ?? 'NG' }}</div>
                    <div>
                        <h2 style="font-size:1rem;margin:0"><a href="/products/{{ $item->product?->slug }}">{{ $item->product?->name ?? 'Product' }}</a></h2>
                        <p class="sub">SKU {{ $item->product?->sku ?? 'TBA' }} · Unit {{ $cart->currency_code }} {{ number_format((float)$item->unit_price,2) }} @if((float)$item->unit_price <= 0) · RFQ price pending @endif</p>
                        <div style="display:flex;gap:8px;flex-wrap:wrap">
                            <form method="post" action="/cart/items/{{ $item->id }}">@csrf @method('PATCH')<input class="control" style="width:90px" type="number" name="quantity" min="1" max="500" value="{{ $item->quantity }}"><button class="btn btn-ghost" type="submit">Update</button></form>
                            <form method="post" action="/cart/items/{{ $item->id }}">@csrf @method('DELETE')<button class="btn btn-ghost" type="submit">Remove</button></form>
                        </div>
                    </div>
                    <strong>{{ $cart->currency_code }} {{ number_format((float)$item->total,2) }}</strong>
                </div>
            @empty
                <div style="text-align:center;padding:48px"><h2>Your cart is empty</h2><p class="sub">Browse products, ask AI to build a BOM, or submit a sourcing RFQ.</p><a class="btn btn-primary" href="/products">Browse Products</a></div>
            @endforelse
        </div>
        <aside class="panel" style="padding:18px">
            <h2>Summary</h2>
            <table class="spec-table"><tr><th>Items</th><td>{{ number_format($cart->item_count) }}</td></tr><tr><th>Subtotal</th><td>{{ $cart->currency_code }} {{ number_format((float)$cart->subtotal,2) }}</td></tr><tr><th>Tax estimate</th><td>{{ $cart->currency_code }} {{ number_format((float)$cart->tax_total,2) }}</td></tr><tr><th>Shipping estimate</th><td>{{ $cart->currency_code }} {{ number_format((float)$cart->shipping_total,2) }}</td></tr><tr><th>Total estimate</th><td><strong>{{ $cart->currency_code }} {{ number_format((float)$cart->grand_total,2) }}</strong></td></tr></table>
            <h3 style="margin:16px 0 8px">Regional routing</h3>
            @forelse($routes as $route)
                <p style="margin:8px 0"><strong>{{ $route['product_name'] }}</strong><br><span class="sub">{{ $route['warehouse_name'] ?: 'Warehouse quote required' }} @if($route['country_name']) · {{ $route['country_name'] }} @endif · {{ str_replace('_',' ', $route['status']) }}</span></p>
            @empty
                <p class="sub">Routing appears after products are added.</p>
            @endforelse
            <div class="grid" style="margin-top:14px">
                @if($checkoutEnabled)
                    <a class="btn btn-primary" href="/checkout">Proceed to Checkout</a>
                @else
                    <span class="badge b-warn">{{ $marketplaceName }} is currently RFQ-only.</span>
                @endif
                <a class="btn btn-ghost" href="/rfq">Request Bulk RFQ</a>
                <a class="btn btn-gold" href="/ai-commerce">Ask AI Engineer</a>
            </div>
            <p class="sub">Advisory only. Staff confirms taxes, shipping, stock, quote-only items and payment instructions.</p>
        </aside>
    </div>
</section>
@endsection
