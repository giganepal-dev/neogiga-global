@extends('admin.layout')
@section('title','Affiliates')
@section('crumb','Referral partners and commissions')
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Affiliates</div><div class="v tnum">{{ number_format($stats['affiliates']) }}</div><div class="s">registered</div></div>
    <div class="kpi"><div class="t">Pending approval</div><div class="v tnum">{{ number_format($stats['pending']) }}</div><div class="s">awaiting</div></div>
    <div class="kpi"><div class="t">Commission pending</div><div class="v tnum">{{ number_format($stats['commissionsPending'], 2) }}</div><div class="s">unapproved</div></div>
    <div class="kpi"><div class="t">Commission approved</div><div class="v tnum">{{ number_format($stats['commissionsApproved'], 2) }}</div><div class="s">payable</div></div>
    <div class="kpi"><div class="t">Payout requests</div><div class="v tnum">{{ number_format($stats['payoutRequests']) }}</div><div class="s">all time</div></div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-h"><h2>Affiliates</h2><span class="sub">Latest 20</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Affiliate</th><th>Email</th><th class="num">Earned</th><th class="num">Paid</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        @forelse ($affiliates as $a)
            <tr>
                <td><strong>{{ $a->display_name ?? ('Affiliate #'.$a->id) }}</strong></td>
                <td>{{ $a->email ?? '—' }}</td>
                <td class="num tnum">{{ number_format($a->total_earned, 2) }}</td>
                <td class="num tnum">{{ number_format($a->total_paid, 2) }}</td>
                <td><span class="badge {{ $a->status === 'approved' ? 'b-ok' : ($a->status === 'pending' ? 'b-muted' : 'b-info') }}">{{ $a->status }}</span></td>
                <td>@if($a->status === 'pending')<form method="post" action="/admin/affiliate/{{ $a->id }}/approve">@csrf<button class="btn btn-primary" type="submit">Approve</button></form>@else<span class="sub">—</span>@endif</td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty"><h3>No affiliates yet</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-h"><h2>Commission Ledger</h2><span class="sub">Latest 20 · approve to make payable</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>#</th><th>Order</th><th class="num">Commission</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        @forelse ($commissions as $c)
            <tr>
                <td class="mono">{{ $c->id }}</td>
                <td>{{ $c->order_id ? '#'.$c->order_id : '—' }}</td>
                <td class="num tnum">{{ number_format($c->commission_amount, 2) }} {{ $c->currency }}</td>
                <td><span class="badge {{ in_array($c->status,['approved','paid']) ? 'b-ok' : ($c->status === 'pending' ? 'b-muted' : 'b-info') }}">{{ $c->status }}</span></td>
                <td>@if($c->status === 'pending')<form method="post" action="/admin/affiliate/commissions/{{ $c->id }}/approve">@csrf<button class="btn btn-primary" type="submit">Approve</button></form>@else<span class="sub">—</span>@endif</td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty"><h3>No commissions recorded</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="card">
    <div class="card-h"><h2>Commission Rules</h2><span class="sub">Rate table</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>#</th><th>Name</th><th>Scope</th><th class="num">Priority</th><th class="num">Rate</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($rules as $r)
            <tr>
                <td class="mono">{{ $r->id }}</td>
                <td>{{ $r->name }}</td>
                <td>{{ $r->scope }}</td>
                <td class="num tnum">{{ $r->priority }}</td>
                <td class="num tnum">{{ $r->type === 'percentage' ? rtrim(rtrim(number_format($r->rate,2),'0'),'.').'%' : number_format($r->rate,2).' '.$r->currency }}</td>
                <td><span class="badge {{ $r->is_active ? 'b-ok' : 'b-muted' }}">{{ $r->is_active ? 'Active' : 'Inactive' }}</span></td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty"><h3>No commission rules</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

@endsection
