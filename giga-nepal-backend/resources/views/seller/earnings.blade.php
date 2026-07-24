@extends('seller.layout')
@section('title', 'Earnings')
@section('content')

<div class="page-intro">
    <h1>Earnings</h1>
    <p>Track your sales revenue and pending payouts.</p>
</div>

{{-- Earnings Summary --}}
<div class="kpi-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:16px">
    <div class="kpi">
        <div class="t">Total Earned</div>
        <div class="v">${{ number_format($totals->total_earned ?? 0, 2) }}</div>
        <div class="s">Lifetime net earnings</div>
    </div>
    <div class="kpi">
        <div class="t">Completed Sales</div>
        <div class="v" style="color:var(--ok)">${{ number_format($totals->completed_earning ?? 0, 2) }}</div>
        <div class="s">Fulfilled/delivered orders</div>
    </div>
    <div class="kpi">
        <div class="t">Pending Earnings</div>
        <div class="v" style="color:var(--warn)">${{ number_format($totals->pending_earning ?? 0, 2) }}</div>
        <div class="s">Processing orders</div>
    </div>
    <div class="kpi">
        <div class="t">Paid Out</div>
        <div class="v" style="color:var(--info)">${{ number_format($payoutTotals->paid_out ?? 0, 2) }}</div>
        <div class="s">Settled to your account</div>
    </div>
    <div class="kpi">
        <div class="t">Pending Payout</div>
        <div class="v">${{ number_format($payoutTotals->pending_payout ?? 0, 2) }}</div>
        <div class="s">Awaiting settlement</div>
    </div>
    <div class="kpi">
        <div class="t">Available Balance</div>
        <div class="v" style="color:var(--ok)">
            ${{ number_format(($totals->completed_earning ?? 0) - ($payoutTotals->paid_out ?? 0), 2) }}
        </div>
        <div class="s">Net of paid amounts</div>
    </div>
</div>

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
                    <th>Order</th>
                    <th>Status</th>
                    <th class="num">Subtotal</th>
                    <th class="num">Commission</th>
                    <th class="num">Net Earning</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentOrders as $order)
                <tr>
                    <td class="mono">{{ $order->vendor_order_number ?? '#' . $order->id }}</td>
                    <td>
                        <span class="badge {{ in_array($order->status, ['fulfilled', 'delivered', 'shipped']) ? 'b-ok' : ($order->status === 'cancelled' ? 'b-bad' : 'b-warn') }}">
                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                        </span>
                    </td>
                    <td class="num tnum">${{ number_format($order->subtotal ?? 0, 2) }}</td>
                    <td class="num tnum" style="color:var(--bad)">-${{ number_format($order->commission_total ?? 0, 2) }}</td>
                    <td class="num tnum" style="color:var(--ok)">${{ number_format($order->vendor_net_total ?? 0, 2) }}</td>
                    <td class="sub">{{ \Illuminate\Support\Carbon::parse($order->created_at)->diffForHumans() }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6">
                        <div class="empty">
                            <h3>No orders yet</h3>
                            <p>Complete your onboarding to start receiving orders and earning revenue.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
