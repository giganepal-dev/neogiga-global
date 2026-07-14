@extends('admin.layout')
@section('title','Dashboard')
@section('crumb','Operating overview of the NeoGiga marketplace')
@section('content')

@php
    $money = fn($v) => number_format((float) $v, 2);
    $cards = [
        ['Products', $stats['products'], 'catalog items', '/admin/products'],
        ['Categories', $stats['categories'], 'taxonomy nodes', '/admin/categories'],
        ['Sellers', $stats['vendors'], 'vendor accounts', '/admin/vendors'],
        ['Customers', $stats['customers'], 'customer records', auth()->user()?->hasPermission('customers.view') ? '/admin/marketing/crm' : '/admin/users'],
        ['Orders', $stats['orders'], 'all time', '/admin/orders'],
        ['Sales', $money($stats['sales']), 'gross order value', '/admin/orders'],
        ['Pending RFQs', $stats['pendingRfqs'], 'open requests', '/admin/rfqs'],
        ['Seller Apps', $stats['pendingApplications'], 'pending review', '/admin/applications'],
        ['Low Stock', $stats['lowStock'], 'needs replenishment', '/admin/inventory'],
        ['Queue Jobs', $stats['queuePending'], 'default queue', '/admin/system-health'],
        ['Support', $stats['openSupport'], 'open tickets', '/admin/support'],
        ['AI Chats', $stats['aiConversations'], 'commerce sessions', '/admin/system-health'],
    ];
@endphp

<div class="grid kpis">
    @foreach ($cards as [$label,$val,$sub,$href])
        <a class="kpi" href="{{ $href }}" aria-label="Open {{ $label }}">
            <div class="t">{{ $label }}</div>
            <div class="v tnum">{{ is_numeric($val) ? number_format($val) : $val }}</div>
            <div class="s">{{ $sub }}</div>
        </a>
    @endforeach
</div>

<div class="grid dashboard-split">
    <section class="card">
        <div class="card-h"><div><h2>API Readiness</h2><div class="sub">Registered Laravel endpoints with live platform health</div></div><a class="btn btn-ghost" href="/admin/system-health">Open health</a></div>
        <div style="padding:16px;display:grid;gap:14px">
            <div class="grid" style="grid-template-columns:repeat(3,1fr)">
                <a class="kpi" href="/admin/system-health"><div class="t">API routes</div><div class="v tnum">{{ number_format($apiStats['total']) }}</div><div class="s">registered</div></a>
                <a class="kpi" href="/admin/system-health"><div class="t">Admin APIs</div><div class="v tnum">{{ number_format($apiStats['admin']) }}</div><div class="s">protected</div></a>
                <a class="kpi" href="/admin/system-health"><div class="t">Public APIs</div><div class="v tnum">{{ number_format($apiStats['public']) }}</div><div class="s">catalog &amp; commerce</div></a>
            </div>
            <a href="{{ $apiStats['health']['endpoint'] }}" target="_blank" rel="noopener" style="display:flex;align-items:center;justify-content:space-between;gap:12px;border:1px solid var(--line);border-radius:9px;padding:12px 14px">
                <span><strong>Health endpoint</strong><span class="sub mono" style="display:block">{{ $apiStats['health']['endpoint'] }}</span></span>
                <span class="badge {{ $apiStats['health']['ok'] ? 'b-ok' : 'b-danger' }}">HTTP {{ $apiStats['health']['status'] }}</span>
            </a>
        </div>
    </section>

    <section class="card">
        <div class="card-h"><div><h2>Email Credentials</h2><div class="sub">Encrypted SMTP or HTTP API provider configuration</div></div>@if(auth()->user()?->hasPermission('email.providers.manage'))<a class="btn btn-primary" href="/admin/marketing/settings">Configure</a>@endif</div>
        <div style="padding:16px;display:grid;gap:10px">
            @foreach($providerSummaries as $channel => $provider)
                @php
                    $credentialReady = $provider['transport'] === 'smtp'
                        ? ($provider['smtp_username_configured'] && $provider['smtp_password_configured'])
                        : ($provider['transport'] === 'generic_http' ? $provider['api_key_configured'] : in_array($provider['transport'], ['log', 'sandbox'], true));
                    $settingsHref = auth()->user()?->hasPermission('email.providers.manage') ? '/admin/marketing/settings#'.$channel.'-provider' : '/admin/system-health';
                @endphp
                <a href="{{ $settingsHref }}" style="display:flex;align-items:center;justify-content:space-between;gap:12px;border:1px solid var(--line);border-radius:9px;padding:12px 14px">
                    <span><strong style="text-transform:capitalize">{{ $channel }}</strong><span class="sub" style="display:block">{{ strtoupper($provider['transport']) }} · {{ $provider['source'] === 'admin' ? 'admin configured' : 'environment fallback' }}</span></span>
                    <span class="badge {{ $credentialReady && $provider['is_enabled'] ? 'b-ok' : ($credentialReady ? 'b-info' : 'b-warn') }}">{{ $credentialReady ? ($provider['is_enabled'] ? 'Ready' : 'Configured') : 'Credentials needed' }}</span>
                </a>
            @endforeach
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                @if(auth()->user()?->hasPermission('campaigns.view'))<a class="btn btn-ghost" href="/admin/marketing/email">Compose campaign</a>@endif
                @if(auth()->user()?->hasPermission('customers.import'))<a class="btn btn-ghost" href="/admin/marketing/customer-imports">Import customers</a>@endif
            </div>
        </div>
    </section>
