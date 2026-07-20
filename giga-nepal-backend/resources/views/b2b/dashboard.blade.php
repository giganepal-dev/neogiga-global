@extends('b2b.layout')
@section('title','Dashboard')
@section('content')
<div class="page-intro">
    <h1>{{ $account->name }}</h1>
    <p>{{ ucfirst($account->type ?? 'corporate') }} account · {{ ucfirst($account->status ?? 'pending') }}</p>
</div>
<div class="kpi-grid">
    <div class="kpi"><div class="t">Orders</div><div class="v">{{ number_format($stats['order_count']) }}</div><div class="s">total</div></div>
    <div class="kpi"><div class="t">RFQs</div><div class="v">{{ number_format($stats['rfq_count']) }}</div><div class="s">submitted</div></div>
    <div class="kpi"><div class="t">Quotations</div><div class="v">{{ number_format($stats['quotation_count']) }}</div><div class="s">received</div></div>
    <div class="kpi"><div class="t">Status</div><div class="v"><span class="badge {{ ($account->status ?? '') === 'approved' ? 'b-ok' : 'b-info' }}">{{ $account->status ?? 'pending' }}</span></div><div class="s">account</div></div>
</div>
<div class="card">
    <div class="card-h"><h2>Quick Actions</h2></div>
    <div class="card-body actions-row">
        <a href="/b2b/quotations" class="btn btn-ghost">View Quotations</a>
        <a href="/b2b/rfqs/create" class="btn btn-primary">New Quote Request</a>
        <a href="/b2b/orders" class="btn btn-ghost">Orders</a>
    </div>
</div>
@endsection
