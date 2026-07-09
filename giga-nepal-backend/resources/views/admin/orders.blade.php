@extends('admin.layout')
@section('title','Orders')
@section('crumb','Order management')
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Orders</div><div class="v tnum">{{ number_format($stats['total']) }}</div><div class="s">all time</div></div>
    <div class="kpi"><div class="t">Pending</div><div class="v tnum">{{ number_format($stats['pending']) }}</div><div class="s">to confirm</div></div>
    <div class="kpi"><div class="t">Processing</div><div class="v tnum">{{ number_format($stats['processing']) }}</div><div class="s">in fulfilment</div></div>
    <div class="kpi"><div class="t">Delivered</div><div class="v tnum">{{ number_format($stats['delivered']) }}</div><div class="s">completed</div></div>
    <div class="kpi"><div class="t">Unpaid</div><div class="v tnum">{{ number_format($stats['unpaid']) }}</div><div class="s">payment pending</div></div>
</div>

@php
    $statusBadge = fn($s) => match($s) {
        'delivered' => 'b-ok',
        'confirmed', 'processing', 'shipped' => 'b-info',
        default => 'b-muted',
    };
    $payBadge = fn($s) => $s === 'paid' ? 'b-ok' : ($s === 'partial' ? 'b-info' : 'b-muted');
    $statuses = ['pending','confirmed','processing','shipped','delivered','cancelled','refunded','failed'];
@endphp

<div class="card">
    <div class="card-h">
        <h2>Orders</h2>
        <form method="get" action="/admin/orders" style="display:flex;gap:8px;flex-wrap:wrap">
            <input class="control" name="q" value="{{ $filters['q'] }}" placeholder="Order #" style="min-height:34px;max-width:160px">
            <select class="control" name="status" style="min-height:34px">
                <option value="">All statuses</option>
                @foreach ($statuses as $s)<option value="{{ $s }}" @selected($filters['status']===$s)>{{ ucfirst($s) }}</option>@endforeach
            </select>
            <select class="control" name="payment" style="min-height:34px">
                <option value="">All payments</option>
                @foreach (['pending','paid','partial','refunded','failed'] as $p)<option value="{{ $p }}" @selected($filters['payment']===$p)>{{ ucfirst($p) }}</option>@endforeach
            </select>
            <button class="btn" type="submit">Filter</button>
        </form>
    </div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Order</th><th>Customer</th><th>Marketplace</th><th class="num">Total</th><th>Payment</th><th>Status</th><th>Placed</th><th>Action</th></tr></thead>
        <tbody>
        @forelse ($orders as $o)
            <tr>
                <td class="mono"><strong>{{ $o->order_number }}</strong></td>
                <td>{{ $o->user->name ?? '—' }}<div class="sub">{{ $o->user->email ?? '' }}</div></td>
                <td>{{ $o->marketplace->code ?? '—' }}</td>
                <td class="num tnum">{{ number_format($o->grand_total, 2) }} {{ $o->currency_code }}</td>
                <td><span class="badge {{ $payBadge($o->payment_status) }}">{{ $o->payment_status }}</span></td>
                <td><span class="badge {{ $statusBadge($o->status) }}">{{ $o->status }}</span></td>
                <td class="sub">{{ $o->created_at?->format('Y-m-d H:i') }}</td>
                <td><a class="btn btn-ghost" href="/admin/orders/{{ $o->id }}">View</a></td>
            </tr>
        @empty
            <tr><td colspan="8"><div class="empty"><h3>No orders yet</h3><p>Orders placed via checkout will appear here.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if ($orders->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $orders->links() }}</div>
    @endif
</div>

@endsection
