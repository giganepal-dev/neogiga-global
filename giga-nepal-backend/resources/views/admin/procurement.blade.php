@extends('admin.layout')
@section('title','Suppliers & Purchase Orders')
@section('crumb','Procurement')
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">Suppliers</div><div class="v tnum">{{ number_format($stats['suppliers']) }}</div><div class="s">registered</div></div>
    <div class="kpi"><div class="t">Purchase orders</div><div class="v tnum">{{ number_format($stats['purchaseOrders']) }}</div><div class="s">all time</div></div>
    <div class="kpi"><div class="t">Open PO value</div><div class="v tnum">{{ number_format($stats['openValue'], 2) }}</div><div class="s">ordered / receiving</div></div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-h"><h2>Suppliers</h2><span class="sub">Latest 20</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Code</th><th>Name</th><th>Email</th><th>Terms</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($suppliers as $s)
            <tr>
                <td class="mono">{{ $s->code }}</td>
                <td><strong>{{ $s->name }}</strong></td>
                <td>{{ $s->email ?? '—' }}</td>
                <td>{{ $s->payment_terms ?? '—' }}</td>
                <td><span class="badge {{ $s->status === 'active' ? 'b-ok' : 'b-muted' }}">{{ $s->status }}</span></td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty"><h3>No suppliers yet</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="card">
    <div class="card-h"><h2>Purchase Orders</h2><span class="sub">Latest 20</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>PO #</th><th>Supplier</th><th class="num">Total</th><th>Status</th><th>Expected</th></tr></thead>
        <tbody>
        @forelse ($purchaseOrders as $po)
            <tr>
                <td class="mono"><strong>{{ $po->po_number }}</strong></td>
                <td>{{ $po->supplier_name ?? ('#'.$po->supplier_id) }}</td>
                <td class="num tnum">{{ number_format($po->grand_total, 2) }} {{ $po->currency }}</td>
                <td><span class="badge {{ $po->status === 'received' ? 'b-ok' : ($po->status === 'draft' ? 'b-muted' : 'b-info') }}">{{ $po->status }}</span></td>
                <td class="sub">{{ $po->expected_at ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="5"><div class="empty"><h3>No purchase orders yet</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="note" style="margin-top:16px">Purchase orders and supplier records are created through the procurement API / seller flows; this screen is the review console.</div>

@endsection
