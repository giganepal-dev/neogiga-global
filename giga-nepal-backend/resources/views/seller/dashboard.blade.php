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
    $onboardingTotal = count($onboarding);
    $onboardingPercent = $onboardingTotal > 0 ? round(($onboardingDone / $onboardingTotal) * 100) : 0;
@endphp

{{-- Page Intro with Seller Info --}}
<div class="page-intro page-intro--row">
    <div>
        <h1>{{ $v->name }}</h1>
        <p>
            <span class="mono">{{ $v->id ?? 'N/A' }}</span> · 
            {{ ($v->operating_scope ?? 'country') === 'global' ? 'Global seller' : 'Single-country seller' }} · 
            Base: {{ $overview['vendor']['base_country_name'] ?? 'Not assigned' }} · 
            {{ ucfirst($overview['vendor']['commerce_status'] ?? $v->status ?? 'pending') }}
        </p>
        <div style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap">
            <span class="badge {{ ($overview['vendor']['is_verified'] ?? false) ? 'b-ok' : 'b-warn' }}">
                {{ ($overview['vendor']['is_verified'] ?? false) ? '✓ Verified' : '⏳ Verification pending' }}
            </span>
            @if(isset($overview['vendor']['seller_type']))
            <span class="badge b-info">{{ ucfirst($overview['vendor']['seller_type']) }}</span>
            @endif
            @if(isset($overview['vendor']['rating']))
            <span class="badge b-ok">★ {{ number_format($overview['vendor']['rating'], 1) }}/5</span>
            @endif
        </div>
    </div>
    <div style="text-align:right">
        <div class="badge b-info" style="margin-bottom:6px">Onboarding: {{ $onboardingPercent }}%</div>
        <div style="width:140px;height:6px;background:rgba(100,116,139,.2);border-radius:3px;overflow:hidden">
            <div style="width:{{ $onboardingPercent }}%;height:100%;background:var(--accent);transition:width .3s"></div>
        </div>
        <div class="sub" style="margin-top:4px">{{ $onboardingDone }}/{{ $onboardingTotal }} steps complete</div>
    </div>
</div>

{{-- Actionable Alerts --}}
@if(count($overview['alerts']) > 0)
    @foreach($overview['alerts'] as $alert)
        <div class="flash" role="alert" style="background:rgba(217,119,6,.1);color:var(--warn);display:flex;justify-content:space-between;align-items:center;gap:12px">
            <span>{{ $alert['message'] }}</span>
            @if(isset($alert['action_url']) && isset($alert['action_label']))
                <a href="{{ $alert['action_url'] }}" class="btn btn-primary" style="padding:6px 12px;font-size:.85rem">{{ $alert['action_label'] }}</a>
            @endif
        </div>
    @endforeach
@endif

{{-- Show actionable warnings for common issues --}}
@if(!($overview['vendor']['is_verified'] ?? false))
    <div class="flash" role="alert" style="background:rgba(217,119,6,.1);color:var(--warn);display:flex;justify-content:space-between;align-items:center;gap:12px">
        <div>
            <strong>Verification pending</strong>
            <span class="sub" style="display:block;margin-top:4px">Complete compliance documents to activate selling capabilities.</span>
        </div>
        <a href="/seller/readiness" class="btn btn-primary" style="padding:6px 12px;font-size:.85rem">Continue verification</a>
    </div>
@endif

@if(empty($overview['marketplace_approvals']) || collect($overview['marketplace_approvals'])->where('status', 'approved')->isEmpty())
    <div class="flash" role="alert" style="background:rgba(15,98,230,.1);color:var(--info);display:flex;justify-content:space-between;align-items:center;gap:12px">
        <div>
            <strong>No approved marketplace</strong>
            <span class="sub" style="display:block;margin-top:4px">Apply to a regional marketplace to start listing products and receiving orders.</span>
        </div>
        <a href="/seller/marketplace" class="btn btn-primary" style="padding:6px 12px;font-size:.85rem">Apply now</a>
    </div>
