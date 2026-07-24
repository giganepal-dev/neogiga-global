@extends('admin.layout')
@section('title', 'Robot Models')
@section('crumb', 'AI & Robotics / Robot Models')
@section('content')
<div class="page-head">
    <div><h2>Robot Models</h2></div>
    <div class="page-actions"><a href="/admin/ai-robotics/robot-models/create" class="btn btn-primary">Add Robot Model</a></div>
</div>
@if(session('status'))<div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>@endif
<div class="card">
    <div class="card-h">
        <h2>Models ({{ $models->total() }})</h2>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <input class="control" name="search" value="{{ request('search') }}" placeholder="Search..." style="width:200px">
            <select class="control" name="type" style="width:140px"><option value="">All Types</option>@foreach($types as $t)<option value="{{ $t->id }}" {{ request('type')==$t->id ? 'selected' : '' }}>{{ $t->name }}</option>@endforeach</select>
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th>Name</th><th>Model #</th><th>Manufacturer</th><th>Type</th><th>ROS 2</th><th>Price</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @forelse($models as $m)
                <tr>
                    <td style="font-weight:600">{{ $m->name }}</td>
                    <td class="mono sub">{{ $m->model_number ?? '—' }}</td>
                    <td>{{ $m->manufacturer?->name ?? '—' }}</td>
                    <td>{{ $m->type?->name ?? '—' }}</td>
                    <td>{{ $m->ros2_support ? '✅' : '—' }}</td>
                    <td class="num">{{ $m->global_price ? $m->currency.' '.number_format($m->global_price,2) : '—' }}</td>
                    <td><span class="badge {{ $m->is_active ? 'b-ok' : 'b-muted' }}">{{ $m->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td><a href="/admin/ai-robotics/robot-models/{{ $m->id }}/edit" class="btn btn-ghost btn-sm">Edit</a></td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty">No robot models yet. <a href="/admin/ai-robotics/robot-models/create">Add one</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $models->withQueryString()->links() }}
</div>
@endsection
