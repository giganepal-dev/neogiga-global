@extends('admin.layout')
@section('title', 'Events')
@section('crumb', 'AI & Robotics / Events')
@section('content')
<div class="page-head"><div><h2>Events</h2></div><div class="page-actions"><a href="/admin/ai-robotics/events/create" class="btn btn-primary">Add Event</a></div></div>
@if(session('status'))<div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>@endif
<div class="card">
    <div class="card-h"><h2>Events ({{ $events->total() }})</h2>
        <form method="GET" style="display:flex;gap:8px"><select class="control" name="type" style="width:140px"><option value="">All Types</option>@foreach(['webinar','workshop','competition','conference','demo','hackathon'] as $t)<option value="{{ $t }}" {{ request('type')===$t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>@endforeach</select><button class="btn btn-ghost" type="submit">Filter</button></form>
    </div>
    <div class="scroll-x">
        <table class="tbl"><thead><tr><th>Name</th><th>Type</th><th>Date</th><th>Location</th><th>Status</th><th></th></tr></thead>
        <tbody>@forelse($events as $e)<tr><td style="font-weight:600">{{ $e->name }}</td><td><span class="badge b-muted">{{ str_replace('_',' ',$e->event_type) }}</span></td><td style="white-space:nowrap">{{ $e->starts_at->format('M d, Y') }}</td><td>{{ $e->location ?? '—' }}</td><td><span class="badge {{ $e->is_active ? 'b-ok' : 'b-muted' }}">{{ $e->is_active ? 'Active' : 'Inactive' }}</span></td><td><a href="/admin/ai-robotics/events/{{ $e->id }}/edit" class="btn btn-ghost btn-sm">Edit</a></td></tr>@empty<tr><td colspan="6" class="empty">No events yet.</td></tr>@endforelse</tbody></table>
    </div>
    {{ $events->links() }}
</div>
@endsection
