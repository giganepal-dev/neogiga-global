@extends('admin.layout')
@section('title','RFQ & Quotations')
@section('crumb','B2B request-for-quote pipeline')
@section('content')

<div class="grid kpis">
    <div class="kpi"><div class="t">RFQs</div><div class="v tnum">{{ number_format($stats['rfqTotal']) }}</div><div class="s">all time</div></div>
    <div class="kpi"><div class="t">RFQs open</div><div class="v tnum">{{ number_format($stats['rfqOpen']) }}</div><div class="s">awaiting quote</div></div>
    <div class="kpi"><div class="t">Quotes sent</div><div class="v tnum">{{ number_format($stats['quotesSent']) }}</div><div class="s">pending reply</div></div>
    <div class="kpi"><div class="t">Quotes accepted</div><div class="v tnum">{{ number_format($stats['quotesAccepted']) }}</div><div class="s">won</div></div>
</div>

<div class="card" style="margin-bottom:16px">
    <div class="card-h"><h2>RFQ Requests</h2><span class="sub">Latest 20</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>RFQ #</th><th>Company</th><th>Contact</th><th>Status</th></tr></thead>
        <tbody>
        @forelse ($rfqs as $r)
            <tr>
                <td class="mono"><strong>{{ $r->rfq_number }}</strong></td>
                <td>{{ $r->company_name ?? '—' }}</td>
                <td>{{ $r->contact_email ?? $r->contact_name ?? '—' }}</td>
                <td><span class="badge {{ $r->status === 'open' ? 'b-info' : ($r->status === 'closed' ? 'b-ok' : 'b-muted') }}">{{ $r->status }}</span></td>
            </tr>
        @empty
            <tr><td colspan="4"><div class="empty"><h3>No RFQs yet</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="card">
    <div class="card-h"><h2>Quotations</h2><span class="sub">Latest 20</span></div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>Quote #</th><th class="num">Total</th><th>Status</th><th>Valid until</th></tr></thead>
        <tbody>
        @forelse ($quotations as $q)
            <tr>
                <td class="mono"><strong>{{ $q->quote_number }}</strong></td>
                <td class="num tnum">{{ number_format($q->grand_total, 2) }} {{ $q->currency }}</td>
                <td><span class="badge {{ $q->status === 'accepted' ? 'b-ok' : ($q->status === 'sent' ? 'b-info' : 'b-muted') }}">{{ $q->status }}</span></td>
                <td class="sub">{{ $q->valid_until ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="4"><div class="empty"><h3>No quotations yet</h3></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

<div class="note" style="margin-top:16px">RFQs arrive from the public sales API; quotes are issued via the quotation API. This screen is the review console.</div>

@endsection
