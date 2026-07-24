@extends('admin.layout')
@section('title', 'Robot Types')
@section('crumb', 'AI & Robotics / Robot Types')
@section('content')
<div class="page-head"><div><h2>Robot Types</h2></div></div>
@if(session('status'))<div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>@endif
<div class="card">
    <div class="card-h"><h2>Types ({{ $types->count() }})</h2></div>
    <div class="scroll-x">
        <table class="tbl"><thead><tr><th>Name</th><th>Slug</th><th>Models</th><th>Status</th></tr></thead>
        <tbody>@forelse($types as $t)<tr><td style="font-weight:600">{{ $t->name }}</td><td class="mono sub">{{ $t->slug }}</td><td class="num">{{ $t->robot_models_count }}</td><td><span class="badge {{ $t->is_active ? 'b-ok' : 'b-muted' }}">{{ $t->is_active ? 'Active' : 'Inactive' }}</span></td></tr>@empty<tr><td colspan="4" class="empty">No robot types yet.</td></tr>@endforelse</tbody></table>
    </div>
</div>
<div class="card" style="margin-top:16px">
    <div class="card-h"><h2>Add Robot Type</h2></div>
    <div class="card-body">
        <form method="POST" action="/admin/ai-robotics/robot-types" style="display:flex;gap:8px;align-items:end">
            @csrf
            <div class="field" style="flex:1"><label>Name</label><input class="control" name="name" required></div>
            <div class="field" style="flex:1"><label>Slug</label><input class="control" name="slug" required></div>
            <button type="submit" class="btn btn-primary" style="height:38px">Add</button>
        </form>
    </div>
</div>
@endsection
