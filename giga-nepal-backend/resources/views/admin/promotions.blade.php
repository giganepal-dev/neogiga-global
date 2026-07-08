@extends('admin.layout')
@section('title','Coupons & Gift Cards')
@section('crumb','Promotions and stored value')
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Coupons</div><div class="v tnum">{{ number_format($stats['coupons']) }}</div><div class="s">total</div></div>
    <div class="kpi"><div class="t">Active coupons</div><div class="v tnum">{{ number_format($stats['activeCoupons']) }}</div><div class="s">redeemable</div></div>
    <div class="kpi"><div class="t">Gift cards</div><div class="v tnum">{{ number_format($stats['giftCards']) }}</div><div class="s">issued</div></div>
    <div class="kpi"><div class="t">Gift balance</div><div class="v tnum">{{ number_format($stats['giftBalance'], 2) }}</div><div class="s">outstanding</div></div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-h"><h2>Create Coupon</h2><span class="sub">Server-side validated</span></div>
    <form method="post" action="/admin/promotions/coupons" class="form-stack" style="padding:16px;display:grid;grid-template-columns:repeat(3,1fr);gap:12px">@csrf
        <input class="control" name="code" required maxlength="64" placeholder="Code e.g. WELCOME10">
        <select class="control" name="type" required><option value="percentage">Percentage %</option><option value="fixed">Fixed amount</option></select>
        <input class="control" name="value" type="number" step="0.01" min="0" required placeholder="Value">
        <input class="control" name="currency" maxlength="3" placeholder="Currency (USD)">
        <input class="control" name="min_order_total" type="number" step="0.01" min="0" placeholder="Min order total">
        <input class="control" name="usage_limit" type="number" min="0" placeholder="Usage limit (blank = ∞)">
        <input class="control" name="ends_at" type="datetime-local">
        <button class="btn btn-primary" type="submit" style="grid-column:1/2">Create Coupon</button>
    </form>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-h"><h2>Coupons</h2><span class="sub">Latest 25</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Code</th><th>Type</th><th class="num">Value</th><th class="num">Min order</th><th class="num">Used</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        @forelse ($coupons as $c)
            <tr>
                <td class="mono"><strong>{{ $c->code }}</strong></td>
                <td>{{ $c->type }}</td>
                <td class="num tnum">{{ $c->type === 'percentage' ? rtrim(rtrim(number_format($c->value,2),'0'),'.').'%' : number_format($c->value,2).' '.$c->currency }}</td>
                <td class="num tnum">{{ number_format($c->min_order_total, 2) }}</td>
                <td class="num tnum">{{ number_format($c->used_count) }}{{ $c->usage_limit ? '/'.number_format($c->usage_limit) : '' }}</td>
                <td>@if($c->is_active)<span class="badge b-ok">Active</span>@else<span class="badge b-muted">Inactive</span>@endif</td>
                <td><form method="post" action="/admin/promotions/coupons/{{ $c->id }}/toggle">@csrf<button class="btn" type="submit">{{ $c->is_active ? 'Deactivate' : 'Activate' }}</button></form></td>
            </tr>
        @empty
            <tr><td colspan="7"><div class="empty"><h3>No coupons yet</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="card">
    <div class="card-h"><h2>Gift Cards</h2><span class="sub">Latest 25</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Code</th><th class="num">Balance</th><th class="num">Initial</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($giftCards as $g)
            <tr>
                <td class="mono">{{ $g->code }}</td>
                <td class="num tnum">{{ number_format($g->current_balance, 2) }} {{ $g->currency }}</td>
                <td class="num tnum">{{ number_format($g->initial_balance, 2) }}</td>
                <td><span class="badge {{ $g->status === 'active' ? 'b-ok' : 'b-muted' }}">{{ $g->status }}</span></td>
            </tr>
        @empty
            <tr><td colspan="4"><div class="empty"><h3>No gift cards issued</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

@endsection
