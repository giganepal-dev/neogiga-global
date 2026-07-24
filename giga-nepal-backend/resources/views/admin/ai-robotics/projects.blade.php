@extends('admin.layout')
@section('title', 'Projects')
@section('crumb', 'AI & Robotics / Projects')
@section('content')
<div class="page-head"><div><h2>Projects</h2></div></div>
@if(session('status'))<div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>@endif
<div class="card">
    <div class="card-h"><h2>Projects ({{ $projects->total() }})</h2>
        <form method="GET" style="display:flex;gap:8px"><select class="control" name="status" style="width:120px"><option value="">All</option><option value="draft" {{ request('status')==='draft' ? 'selected' : '' }}>Draft</option><option value="published" {{ request('status')==='published' ? 'selected' : '' }}>Published</option><option value="archived" {{ request('status')==='archived' ? 'selected' : '' }}>Archived</option></select><button class="btn btn-ghost" type="submit">Filter</button></form>
    </div>
    <div class="scroll-x">
        <table class="tbl"><thead><tr><th>Name</th><th>Author</th><th>Difficulty</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>@forelse($projects as $p)<tr>
            <td style="font-weight:600">{{ $p->name }}</td>
            <td>{{ $p->user?->name ?? '—' }}</td>
            <td><span class="badge b-muted">{{ ucfirst($p->difficulty) }}</span></td>
            <td><span class="badge {{ $p->status==='published' ? 'b-ok' : 'b-muted' }}">{{ ucfirst($p->status) }}</span></td>
            <td style="font-size:.82rem">{{ $p->created_at->diffForHumans() }}</td>
        </tr>@empty<tr><td colspan="5" class="empty">No projects yet.</td></tr>@endforelse</tbody></table>
    </div>
    {{ $projects->links() }}
</div>
@endsection
