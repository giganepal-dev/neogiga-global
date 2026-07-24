@extends('admin.layout')
@section('title', 'Learning Paths')
@section('crumb', 'AI & Robotics / Learning Paths')
@section('content')
<div class="page-head"><div><h2>Learning Paths</h2></div><div class="page-actions"><a href="/admin/ai-robotics/learning-paths/create" class="btn btn-primary">Add Learning Path</a></div></div>
@if(session('status'))<div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>@endif
<div class="card">
    <div class="card-h"><h2>Paths ({{ $paths->total() }})</h2></div>
    <div class="scroll-x">
        <table class="tbl"><thead><tr><th>Name</th><th>Level</th><th>Courses</th><th>Hours</th><th>Status</th><th></th></tr></thead>
        <tbody>@forelse($paths as $p)<tr><td style="font-weight:600">{{ $p->name }}</td><td><span class="badge b-muted">{{ ucfirst($p->level) }}</span></td><td class="num">{{ $p->courses_count }}</td><td class="num">{{ $p->estimated_hours ?? '—' }}</td><td><span class="badge {{ $p->is_active ? 'b-ok' : 'b-muted' }}">{{ $p->is_active ? 'Active' : 'Inactive' }}</span></td><td><a href="/admin/ai-robotics/learning-paths/{{ $p->id }}/edit" class="btn btn-ghost btn-sm">Edit</a></td></tr>@empty<tr><td colspan="6" class="empty">No learning paths yet.</td></tr>@endforelse</tbody></table>
    </div>
    {{ $paths->links() }}
</div>
@endsection
