@extends('admin.layout')
@section('title', 'Education Courses')
@section('crumb', 'Admin / Education / Courses')

@section('content')
<div class="page-head">
    <div>
        <h2>Education Courses</h2>
        <p>{{ $courses->total() }} courses</p>
    </div>
</div>

<div class="card">
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th>Course</th><th>Modules</th><th>Status</th><th>Created</th></tr></thead>
            <tbody>
                @forelse($courses as $course)
                <tr>
                    <td><a href="/admin/lms" style="font-weight:600;color:var(--primary)">{{ $course->title ?? 'Untitled Course' }}</a></td>
                    <td class="num">{{ $course->modules_count ?? 0 }}</td>
                    <td><span class="badge {{ ($course->status ?? 'draft') === 'published' ? 'b-ok':'b-muted' }}">{{ $course->status ?? 'draft' }}</span></td>
                    <td>{{ $course->created_at->format('M d, Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="empty"><p>No courses yet. Create courses via the LMS module.</p></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px">{{ $courses->links() }}</div>
@endsection
