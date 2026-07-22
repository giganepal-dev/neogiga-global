@extends('seller.layout')
@section('title','Payouts')
@section('content')
<div class="page-intro"><h1>Payouts</h1><p>Settlement history for your completed seller orders.</p></div>
<div class="card"><div class="card-h"><h2>Payout history</h2><span class="badge b-muted">{{ number_format($payouts->total()) }} payouts</span></div><div class="table-wrap"><table class="table">
    <thead><tr><th>Payout</th><th>Status</th><th>Currency</th><th class="num">Amount</th><th>Paid</th></tr></thead>
    <tbody>@forelse($payouts as $payout)<tr><td class="mono">{{ $payout->payout_number ?? '#'.$payout->id }}</td><td><span class="badge {{ ($payout->status ?? '') === 'paid' ? 'b-ok' : 'b-warn' }}">{{ ucfirst($payout->status ?? 'pending') }}</span></td><td>{{ $payout->currency_code ?? $payout->currency ?? 'USD' }}</td><td class="num">{{ number_format($payout->net_amount ?? $payout->amount ?? 0, 2) }}</td><td class="sub">{{ $payout->paid_at ?? '—' }}</td></tr>@empty<tr><td colspan="5" class="empty">No payouts issued yet.</td></tr>@endforelse</tbody>
</table></div>@if($payouts->hasPages())<div class="card-body">{{ $payouts->links() }}</div>@endif</div>
@endsection