</div>

<div class="grid dashboard-split">
    <div class="card">
        <div class="card-h"><h2>Recent Orders</h2><a class="btn btn-ghost" href="/admin/orders">Open orders</a></div>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Order</th><th>Status</th><th class="num">Total</th><th>Placed</th></tr></thead>
            <tbody>
            @forelse($recentOrders as $o)
                <tr><td class="mono"><a href="/admin/orders/{{ $o->id }}"><strong>{{ $o->order_number }}</strong></a></td><td><span class="badge b-info">{{ $o->status }}</span></td><td class="num tnum">{{ $money($o->grand_total) }} {{ $o->currency_code }}</td><td class="sub">{{ $o->created_at }}</td></tr>
            @empty
                <tr><td colspan="4"><div class="empty"><h3>No orders yet</h3></div></td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="card">
        <div class="card-h"><h2>Country Performance</h2><span class="sub">orders and sales</span></div>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Country</th><th class="num">Orders</th><th class="num">Sales</th></tr></thead>
            <tbody>
            @forelse($countryPerformance as $c)
                <tr><td>{{ $c->name }}</td><td class="num tnum">{{ number_format($c->orders_count) }}</td><td class="num tnum">{{ $money($c->sales_total) }}</td></tr>
            @empty
                <tr><td colspan="3"><div class="empty"><h3>No country sales yet</h3></div></td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>

<div class="grid split stack-gap">
    <div class="card">
        <div class="card-h"><h2>Recent Products</h2><a class="btn btn-ghost" href="/admin/products">Manage</a></div>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Product</th><th>SKU</th><th>Status</th><th class="num">Stock</th></tr></thead>
            <tbody>
            @forelse($recentProducts as $p)
                <tr><td><a href="/admin/products/{{ $p->id }}"><strong>{{ $p->name }}</strong></a></td><td class="mono">{{ $p->sku ?? '—' }}</td><td><span class="badge b-muted">{{ $p->status ?? 'draft' }}</span></td><td class="num tnum">{{ number_format((float) ($p->stock_quantity ?? 0)) }}</td></tr>
            @empty
                <tr><td colspan="4"><div class="empty"><h3>No products yet</h3></div></td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>

    <div class="card">
        <div class="card-h"><h2>Recent Vendors</h2><a class="btn btn-ghost" href="/admin/vendors">Review</a></div>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Seller</th><th>Type</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($recentVendors as $v)
                <tr><td><strong>{{ $v->name }}</strong><div class="sub">{{ $v->email ?? '' }}</div></td><td>{{ $v->type ?? '—' }}</td><td><span class="badge {{ $v->status === 'active' ? 'b-ok' : 'b-muted' }}">{{ $v->status }}</span></td></tr>
            @empty
                <tr><td colspan="3"><div class="empty"><h3>No vendors yet</h3></div></td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>

<div class="grid split stack-gap">
    <div class="card">
        <div class="card-h"><h2>Inventory / Marketing</h2><span class="sub">quick charts</span></div>
        <div style="padding:16px;display:grid;gap:14px">
            <div>
                <div class="sub">Low stock exposure</div>
                <div style="height:10px;background:#e2e8f0;border-radius:999px;overflow:hidden"><div style="height:10px;width:{{ $inventoryBands['total'] ? min(100, round(($inventoryBands['low'] / max(1,$inventoryBands['total'])) * 100)) : 0 }}%;background:#d97706"></div></div>
                <div class="sub">{{ number_format($inventoryBands['low']) }} of {{ number_format($inventoryBands['total']) }} products</div>
            </div>
            <div class="grid" style="grid-template-columns:repeat(3,1fr)">
                <div class="kpi"><div class="t">Email</div><div class="v tnum">{{ number_format($marketingStats['emailCampaigns']) }}</div><div class="s">campaigns</div></div>
                <div class="kpi"><div class="t">Subscribers</div><div class="v tnum">{{ number_format($marketingStats['newsletterSubscribers']) }}</div><div class="s">newsletter</div></div>
                <div class="kpi"><div class="t">Segments</div><div class="v tnum">{{ number_format($marketingStats['segments']) }}</div><div class="s">CRM</div></div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-h"><h2>Support Activity</h2><a class="btn btn-ghost" href="/admin/support">Inbox</a></div>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Ticket</th><th>Priority</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($recentSupport as $t)
                <tr><td><strong>{{ $t->subject }}</strong><div class="sub mono">{{ $t->ticket_number }}</div></td><td><span class="badge {{ $t->priority === 'urgent' ? 'b-danger' : ($t->priority === 'high' ? 'b-warn' : 'b-muted') }}">{{ $t->priority }}</span></td><td>{{ str_replace('_',' ',$t->status) }}</td></tr>
            @empty
                <tr><td colspan="3"><div class="empty"><h3>No support tickets yet</h3></div></td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>

@endsection
