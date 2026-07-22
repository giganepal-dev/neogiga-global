@extends('seller.layout')
@section('title','Dashboard')
@section('content')
@php
    $products = $overview['products'];
    $orders = $overview['orders'];
    $inventory = $overview['inventory'];
    $payouts = $overview['payouts'];
    $onboarding = $overview['onboarding'];
    $onboardingDone = collect($onboarding)->filter()->count();
@endphp
<div class="page-intro page-intro--row">
    <div><h1>{{ $v->name }}</h1><p>{{ ($v->operating_scope ?? 'country') === 'global' ? 'Global seller' : 'Single-country seller' }} · Base: {{ $overview['vendor']['base_country_name'] ?? 'Not assigned' }} · {{ $v->email ?? 'Seller account' }} · {{ ucfirst($overview['vendor']['commerce_status'] ?? $v->status ?? 'pending') }}</p></div>
    <span class="badge {{ ($overview['vendor']['is_verified'] ?? false) ? 'b-ok' : 'b-warn' }}">{{ ($overview['vendor']['is_verified'] ?? false) ? 'Verified seller' : 'Verification pending' }}</span>
</div>

@foreach($overview['alerts'] as $alert)
    <div class="flash" style="background:rgba(217,119,6,.1);color:var(--warn)">{{ $alert['message'] }}</div>
@endforeach

<div class="kpi-grid">
    <a class="kpi" href="/seller/products"><div class="t">Products</div><div class="v">{{ number_format($products['total_products']) }}</div><div class="s">{{ number_format($products['approved_products']) }} approved · {{ number_format($products['pending_products']) }} pending</div></a>
    <a class="kpi" href="/seller/orders"><div class="t">Orders</div><div class="v">{{ number_format($orders['total_orders']) }}</div><div class="s">{{ number_format($orders['pending_orders']) }} awaiting action</div></a>
    <div class="kpi"><div class="t">Gross sales</div><div class="v">${{ number_format($orders['gross_sales'], 2) }}</div><div class="s">Seller-scoped orders</div></div>
    <div class="kpi"><div class="t">Net earnings</div><div class="v">${{ number_format($orders['net_earnings'], 2) }}</div><div class="s">After marketplace commission</div></div>
    <a class="kpi" href="/seller/inventory"><div class="t">Available units</div><div class="v">{{ number_format($inventory['available_units']) }}</div><div class="s">{{ number_format($inventory['reserved_units']) }} reserved</div></a>
    <a class="kpi" href="/seller/payouts"><div class="t">Pending payout</div><div class="v">${{ number_format($payouts['pending_payout'], 2) }}</div><div class="s">${{ number_format($payouts['paid_payout'], 2) }} paid</div></a>
</div>

<div style="display:grid;grid-template-columns:minmax(0,1.5fr) minmax(280px,.8fr);gap:16px" class="dashboard-grid">
    <div>
        <div class="card"><div class="card-h"><h2>Recent orders</h2><a href="/seller/orders" class="sub">View all</a></div><div class="table-wrap"><table class="table">
            <thead><tr><th>Order</th><th>Status</th><th class="num">Net</th><th>Created</th></tr></thead>
            <tbody>@forelse($recentOrders as $order)<tr><td class="mono">{{ $order->vendor_order_number ?? '#'.$order->id }}</td><td><span class="badge {{ in_array($order->status, ['fulfilled','delivered','shipped']) ? 'b-ok' : ($order->status === 'cancelled' ? 'b-bad' : 'b-warn') }}">{{ ucfirst($order->status) }}</span></td><td class="num">${{ number_format($order->vendor_net_total ?? 0, 2) }}</td><td class="sub">{{ \Illuminate\Support\Carbon::parse($order->created_at)->diffForHumans() }}</td></tr>@empty<tr><td colspan="4" class="empty">No seller orders yet.</td></tr>@endforelse</tbody>
        </table></div></div>
        <div class="card"><div class="card-h"><h2>Recently updated products</h2><a href="/seller/products" class="sub">View all</a></div><div class="table-wrap"><table class="table">
            <thead><tr><th>Product</th><th>SKU</th><th>Status</th></tr></thead>
            <tbody>@forelse($recentProducts as $product)<tr><td>{{ $product->name }}</td><td class="mono">{{ $product->sku }}</td><td><span class="badge {{ $product->status === 'approved' ? 'b-ok' : ($product->status === 'rejected' ? 'b-bad' : 'b-warn') }}">{{ ucfirst(str_replace('_',' ',$product->status)) }}</span></td></tr>@empty<tr><td colspan="3" class="empty">No products yet.</td></tr>@endforelse</tbody>
        </table></div></div>
    </div>
    <div>
        <div class="card"><div class="card-h"><h2>Seller readiness</h2><span class="badge b-info">{{ $onboardingDone }}/{{ count($onboarding) }}</span></div><div class="card-body">
            @foreach(['profile_created'=>'Business profile','has_marketplace_application'=>'Marketplace application','has_warehouse'=>'Warehouse','has_document'=>'Compliance document','is_verified'=>'Verification'] as $key => $label)
                <div style="display:flex;justify-content:space-between;gap:12px;padding:8px 0;border-bottom:1px solid var(--line)"><span>{{ $label }}</span><span class="badge {{ $onboarding[$key] ? 'b-ok' : 'b-muted' }}">{{ $onboarding[$key] ? 'Complete' : 'Required' }}</span></div>
            @endforeach
        </div></div>
        <div class="card"><div class="card-h"><h2>Marketplace access</h2></div><div class="card-body">
            @forelse($overview['marketplace_approvals'] as $approval)<div style="display:flex;justify-content:space-between;gap:12px;padding:8px 0"><span>{{ $approval->country_name ?? 'Global' }} @if($approval->country_iso_code_2)({{ $approval->country_iso_code_2 }})@endif · {{ $approval->marketplace_name ?? $approval->marketplace_code ?? 'Marketplace' }}</span><span class="badge {{ $approval->status === 'approved' ? 'b-ok' : 'b-warn' }}">{{ ucfirst($approval->status) }}</span></div>@empty<p class="sub">No marketplace applications yet.</p>@endforelse
        </div></div>
        <div class="card"><div class="card-h"><h2>Quick actions</h2></div><div class="card-body actions-row"><a href="/seller/products" class="btn btn-primary">Manage products</a><a href="/seller/inventory" class="btn btn-ghost">Check inventory</a><a href="/seller/support" class="btn btn-ghost">Get support</a></div></div>
    </div>
</div>
<style nonce="{{ $csp_nonce ?? '' }}">@media(max-width:980px){.dashboard-grid{grid-template-columns:1fr!important}}</style>
@endsection
