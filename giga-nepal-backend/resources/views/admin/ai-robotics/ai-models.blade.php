@extends('admin.layout')
@section('title', 'AI Models')
@section('crumb', 'AI & Robotics / AI Models')
@section('content')
<div class="page-head">
    <div><h2>AI Models</h2></div>
    <div class="page-actions"><a href="/admin/ai-robotics/ai-models/create" class="btn btn-primary">Add AI Model</a></div>
</div>
@if(session('status'))<div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>@endif
<div class="card">
    <div class="card-h">
        <h2>Models ({{ $models->total() }})</h2>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <input class="control" name="search" value="{{ request('search') }}" placeholder="Search..." style="width:200px">
            <select class="control" name="type" style="width:140px"><option value="">All Types</option>@foreach(['vision','nlp','speech','generative','reinforcement'] as $t)<option value="{{ $t }}" {{ request('type')===$t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>@endforeach</select>
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th>Name</th><th>Provider</th><th>Type</th><th>License</th><th>Edge</th><th>Cloud</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($models as $m)
                <tr>
                    <td style="font-weight:600">{{ $m->name }}</td>
                    <td>{{ $m->provider ?? '—' }}</td>
                    <td><span class="badge b-muted">{{ $m->model_type ?? '—' }}</span></td>
                    <td>{{ $m->license_type ?? '—' }}</td>
                    <td>{{ $m->edge_compatible ? '✅' : '—' }}</td>
                    <td>{{ $m->cloud_compatible ? '✅' : '—' }}</td>
                    <td><span class="badge {{ $m->is_active ? 'b-ok' : 'b-muted' }}">{{ $m->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td><a href="/admin/ai-robotics/ai-models/{{ $m->id }}/edit" class="btn btn-ghost btn-sm">Edit</a></td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty">No AI models yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $models->withQueryString()->links() }}
</div>
@endsection
