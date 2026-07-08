@extends('admin.layout')
@section('title','LMS')
@section('crumb','Courses, projects, lessons and learner progress')
@section('content')
<div class="note"><strong>Adaptation status:</strong> LMS is now backed by additive NeoGiga tables and public API endpoints. Reference code was not copied.</div>

<div class="grid kpis">
    <div class="kpi"><div class="t">Courses</div><div class="v tnum">{{ number_format($stats['courses']) }}</div><div class="s">{{ number_format($stats['publishedCourses']) }} published</div></div>
    <div class="kpi"><div class="t">Projects</div><div class="v tnum">{{ number_format($stats['projects']) }}</div><div class="s">project tutorials</div></div>
    <div class="kpi"><div class="t">Lessons</div><div class="v tnum">{{ number_format($stats['lessons']) }}</div><div class="s">learning units</div></div>
    <div class="kpi"><div class="t">Enrollments</div><div class="v tnum">{{ number_format($stats['enrollments']) }}</div><div class="s">learner records</div></div>
    <div class="kpi"><div class="t">Certificates</div><div class="v tnum">{{ number_format($stats['certificates']) }}</div><div class="s">issued</div></div>
</div>

<div class="grid split">
    <div class="card"><div class="card-h"><h2>Recent Courses</h2><span class="sub">API: /api/v1/lms/courses</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Title</th><th>Level</th><th>Status</th></tr></thead><tbody>@forelse($courses as $c)<tr><td><strong>{{ $c->title ?? 'Untitled course' }}</strong><div class="sub mono">{{ $c->slug ?? '—' }}</div></td><td>{{ $c->level ?? '—' }}</td><td><span class="badge {{ ($c->status ?? '') === 'published' ? 'b-ok':'b-muted' }}">{{ $c->status ?? 'draft' }}</span></td></tr>@empty<tr><td colspan="3"><div class="empty"><h3>No courses yet</h3></div></td></tr>@endforelse</tbody></table></div></div>
    <div class="card"><div class="card-h"><h2>Recent Projects</h2><span class="sub">API: /api/v1/lms/projects</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Title</th><th>Difficulty</th><th>Status</th></tr></thead><tbody>@forelse($projects as $p)<tr><td><strong>{{ $p->title ?? 'Untitled project' }}</strong><div class="sub mono">{{ $p->slug ?? '—' }}</div></td><td>{{ $p->difficulty_level ?? '—' }}</td><td><span class="badge {{ ($p->status ?? '') === 'published' ? 'b-ok':'b-muted' }}">{{ $p->status ?? 'draft' }}</span></td></tr>@empty<tr><td colspan="3"><div class="empty"><h3>No projects yet</h3></div></td></tr>@endforelse</tbody></table></div></div>
</div>

<div class="card stack-gap"><div class="card-h"><h2>Recent Enrollments</h2><span class="sub">Progress and certificates</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Learner</th><th>Course</th><th>Progress</th><th>Status</th></tr></thead><tbody>@forelse($enrollments as $e)<tr><td class="mono">{{ $e->email ?? ('user#'.$e->user_id) }}</td><td>#{{ $e->lms_course_id }}</td><td class="tnum">{{ number_format((float) $e->progress_percent, 2) }}%</td><td><span class="badge b-muted">{{ $e->status }}</span></td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No enrollments yet</h3></div></td></tr>@endforelse</tbody></table></div></div>
@endsection
