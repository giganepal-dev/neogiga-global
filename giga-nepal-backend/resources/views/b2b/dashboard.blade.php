@extends('b2b.layout')
@section('title','Dashboard')
@section('content')
<h1 style="margin:0 0 8px">{{ $a->name }}</h1>
<p style="color:var(--muted);margin:0 0 24px">{{ $a->type ?? 'Business Account' }} · {{ $a->status ?? 'active' }}</p>
<div class="kpi-grid">
    <div class="kpi"><div class="t">Orders</div><div class="v">{{ number_format($stats['order_count']) }}</div><div class="s">total</div></div>
    <div class="kpi"><div class="t">RFQs</div><div class="v">{{ number_format($stats['rfq_count']) }}</div><div class="s">submitted</div></div>
    <div class="kpi"><div class="t">Team Members</div><div class="v">{{ number_format($stats['user_count']) }}</div><div class="s">active</div></div>
    <div class="kpi"><div class="t">Status</div><div class="v"><span class="badge {{ ($a->status??'')==='approved'?'b-ok':'b-info' }}">{{ $a->status ?? 'pending' }}</span></div><div class="s">account</div></div>
</div>
<div class="card"><h2 style="margin:0 0 12px;font-size:1rem">Quick Actions</h2>
    <div style="display:flex;gap:10px;flex-wrap:wrap"><a href="/b2b/orders" class="btn btn-ghost">View Orders</a><a href="/b2b/rfqs" class="btn btn-ghost">RFQ Requests</a><a href="/en/rfq" class="btn btn-ghost">New RFQ</a></div></div>
@endsection
