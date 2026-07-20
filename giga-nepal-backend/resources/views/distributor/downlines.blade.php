@extends('distributor.layout')
@section('title','Downlines')
@section('content')
<div class="page-intro"><h1>Downline Network</h1><p>Sub-distributors and resellers under your hierarchy.</p></div>
<div class="kpi-grid">
    <div class="kpi"><div class="t">Total</div><div class="v">{{ number_format($stats['total'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Active</div><div class="v">{{ number_format($stats['active'] ?? 0) }}</div></div>
    <div class="kpi"><div class="t">Pending</div><div class="v">{{ number_format($stats['pending'] ?? 0) }}</div></div>
</div>
<div class="card"><div class="card-h"><h2>Downline distributors</h2></div><div class="table-wrap"><table class="table">
    <thead><tr><th>Name</th><th>Email</th><th>Type</th><th>Status</th><th>Relationship</th><th>Since</th></tr></thead>
    <tbody>@forelse($downlines as $d)<tr>
        <td><strong>{{ $d->child_name }}</strong></td>
        <td>{{ $d->child_email ?? '—' }}</td>
        <td>{{ $d->child_type ?? '—' }}</td>
        <td><span class="badge b-info">{{ $d->child_status ?? 'pending' }}</span></td>
        <td>{{ $d->relationship_type ?? 'downline' }}</td>
        <td>{{ $d->created_at ?? '—' }}</td>
    </tr>@empty<tr><td colspan="6" class="sub">No downlines linked to your account.</td></tr>@endforelse</tbody>
</table></div></div>
@endsection
