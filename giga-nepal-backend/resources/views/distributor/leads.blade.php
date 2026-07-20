@extends('distributor.layout')
@section('title','Leads')
@section('content')
<div class="page-intro"><h1>Sales Leads</h1><p>Prospects assigned to your distributor account.</p></div>
<div class="kpi-grid">
    <div class="kpi"><div class="t">Total leads</div><div class="v">{{ number_format($leadSummary['total'] ?? 0) }}</div></div>
</div>
<div class="card"><div class="card-h"><h2>Lead pipeline</h2></div><div class="table-wrap"><table class="table">
    <thead><tr><th>Name</th><th>Company</th><th>Email</th><th>Status</th><th>Est. value</th><th>Created</th></tr></thead>
    <tbody>@forelse($leads as $lead)<tr>
        <td><strong>{{ $lead->name ?? '—' }}</strong></td>
        <td>{{ $lead->company ?? '—' }}</td>
        <td>{{ $lead->email ?? '—' }}</td>
        <td><span class="badge b-info">{{ $lead->status ?? 'new' }}</span></td>
        <td>@if(isset($lead->estimated_value))${{ number_format($lead->estimated_value, 2) }}@else—@endif</td>
        <td>{{ $lead->created_at ?? '—' }}</td>
    </tr>@empty<tr><td colspan="6" class="sub">No leads yet. Use the API to register new prospects.</td></tr>@endforelse</tbody>
</table></div>{{ $leads->links() }}</div>
@endsection