@endif

{{-- KPI Grid - Enhanced with more metrics --}}
<div class="kpi-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr))">
    {{-- Product Metrics --}}
    <a class="kpi" href="/seller/products">
        <div class="t">Active Products</div>
        <div class="v">{{ number_format($products['approved_products'] ?? 0) }}</div>
        <div class="s">{{ number_format($products['pending_products'] ?? 0) }} pending · {{ number_format($products['rejected_products'] ?? 0) }} rejected</div>
    </a>
    
    {{-- Order Metrics --}}
    <a class="kpi" href="/seller/orders">
        <div class="t">Total Orders</div>
        <div class="v">{{ number_format($orders['total_orders'] ?? 0) }}</div>
        <div class="s">{{ number_format($orders['pending_orders'] ?? 0) }} awaiting action</div>
    </a>
    
    {{-- Financial Metrics --}}
    <div class="kpi">
        <div class="t">Gross Sales</div>
        <div class="v">${{ number_format($orders['gross_sales'] ?? 0, 2) }}</div>
        <div class="s">Seller-scoped orders</div>
    </div>
    
    <div class="kpi">
        <div class="t">Net Earnings</div>
        <div class="v">${{ number_format($orders['net_earnings'] ?? 0, 2) }}</div>
        <div class="s">After marketplace commission</div>
    </div>
    
    <a class="kpi" href="/seller/payouts">
        <div class="t">Pending Payout</div>
        <div class="v">${{ number_format($payouts['pending_payout'] ?? 0, 2) }}</div>
        <div class="s">${{ number_format($payouts['paid_payout'] ?? 0, 2) }} paid</div>
    </a>
    
    {{-- Inventory Metrics --}}
    <a class="kpi" href="/seller/inventory">
        <div class="t">Available Stock</div>
        <div class="v">{{ number_format($inventory['available_units'] ?? 0) }}</div>
        <div class="s">{{ number_format($inventory['reserved_units'] ?? 0) }} reserved</div>
    </a>
    
    <a class="kpi" href="/seller/inventory/alerts">
        <div class="t">Low Stock Items</div>
        <div class="v" style="color:{{ ($inventory['low_stock_count'] ?? 0) > 0 ? 'var(--warn)' : 'var(--ok)' }}">
            {{ number_format($inventory['low_stock_count'] ?? 0) }}
        </div>
        <div class="s">Require attention</div>
    </a>
    
    {{-- Additional Metrics --}}
    <a class="kpi" href="/seller/rfqs">
        <div class="t">Open RFQs</div>
        <div class="v">{{ number_format($overview['rfqs']['open_rfqs'] ?? 0) }}</div>
        <div class="s">Awaiting quotation</div>
    </a>
    
    <a class="kpi" href="/seller/support">
        <div class="t">Support Tickets</div>
        <div class="v">{{ number_format($overview['support']['open_tickets'] ?? 0) }}</div>
        <div class="s">{{ number_format($overview['support']['pending_responses'] ?? 0) }} need response</div>
    </a>
</div>

