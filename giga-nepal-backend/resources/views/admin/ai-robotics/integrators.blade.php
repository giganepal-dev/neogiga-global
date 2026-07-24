@extends('admin.layout')
@section('title', 'Integrators')
@section('crumb', 'AI & Robotics / Integrators')
@section('content')
<div class="page-head"><div><h2>Integrators</h2></div><div class="page-actions"><a href="/admin/ai-robotics/integrators/create" class="btn btn-primary">Add Integrator</a></div></div>
@if(session('status'))<div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>@endif
<div class="card">
    <div class="card-h"><h2>Integrators ({{ $integrators->total() }})</h2>
        <form method="GET" style="display:flex;gap:8px"><input class="control" name="search" value="{{ request('search') }}" placeholder="Search..." style="width:200px"><button class="btn btn-ghost" type="submit">Filter</button></form>
    </div>
    <div class="scroll-x">
        <table class="tbl"><thead><tr><th>Name</th><th>Country</th><th>Status</th><th></th></tr></thead>
        <tbody>@forelse($integrators as $i)<tr><td style="font-weight:600">{{ $i->name }}</td><td>{{ $i->country ?? '—' }}</td><td><span class="badge {{ $i->is_active ? 'b-ok' : 'b-muted' }}">{{ $i->is_active ? 'Active' : 'Inactive' }}</span></td><td><a href="/admin/ai-robotics/integrators/{{ $i->id }}/edit" class="btn btn-ghost btn-sm">Edit</a></td></tr>@empty<tr><td colspan="4" class="empty">No integrators yet.</td></tr>@endforelse</tbody></table>
    </div>
    {{ $integrators->withQueryString()->links() }}
</div>
@endsection
