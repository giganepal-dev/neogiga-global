@extends('admin.layout')
@section('title','POS')
@section('crumb','Terminals, sessions, sales and payments')
@section('content')
<div class="note"><strong>Provider-safe POS:</strong> Payments are recorded as local methods only. External payment capture is not enabled.</div>
<div class="grid kpis">
    <div class="kpi"><div class="t">Terminals</div><div class="v tnum">{{ number_format($stats['terminals']) }}</div><div class="s">registered</div></div>
    <div class="kpi"><div class="t">Open Sessions</div><div class="v tnum">{{ number_format($stats['openSessions']) }}</div><div class="s">cash drawers</div></div>
    <div class="kpi"><div class="t">Sales</div><div class="v tnum">{{ number_format($stats['sales']) }}</div><div class="s">{{ number_format($stats['paidSales']) }} paid</div></div>
    <div class="kpi"><div class="t">Revenue</div><div class="v tnum">{{ number_format((float) $stats['revenue'], 2) }}</div><div class="s">local recorded total</div></div>
</div>
<div class="grid" style="grid-template-columns:1fr 1fr;align-items:start">
<div class="card"><div class="card-h"><h2>Recent Sessions</h2><span class="sub">API: /api/v1/pos/sessions/open</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Session</th><th>Warehouse</th><th>Status</th><th>Opened</th></tr></thead><tbody>@forelse($sessions as $s)<tr><td class="mono">{{ $s->session_number ?? ('#'.$s->id) }}</td><td>#{{ $s->warehouse_id }}</td><td><span class="badge {{ $s->status === 'open' ? 'b-ok':'b-muted' }}">{{ $s->status }}</span></td><td>{{ $s->opened_at ?? '—' }}</td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No sessions yet</h3></div></td></tr>@endforelse</tbody></table></div></div>
<div class="card"><div class="card-h"><h2>Recent Sales</h2><span class="sub">Local sales ledger</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Sale</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead><tbody>@forelse($sales as $s)<tr><td class="mono">{{ $s->sale_reference ?? ('#'.$s->id) }}</td><td class="tnum">{{ number_format((float) $s->total_amount, 2) }}</td><td><span class="badge b-muted">{{ $s->payment_status ?? 'pending' }}</span></td><td>{{ $s->status ?? 'draft' }}</td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No sales yet</h3></div></td></tr>@endforelse</tbody></table></div></div>
</div>
@endsection
