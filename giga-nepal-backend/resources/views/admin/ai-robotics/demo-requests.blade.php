@extends('admin.layout')
@section('title', 'Demo Requests')
@section('crumb', 'AI & Robotics / Demo Requests')
@section('content')
<div class="page-head"><div><h2>Demo Requests</h2></div></div>
@if(session('status'))<div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>@endif
<div class="card">
    <div class="card-h"><h2>Requests ({{ $requests->total() }})</h2>
        <form method="GET" style="display:flex;gap:8px"><select class="control" name="status" style="width:140px"><option value="">All Status</option>@foreach(['pending','contacted','scheduled','completed','cancelled'] as $s)<option value="{{ $s }}" {{ request('status')===$s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>@endforeach</select><button class="btn btn-ghost" type="submit">Filter</button></form>
    </div>
    <div class="scroll-x">
        <table class="tbl"><thead><tr><th>Contact</th><th>Email</th><th>Robot</th><th>Institution</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>@forelse($requests as $r)<tr>
            <td style="font-weight:600">{{ $r->contact_name }}</td>
            <td>{{ $r->contact_email }}</td>
            <td>{{ $r->robotModel?->name ?? '—' }}</td>
            <td>{{ $r->institution_name ?? '—' }}</td>
            <td><span class="badge b-muted">{{ ucfirst($r->status) }}</span></td>
            <td style="font-size:.82rem">{{ $r->created_at->diffForHumans() }}</td>
            <td>
                <form method="POST" action="/admin/ai-robotics/demo-requests/{{ $r->id }}/status" style="display:inline">
                    @csrf
                    <select name="status" onchange="this.form.submit()" class="control" style="width:120px;height:30px;font-size:.8rem">
                        @foreach(['pending','contacted','scheduled','completed','cancelled'] as $s)<option value="{{ $s }}" {{ $r->status===$s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>@endforeach
                    </select>
                </form>
            </td>
        </tr>@empty<tr><td colspan="7" class="empty">No demo requests.</td></tr>@endforelse</tbody></table>
    </div>
    {{ $requests->links() }}
</div>
@endsection
