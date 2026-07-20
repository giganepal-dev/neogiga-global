@extends('distributor.layout')
@section('title','Payouts')
@section('content')
<div class="page-intro"><h1>Payouts</h1><p>Settlement batches for approved commissions.</p></div>
<div class="card"><div class="card-h"><h2>Payout history</h2></div><div class="table-wrap"><table class="table">
    <thead><tr><th>Number</th><th>Status</th><th>Gross</th><th>Net</th><th>Paid at</th></tr></thead>
    <tbody>@forelse($payouts as $p)<tr>
        <td class="mono">{{ $p->payout_number ?? '#'.$p->id }}</td>
        <td><span class="badge {{ ($p->status ?? '') === 'paid' ? 'b-ok' : 'b-info' }}">{{ $p->status ?? 'pending' }}</span></td>
        <td>${{ number_format($p->gross_amount ?? 0, 2) }}</td>
        <td><strong>${{ number_format($p->net_amount ?? 0, 2) }}</strong></td>
        <td>{{ $p->paid_at ?? '—' }}</td>
    </tr>@empty<tr><td colspan="5" class="sub">No payouts issued yet.</td></tr>@endforelse</tbody>
</table></div>{{ $payouts->links() }}</div>
@endsection
