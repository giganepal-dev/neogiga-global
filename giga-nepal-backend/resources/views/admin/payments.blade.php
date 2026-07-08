@extends('admin.layout')
@section('title','Payments & Wallet')
@section('crumb','Providers, wallets and vendor payouts')
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Providers</div><div class="v tnum">{{ number_format($stats['providers']) }}</div><div class="s">registered</div></div>
    <div class="kpi"><div class="t">Enabled</div><div class="v tnum">{{ number_format($stats['enabled']) }}</div><div class="s">accepting</div></div>
    <div class="kpi"><div class="t">Wallets</div><div class="v tnum">{{ number_format($stats['wallets']) }}</div><div class="s">customer</div></div>
    <div class="kpi"><div class="t">Wallet balance</div><div class="v tnum">{{ number_format($stats['walletBalance'], 2) }}</div><div class="s">total held</div></div>
    <div class="kpi"><div class="t">Payouts pending</div><div class="v tnum">{{ number_format($stats['payoutsPending']) }}</div><div class="s">to review</div></div>
</div>

<div class="note" style="margin-bottom:16px">
    Providers ship <strong>disabled &amp; sandbox</strong>. Enabling a provider here turns it on for checkout selection only —
    live gateway credentials are configured separately and are never edited from this screen.
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-h"><h2>Payment Providers</h2><span class="sub">Toggle availability</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Provider</th><th>Code</th><th>Mode</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        @forelse ($providers as $p)
            <tr>
                <td><strong>{{ $p->name }}</strong></td>
                <td class="mono">{{ $p->code }}</td>
                <td>@if($p->is_live)<span class="badge b-info">Live</span>@else<span class="badge b-muted">Sandbox</span>@endif</td>
                <td>@if($p->is_enabled)<span class="badge b-ok">Enabled</span>@else<span class="badge b-muted">Disabled</span>@endif</td>
                <td>
                    <form method="post" action="/admin/payments/providers/{{ $p->id }}/toggle">@csrf
                        <button class="btn {{ $p->is_enabled ? '' : 'btn-primary' }}" type="submit">{{ $p->is_enabled ? 'Disable' : 'Enable' }}</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty"><h3>No providers seeded</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-h"><h2>Vendor Payouts</h2><span class="sub">Latest 20</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Payout</th><th>Vendor</th><th class="num">Amount</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        @forelse ($vendorPayouts as $v)
            <tr>
                <td class="mono">{{ $v->payout_number }}</td>
                <td>#{{ $v->vendor_id }}</td>
                <td class="num tnum">{{ number_format($v->amount, 2) }} {{ $v->currency }}</td>
                <td><span class="badge {{ $v->status === 'paid' ? 'b-ok' : ($v->status === 'approved' ? 'b-info' : 'b-muted') }}">{{ $v->status }}</span></td>
                <td>
                    @if($v->status === 'pending')
                        <form method="post" action="/admin/payments/payouts/{{ $v->id }}/approve">@csrf<button class="btn btn-primary" type="submit">Approve</button></form>
                    @elseif(in_array($v->status, ['approved','processing']))
                        <form method="post" action="/admin/payments/payouts/{{ $v->id }}/mark-paid">@csrf<button class="btn" type="submit">Mark paid</button></form>
                    @else <span class="sub">—</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty"><h3>No vendor payouts yet</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="card">
    <div class="card-h"><h2>Recent Payment Events</h2><span class="sub">Transaction event log</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Event</th><th>Provider</th><th>Order</th><th class="num">Amount</th><th>When</th></tr></thead>
        <tbody>
        @forelse ($events as $e)
            <tr>
                <td><span class="badge b-muted">{{ $e->event }}</span></td>
                <td class="mono">{{ $e->provider_code ?? '—' }}</td>
                <td>{{ $e->order_id ? '#'.$e->order_id : '—' }}</td>
                <td class="num tnum">{{ $e->amount !== null ? number_format($e->amount, 2).' '.$e->currency : '—' }}</td>
                <td class="sub">{{ $e->created_at }}</td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty"><h3>No payment events recorded</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

@endsection
