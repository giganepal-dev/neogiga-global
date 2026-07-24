@extends('admin.layout')
@section('title', 'Lab Bookings')
@section('crumb', 'AI & Robotics / Lab Bookings')
@section('content')
<div class="page-head"><div><h2>Lab Bookings</h2></div></div>
@if(session('status'))<div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>@endif
<div class="card">
    <div class="card-h"><h2>Bookings ({{ $bookings->total() }})</h2>
        <form method="GET" style="display:flex;gap:8px"><select class="control" name="status" style="width:140px"><option value="">All Status</option>@foreach(['pending','confirmed','completed','cancelled'] as $s)<option value="{{ $s }}" {{ request('status')===$s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>@endforeach</select><button class="btn btn-ghost" type="submit">Filter</button></form>
    </div>
    <div class="scroll-x">
        <table class="tbl"><thead><tr><th>Contact</th><th>Email</th><th>Type</th><th>Date</th><th>Status</th><th></th></tr></thead>
        <tbody>@forelse($bookings as $b)<tr>
            <td style="font-weight:600">{{ $b->contact_name }}</td>
            <td>{{ $b->contact_email }}</td>
            <td><span class="badge b-muted">{{ ucfirst($b->booking_type) }}</span></td>
            <td style="white-space:nowrap">{{ $b->preferred_date->format('M d, Y') }} {{ $b->preferred_time }}</td>
            <td><span class="badge b-muted">{{ ucfirst($b->status) }}</span></td>
            <td>
                <form method="POST" action="/admin/ai-robotics/lab-bookings/{{ $b->id }}/status" style="display:inline">
                    @csrf
                    <select name="status" onchange="this.form.submit()" class="control" style="width:120px;height:30px;font-size:.8rem">
                        @foreach(['pending','confirmed','completed','cancelled'] as $s)<option value="{{ $s }}" {{ $b->status===$s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>@endforeach
                    </select>
                </form>
            </td>
        </tr>@empty<tr><td colspan="6" class="empty">No lab bookings.</td></tr>@endforelse</tbody></table>
    </div>
    {{ $bookings->links() }}
</div>
@endsection
