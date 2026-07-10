@extends('admin.layout')
@section('title','RFQs')
@section('crumb','Request-for-quote pipeline')
@section('content')

@php
    $badge = fn($s) => match($s) { 'accepted' => 'b-ok', 'open', 'quoted' => 'b-info', default => 'b-muted' };
    $statuses = ['open','quoted','accepted','closed','cancelled'];
@endphp

<div class="grid kpis">
    <div class="kpi"><div class="t">RFQs</div><div class="v tnum">{{ number_format($stats['total']) }}</div><div class="s">all time</div></div>
    <div class="kpi"><div class="t">Open</div><div class="v tnum">{{ number_format($stats['open']) }}</div><div class="s">awaiting quote</div></div>
    <div class="kpi"><div class="t">Quoted</div><div class="v tnum">{{ number_format($stats['quoted']) }}</div><div class="s">pending reply</div></div>
    <div class="kpi"><div class="t">Accepted</div><div class="v tnum">{{ number_format($stats['accepted']) }}</div><div class="s">won</div></div>
</div>

<div class="card">
    <div class="card-h">
        <h2>RFQ Requests</h2>
        <form method="get" action="/admin/rfqs" style="display:flex;gap:8px">
            <select class="control" name="status" style="min-height:34px">
                <option value="">All statuses</option>
                @foreach ($statuses as $s)<option value="{{ $s }}" @selected($statusFilter===$s)>{{ ucfirst($s) }}</option>@endforeach
            </select>
            <button class="btn" type="submit">Filter</button>
        </form>
    </div>
    <div class="scroll-x"><table class="tbl">
        <thead><tr><th>RFQ #</th><th>Contact</th><th>Company</th><th class="num">Items</th><th>Status</th><th>Received</th><th>Action</th></tr></thead>
        <tbody>
        @forelse ($rfqs as $r)
            <tr>
                <td class="mono"><strong>{{ $r->rfq_number }}</strong></td>
                <td>{{ $r->contact_name ?? '—' }}<div class="sub">{{ $r->contact_email ?? '' }}</div></td>
                <td>{{ $r->company_name ?? '—' }}</td>
                <td class="num tnum">{{ $r->items->count() }}</td>
                <td><span class="badge {{ $badge($r->status) }}">{{ $r->status }}</span></td>
                <td class="sub">{{ $r->created_at?->format('Y-m-d H:i') }}</td>
                <td><a class="btn btn-ghost" href="/admin/rfqs/{{ $r->id }}">View</a></td>
            </tr>
        @empty
            <tr><td colspan="7"><div class="empty"><h3>No RFQs yet</h3><p>Public submissions from /rfq and the sales API land here.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    @if ($rfqs->hasPages())
        <div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $rfqs->links() }}</div>
    @endif
</div>

@endsection
