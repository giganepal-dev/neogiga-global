@extends('admin.layout')
@section('title', 'AI & Robotics Admin')
@section('crumb', 'AI & Robotics')
@section('content')
<div class="page-head">
    <div><h2>AI & Robotics</h2><p style="color:var(--muted)">Manage the AI & Robotics ecosystem</p></div>
</div>
@if(session('status'))<div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>@endif

<div class="kpis">
    @foreach(['Robot Models'=>'robot_models','AI Models'=>'ai_models','Manufacturers'=>'manufacturers','Integrators'=>'integrators','Courses'=>'courses','Learning Paths'=>'learning_paths','Events'=>'events','Articles'=>'articles','Demo Requests'=>'demo_requests','Lab Bookings'=>'lab_bookings','Packages'=>'packages','Projects'=>'projects'] as $label=>$key)
    <div class="kpi"><div class="t">{{ $label }}</div><div class="v">{{ number_format($stats[$key] ?? 0) }}</div></div>
    @endforeach
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:20px">
    <div class="card">
        <div class="card-h"><h2>Quick Links</h2></div>
        <div class="card-body" style="display:grid;gap:8px">
            @foreach([
                ['/admin/ai-robotics/robot-models','Robot Models'],
                ['/admin/ai-robotics/robot-types','Robot Types'],
                ['/admin/ai-robotics/robot-applications','Robot Applications'],
                ['/admin/ai-robotics/ai-models','AI Models'],
                ['/admin/ai-robotics/manufacturers','Manufacturers'],
                ['/admin/ai-robotics/integrators','Integrators'],
                ['/admin/ai-robotics/learning-paths','Learning Paths'],
                ['/admin/ai-robotics/events','Events'],
                ['/admin/ai-robotics/articles','Articles & News'],
                ['/admin/ai-robotics/demo-requests','Demo Requests'],
                ['/admin/ai-robotics/lab-bookings','Lab Bookings'],
                ['/admin/ai-robotics/packages','Institutional Packages'],
                ['/admin/ai-robotics/projects','Projects'],
            ] as $url=>$label)
            <a href="{{ $url }}" style="display:block;padding:10px 12px;background:var(--bg);border-radius:6px;text-decoration:none;color:var(--fg);font-weight:500;font-size:.9rem">{{ $label }} →</a>
            @endforeach
        </div>
    </div>
    <div>
        @if($recentDemoRequests->count())
        <div class="card" style="margin-bottom:16px">
            <div class="card-h"><h2>Recent Demo Requests</h2></div>
            <div class="scroll-x">
                <table class="tbl"><thead><tr><th>Name</th><th>Robot</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>@foreach($recentDemoRequests as $d)<tr><td>{{ $d->contact_name }}</td><td>{{ $d->robotModel?->name ?? '—' }}</td><td><span class="badge b-muted">{{ $d->status }}</span></td><td style="font-size:.82rem">{{ $d->created_at->diffForHumans() }}</td></tr>@endforeach</tbody></table>
            </div>
        </div>
        @endif
        @if($recentLabBookings->count())
        <div class="card">
            <div class="card-h"><h2>Recent Lab Bookings</h2></div>
            <div class="scroll-x">
                <table class="tbl"><thead><tr><th>Name</th><th>Type</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>@foreach($recentLabBookings as $b)<tr><td>{{ $b->contact_name }}</td><td>{{ ucfirst($b->booking_type) }}</td><td><span class="badge b-muted">{{ $b->status }}</span></td><td style="font-size:.82rem">{{ $b->created_at->diffForHumans() }}</td></tr>@endforeach</tbody></table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
