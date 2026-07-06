@extends('admin.layout')
@section('title','Marketing Analytics')
@section('crumb','Events, top searches and trending placeholders')
@section('content')
<div class="card"><div class="card-h"><h2>Analytics Events</h2><span class="sub">Consent-aware endpoint foundation</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Event</th><th>Type</th><th>Occurred</th></tr></thead><tbody>@forelse($events as $e)<tr><td><strong>{{ $e->event_name }}</strong></td><td>{{ $e->event_type }}</td><td>{{ $e->occurred_at ?? $e->created_at }}</td></tr>@empty<tr><td colspan="3"><div class="empty"><h3>No events yet</h3></div></td></tr>@endforelse</tbody></table></div>@if($events->hasPages())<div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $events->links() }}</div>@endif</div>
<div class="card" style="margin-top:16px"><div class="card-h"><h2>Top Searches</h2></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Term</th><th class="num">Count</th></tr></thead><tbody>@forelse($topSearches as $s)<tr><td>{{ $s->term }}</td><td class="num tnum">{{ number_format($s->search_count) }}</td></tr>@empty<tr><td colspan="2"><div class="empty"><h3>No searches yet</h3></div></td></tr>@endforelse</tbody></table></div></div>
@endsection
