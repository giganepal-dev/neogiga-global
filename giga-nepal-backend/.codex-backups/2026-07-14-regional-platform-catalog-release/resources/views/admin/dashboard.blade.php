@extends('admin.layout')
@section('title','Dashboard')
@section('crumb','Operating overview of the NeoGiga marketplace')
@section('content')

@php
    $money = fn($v) => number_format((float) $v, 2);
    $cards = [
        ['Products', $stats['products'], 'catalog items'],
        ['Categories', $stats['categories'], 'taxonomy nodes'],
        ['Sellers', $stats['vendors'], 'vendor accounts'],
        ['Customers', $stats['customers'], 'customer records'],
        ['Orders', $stats['orders'], 'all time'],
        ['Sales', $money($stats['sales']), 'gross order value'],
        ['Pending RFQs', $stats['pendingRfqs'], 'open requests'],
        ['Seller Apps', $stats['pendingApplications'], 'pending review'],
        ['Low Stock', $stats['lowStock'], 'needs replenishment'],
        ['Queue Jobs', $stats['queuePending'], 'default queue'],
        ['Support', $stats['openSupport'], 'open tickets'],
        ['AI Chats', $stats['aiConversations'], 'commerce sessions'],
    ];
@endphp

<div class="grid kpis">
    @foreach ($cards as [$label,$val,$sub])
        <div class="kpi">
            <div class="t">{{ $label }}</div>
            <div class="v tnum">{{ is_numeric($val) ? number_format($val) : $val }}</div>
            <div class="s">{{ $sub }}</div>
        </div>
    @endforeach
</div>

<div class="grid dashboard-split">
    <div class="card">
        <div class="card-h"><h2>Recent Orders</h2><a class="btn btn-ghost" href="/admin/orders">Open orders</a></div>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Order</th><th>Status</th><th class="num">Total</th><th>Placed</th></tr></thead>
            <tbody>
            @forelse($recentOrders as $o)
                <tr><td class="mono"><strong>{{ $o->order_number }}</strong></td><td><span class="badge b-info">{{ $o->status }}</span></td><td class="num tnum">{{ $money($o->grand_total) }} {{ $o->currency_code }}</td><td class="sub">{{ $o->created_at }}</td></tr>
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
                <tr><td><strong>{{ $p->name }}</strong></td><td class="mono">{{ $p->sku ?? '—' }}</td><td><span class="badge b-muted">{{ $p->status ?? 'draft' }}</span></td><td class="num tnum">{{ number_format((float) ($p->stock_quantity ?? 0)) }}</td></tr>
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
