@extends('admin.layout')
@section('title', 'Manufacturers')
@section('crumb', 'AI & Robotics / Manufacturers')
@section('content')
<div class="page-head"><div><h2>Manufacturers</h2></div><div class="page-actions"><a href="/admin/ai-robotics/manufacturers/create" class="btn btn-primary">Add Manufacturer</a></div></div>
@if(session('status'))<div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>@endif
<div class="card">
    <div class="card-h"><h2>Manufacturers ({{ $manufacturers->total() }})</h2>
        <form method="GET" style="display:flex;gap:8px"><input class="control" name="search" value="{{ request('search') }}" placeholder="Search..." style="width:200px"><button class="btn btn-ghost" type="submit">Filter</button></form>
    </div>
    <div class="scroll-x">
        <table class="tbl"><thead><tr><th>Name</th><th>Country</th><th>Robots</th><th>AI HW</th><th>Software</th><th>Status</th><th></th></tr></thead>
        <tbody>@forelse($manufacturers as $m)<tr><td style="font-weight:600">{{ $m->name }}</td><td>{{ $m->country ?? '—' }}</td><td>{{ $m->is_robot_manufacturer ? '✅' : '—' }}</td><td>{{ $m->is_ai_hardware_manufacturer ? '✅' : '—' }}</td><td>{{ $m->is_software_provider ? '✅' : '—' }}</td><td><span class="badge {{ $m->is_active ? 'b-ok' : 'b-muted' }}">{{ $m->is_active ? 'Active' : 'Inactive' }}</span></td><td><a href="/admin/ai-robotics/manufacturers/{{ $m->id }}/edit" class="btn btn-ghost btn-sm">Edit</a></td></tr>
        @empty<tr><td colspan="7" class="empty">No manufacturers yet.</td></tr>@endforelse</tbody></table>
    </div>
    {{ $manufacturers->withQueryString()->links() }}
</div>
@endsection
