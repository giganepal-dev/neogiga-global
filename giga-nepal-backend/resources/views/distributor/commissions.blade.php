@extends('distributor.layout')
@section('title','Commissions')
@section('content')
<div class="page-intro"><h1>Commissions</h1><p>Earnings from distributor orders in your network.</p></div>
<div class="kpi-grid">
    <div class="kpi"><div class="t">Pending</div><div class="v">${{ number_format($summary['pending'] ?? 0, 2) }}</div></div>
    <div class="kpi"><div class="t">Approved</div><div class="v">${{ number_format($summary['approved'] ?? 0, 2) }}</div></div>
    <div class="kpi"><div class="t">Paid</div><div class="v">${{ number_format($summary['paid'] ?? 0, 2) }}</div></div>
    <div class="kpi"><div class="t">Lifetime</div><div class="v">${{ number_format($summary['total_earned'] ?? 0, 2) }}</div></div>
</div>
<div class="card"><div class="card-h"><h2>Commission ledger</h2></div><div class="table-wrap"><table class="table">
    <thead><tr><th>ID</th><th>Status</th><th>Base</th><th>Commission</th><th>Approved</th><th>Paid</th></tr></thead>
    <tbody>@forelse($commissions as $c)<tr>
        <td class="mono">#{{ $c->id }}</td>
        <td><span class="badge b-info">{{ $c->status }}</span></td>
        <td>${{ number_format($c->base_amount ?? 0, 2) }}</td>
        <td><strong>${{ number_format($c->commission_amount ?? 0, 2) }}</strong></td>
        <td>{{ $c->approved_at ?? '—' }}</td>
        <td>{{ $c->paid_at ?? '—' }}</td>
    </tr>@empty<tr><td colspan="6" class="sub">No commissions recorded yet.</td></tr>@endforelse</tbody>
</table></div>{{ $commissions->links() }}</div>
@endsection