{{-- Main Content Grid --}}
<div style="display:grid;grid-template-columns:minmax(0,1.5fr) minmax(320px,.8fr);gap:16px" class="dashboard-grid">
    <div>
        {{-- Recent Orders --}}
        <div class="card">
            <div class="card-h">
                <h2>Recent Orders</h2>
                <a href="/seller/orders" class="sub">View all →</a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Status</th>
                            <th class="num">Net Amount</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentOrders as $order)
                        <tr>
                            <td class="mono">{{ $order->vendor_order_number ?? '#'.$order->id }}</td>
                            <td>
                                <span class="badge {{ in_array($order->status, ['fulfilled','delivered','shipped']) ? 'b-ok' : ($order->status === 'cancelled' ? 'b-bad' : 'b-warn') }}">
                                    {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                </span>
                            </td>
                            <td class="num tnum">${{ number_format($order->vendor_net_total ?? 0, 2) }}</td>
                            <td class="sub">{{ \Illuminate\Support\Carbon::parse($order->created_at)->diffForHumans() }}</td>
                            <td>
                                <a href="/seller/orders?search={{ $order->vendor_order_number ?? $order->id }}" class="btn btn-ghost" style="padding:4px 8px;font-size:.8rem">View</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="empty">No seller orders yet. Complete your onboarding to start receiving orders.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Recently Updated Products --}}
        <div class="card">
            <div class="card-h">
                <h2>Recently Updated Products</h2>
                <a href="/seller/products" class="sub">View all →</a>
            </div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>SKU/MPN</th>
                            <th>Status</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentProducts as $product)
                        <tr>
                            <td>{{ $product->name }}</td>
                            <td class="mono sub">{{ $product->sku ?? $product->mpn ?? 'N/A' }}</td>
                            <td>
                                <span class="badge {{ $product->status === 'approved' ? 'b-ok' : ($product->status === 'rejected' ? 'b-bad' : 'b-warn') }}">
                                    {{ ucfirst(str_replace('_',' ',$product->status)) }}
                                </span>
                            </td>
                            <td class="sub">{{ \Illuminate\Support\Carbon::parse($product->updated_at)->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="empty">No products yet. <a href="/seller/products/add" class="sub">Add your first product →</a></td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        
        {{-- Performance Summary --}}
        @if(isset($overview['performance']))
        <div class="card">
            <div class="card-h">
                <h2>Seller Performance</h2>
                <a href="/seller/performance" class="sub">Details →</a>
            </div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
                    @php $metrics = $overview['performance'] ?? []; @endphp
                    <div style="text-align:center;padding:12px;background:rgba(22,163,74,.08);border-radius:10px">
                        <div class="sub">Order Acceptance</div>
                        <div class="v" style="font-size:1.2rem;color:var(--ok)">{{ $metrics['order_acceptance_rate'] ?? '--' }}%</div>
                    </div>
                    <div style="text-align:center;padding:12px;background:rgba(15,98,230,.08);border-radius:10px">
                        <div class="sub">On-time Dispatch</div>
                        <div class="v" style="font-size:1.2rem;color:var(--info)">{{ $metrics['on_time_dispatch_rate'] ?? '--' }}%</div>
                    </div>
                    <div style="text-align:center;padding:12px;background:rgba(249,189,44,.14);border-radius:10px">
                        <div class="sub">Account Health</div>
                        <div class="v" style="font-size:1.2rem;color:var(--warn)">{{ $metrics['account_health'] ?? 'Good' }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    
    <div>
        {{-- Seller Readiness Checklist --}}
        <div class="card">
            <div class="card-h">
                <h2>Seller Readiness</h2>
                <span class="badge b-info">{{ $onboardingDone }}/{{ $onboardingTotal }}</span>
            </div>
            <div class="card-body" style="padding:0">
                @foreach(['profile_created'=>'Business profile','has_marketplace_application'=>'Marketplace application','has_warehouse'=>'Warehouse setup','has_document'=>'Compliance documents','is_verified'=>'Verification approval'] as $key => $label)
                    <a href="/seller/readiness" style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--line);text-decoration:none;color:inherit;transition:background .15s" 
                       onmouseover="this.style.background='rgba(127,127,127,.06)'" onmouseout="this.style.background='transparent'">
                        <span style="display:flex;align-items:center;gap:10px">
                            <span style="width:18px;height:18px;border-radius:50%;display:grid;place-items:center;font-size:.7rem;background:{{ $onboarding[$key] ?? false ? 'var(--ok)' : 'rgba(100,116,139,.2)' }};color:#fff">
                                {{ $onboarding[$key] ?? false ? '✓' : '' }}
                            </span>
                            <span>{{ $label }}</span>
                        </span>
                        <span class="badge {{ $onboarding[$key] ?? false ? 'b-ok' : 'b-muted' }}" style="font-size:.7rem">
                            {{ $onboarding[$key] ?? false ? 'Complete' : 'Required' }}
                        </span>
                    </a>
                @endforeach
            </div>
            @if($onboardingDone < $onboardingTotal)
            <div class="card-h" style="border-top:1px solid var(--line);background:#fff">
                <a href="/seller/readiness" class="btn btn-primary" style="width:100%;justify-content:center">Complete Onboarding</a>
            </div>
            @endif
        </div>
        
        {{-- Marketplace Access Status --}}
        <div class="card">
            <div class="card-h">
                <h2>Marketplace Access</h2>
                <a href="/seller/marketplace" class="sub">Apply →</a>
            </div>
            <div class="card-body" style="padding:0">
                @forelse($overview['marketplace_approvals'] ?? [] as $approval)
                <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--line)">
                    <div>
                        <div style="font-weight:600">{{ $approval->country_name ?? 'Global' }} 
                            @if($approval->country_iso_code_2)<span class="sub">({{ $approval->country_iso_code_2 }})</span>@endif
                        </div>
                        <div class="sub" style="font-size:.75rem">{{ $approval->marketplace_name ?? $approval->marketplace_code ?? 'Marketplace' }}</div>
                    </div>
                    <span class="badge {{ $approval->status === 'approved' ? 'b-ok' : ($approval->status === 'rejected' ? 'b-bad' : 'b-warn') }}">
                        {{ ucfirst($approval->status) }}
                    </span>
                </div>
                @empty
                <div style="padding:24px 16px;text-align:center">
                    <p class="sub" style="margin:0 0 12px">No marketplace applications yet.</p>
                    <a href="/seller/marketplace" class="btn btn-primary" style="font-size:.85rem">Apply to Marketplace</a>
                </div>
                @endforelse
            </div>
        </div>
        
        {{-- Quick Actions --}}
        <div class="card">
            <div class="card-h">
                <h2>Quick Actions</h2>
            </div>
            <div class="card-body actions-row" style="display:grid;gap:8px">
                <a href="/seller/products/add" class="btn btn-primary" style="justify-content:start">
                    <x-icon name="add" :size="18" /> Add Product
                </a>
                <a href="/seller/products/match" class="btn btn-ghost" style="justify-content:start">
                    <x-icon name="search" :size="18" /> Match MPN
                </a>
                <a href="/seller/inventory/import" class="btn btn-ghost" style="justify-content:start">
                    <x-icon name="upload" :size="18" /> Import Stock
                </a>
                <a href="/seller/warehouses" class="btn btn-ghost" style="justify-content:start">
                    <x-icon name="warehouse" :size="18" /> Manage Warehouses
                </a>
                <a href="/seller/support" class="btn btn-ghost" style="justify-content:start">
                    <x-icon name="support" :size="18" /> Get Support
                </a>
            </div>
        </div>
        
        {{-- Notifications Preview --}}
        @if(isset($overview['notifications']) && count($overview['notifications']) > 0)
        <div class="card">
            <div class="card-h">
                <h2>Recent Notifications</h2>
                <a href="/seller/notifications" class="sub">View all →</a>
            </div>
            <div class="card-body" style="padding:0">
                @foreach(array_slice($overview['notifications'], 0, 3) as $notification)
                <div style="padding:12px 16px;border-bottom:1px solid var(--line);{{ $notification->read_at ? '' : 'background:rgba(15,98,230,.04)' }}">
                    <div style="font-weight:600;font-size:.9rem">{{ $notification->title ?? 'Notification' }}</div>
                    <div class="sub" style="font-size:.8rem;margin-top:2px">{{ \Illuminate\Support\Carbon::parse($notification->created_at)->diffForHumans() }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<style nonce="{{ $csp_nonce ?? '' }}">
@media(max-width:980px){.dashboard-grid{grid-template-columns:1fr!important}}
.kpi-grid{grid-template-columns:repeat(auto-fit,minmax(150px,1fr))!important}
</style>
@endsection
