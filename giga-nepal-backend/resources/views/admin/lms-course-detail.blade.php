@extends('admin.layout')
@section('title','LMS Course')
@section('crumb','Learning / Course Detail')

@section('page_actions')
<a class="btn btn-ghost" href="/admin/lms">Back to LMS</a>
<form method="post" action="/admin/lms/courses/{{ $course->id }}/toggle">@csrf<button class="btn btn-ghost" type="submit">{{ $course->status==='published' ? 'Unpublish' : 'Publish' }}</button></form>
@endsection

@section('content')
<div class="grid kpis">
    <div class="kpi"><div class="t">Course</div><div class="v">{{ $course->title }}</div><div class="s mono">{{ $course->slug }}</div></div>
    <div class="kpi"><div class="t">Modules</div><div class="v tnum">{{ number_format($modules->count()) }}</div><div class="s">course sections</div></div>
    <div class="kpi"><div class="t">Lessons</div><div class="v tnum">{{ number_format($lessons->count()) }}</div><div class="s">tutorials</div></div>
    <div class="kpi"><div class="t">Enrollments</div><div class="v tnum">{{ number_format($enrollments->count()) }}</div><div class="s">recent rows</div></div>
</div>

<section class="card">
    <div class="card-h"><h2>Edit Course</h2><span class="badge b-info">{{ $course->status }}</span></div>
    <form method="post" action="/admin/lms/courses" class="form-stack" style="padding:16px">
        @csrf
        <input type="hidden" name="id" value="{{ $course->id }}">
        <div class="form-grid"><div class="field"><label>Title</label><input class="control" name="title" value="{{ $course->title }}" required></div><div class="field"><label>Slug</label><input class="control mono" name="slug" value="{{ $course->slug }}"></div><div class="field"><label>Status</label><select class="control" name="status"><option @selected($course->status==='draft')>draft</option><option @selected($course->status==='published')>published</option></select></div><div class="field"><label>Level</label><input class="control" name="level" value="{{ $course->level }}"></div><div class="field"><label>Language</label><input class="control" name="language" value="{{ $course->language }}"></div><div class="field"><label>Minutes</label><input class="control" type="number" name="estimated_minutes" value="{{ $course->estimated_minutes }}"></div></div>
        <div class="field"><label>Subtitle</label><input class="control" name="subtitle" value="{{ $course->subtitle }}"></div>
        <div class="field"><label>Description</label><textarea class="control" name="description">{{ $course->description }}</textarea></div>
        <div class="form-grid"><div class="field"><label>SEO title</label><input class="control" name="seo_title" value="{{ $course->seo_title }}"></div><div class="field"><label>SEO description</label><input class="control" name="seo_description" value="{{ $course->seo_description }}"></div></div>
        <div class="sub">AI Tutor placeholder remains advisory only until a tutor engine is connected.</div>
        <button class="btn btn-primary" type="submit">Save Course</button>
    </form>
</section>

<div class="grid split stack-gap">
    <section class="card">
        <div class="card-h"><h2>Modules</h2><span class="badge b-info">sections</span></div>
        <div style="padding:16px">
            @forelse($modules as $m)<div style="padding:8px 0;border-bottom:1px solid var(--line)"><strong>{{ $m->title }}</strong><div class="sub">{{ $m->summary }} · {{ $m->status }}</div></div>@empty<div class="empty"><h3>No modules</h3></div>@endforelse
            <form method="post" action="/admin/lms/courses/{{ $course->id }}/modules" class="form-stack" style="margin-top:12px">@csrf<div class="form-grid"><div class="field"><label>Title</label><input class="control" name="title" required></div><div class="field"><label>Sort</label><input class="control" type="number" name="sort_order" value="100"></div><div class="field"><label>Status</label><select class="control" name="status"><option>draft</option><option>published</option></select></div></div><div class="field"><label>Summary</label><textarea class="control" name="summary"></textarea></div><button class="btn" type="submit">Add Module</button></form>
        </div>
    </section>

    <section class="card">
        <div class="card-h"><h2>Projects</h2><span class="badge b-info">labs</span></div>
        <div style="padding:16px">
            @forelse($projects as $p)<div style="padding:8px 0;border-bottom:1px solid var(--line)"><strong>{{ $p->title }}</strong><div class="sub">{{ $p->difficulty_level }} · {{ $p->status }} · {{ $p->summary }}</div></div>@empty<div class="empty"><h3>No projects</h3></div>@endforelse
            <form method="post" action="/admin/lms/courses/{{ $course->id }}/projects" class="form-stack" style="margin-top:12px">@csrf<div class="form-grid"><div class="field"><label>Title</label><input class="control" name="title" required></div><div class="field"><label>Difficulty</label><select class="control" name="difficulty_level"><option>beginner</option><option>intermediate</option><option>advanced</option></select></div><div class="field"><label>Status</label><select class="control" name="status"><option>draft</option><option>published</option></select></div><div class="field"><label>Minutes</label><input class="control" type="number" name="estimated_minutes"></div></div><div class="field"><label>Summary</label><textarea class="control" name="summary"></textarea></div><div class="field"><label>Description</label><textarea class="control" name="description"></textarea></div><button class="btn" type="submit">Add Project</button></form>
        </div>
    </section>
</div>

<section class="card stack-gap">
    <div class="card-h"><h2>Lessons & Files</h2><span class="badge b-info">resources</span></div>
    <div class="scroll-x"><table class="tbl"><thead><tr><th>Lesson</th><th>Module</th><th>Type</th><th>Status</th><th>Attach file</th></tr></thead><tbody>@forelse($lessons as $l)<tr><td><strong>{{ $l->title }}</strong><div class="sub">{{ $l->summary }}</div></td><td>{{ $l->module_title ?: '—' }}</td><td>{{ $l->type }}</td><td><span class="badge b-muted">{{ $l->status }}</span></td><td><details class="modal"><summary class="btn btn-ghost">File</summary><div class="modal-panel"><div class="modal-h"><h3>Attach Lesson File</h3></div><form class="modal-b form-stack" method="post" enctype="multipart/form-data" action="/admin/lms/courses/{{ $course->id }}/lessons/{{ $l->id }}/files">@csrf<div class="form-grid"><div class="field"><label>Title</label><input class="control" name="title" required></div><div class="field"><label>Type</label><select class="control" name="file_type"><option>resource</option><option>video</option><option>datasheet</option><option>code</option><option>lab_file</option></select></div><div class="field"><label>Media asset</label><select class="control" name="admin_media_asset_id"><option value="">None</option>@foreach($mediaAssets as $asset)<option value="{{ $asset->id }}">{{ $asset->title ?: $asset->original_name }}</option>@endforeach</select></div><input class="control" type="file" name="file"><input class="control" name="file_url" placeholder="File URL"></div><label><input type="checkbox" name="is_downloadable" value="1" checked> Downloadable</label><button class="btn btn-primary" type="submit">Attach File</button></form></div></details></td></tr>@empty<tr><td colspan="5"><div class="empty"><h3>No lessons</h3></div></td></tr>@endforelse</tbody></table></div>
    <div style="padding:0 16px 16px"><form method="post" action="/admin/lms/lessons" class="form-stack">@csrf<input type="hidden" name="lms_course_id" value="{{ $course->id }}"><div class="form-grid"><div class="field"><label>Module</label><select class="control" name="lms_module_id"><option value="">None</option>@foreach($modules as $m)<option value="{{ $m->id }}">{{ $m->title }}</option>@endforeach</select></div><div class="field"><label>Title</label><input class="control" name="title" required></div><div class="field"><label>Status</label><select class="control" name="status"><option>draft</option><option>published</option></select></div><div class="field"><label>Duration</label><input class="control" type="number" name="duration_minutes"></div></div><input class="control" name="video_url" placeholder="Video URL"><textarea class="control" name="summary" placeholder="Summary"></textarea><textarea class="control" name="content" placeholder="Content"></textarea><button class="btn" type="submit">Add Lesson</button></form></div>
</section>

<div class="grid split stack-gap">
    <section class="card"><div class="card-h"><h2>Product / Lab Kit Links</h2><span class="badge b-info">commerce bridge</span></div><div style="padding:16px">@forelse($productLinks as $link)<div style="padding:8px 0;border-bottom:1px solid var(--line)"><strong>{{ $link->title ?: $link->product_name }}</strong><div class="sub">{{ $link->link_type ?: $link->relation_type }} · {{ $link->product_sku }} · {{ $link->is_required ? 'required' : 'optional' }}</div></div>@empty<div class="empty"><h3>No product links</h3></div>@endforelse<form method="post" action="/admin/lms/courses/{{ $course->id }}/products" class="form-stack" style="margin-top:12px">@csrf<div class="form-grid"><div class="field"><label>Product</label><select class="control" name="product_id" required>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} · {{ $p->sku }}</option>@endforeach</select></div><div class="field"><label>Project</label><select class="control" name="lms_project_id"><option value="">Course-level</option>@foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach</select></div><div class="field"><label>Lesson</label><select class="control" name="lms_lesson_id"><option value="">None</option>@foreach($lessons as $l)<option value="{{ $l->id }}">{{ $l->title }}</option>@endforeach</select></div><div class="field"><label>Type</label><select class="control" name="link_type"><option>lab_kit</option><option>component</option><option>related</option><option>required_part</option></select></div></div><input class="control" name="title" placeholder="Display title"><textarea class="control" name="notes" placeholder="Notes"></textarea><label><input type="checkbox" name="is_required" value="1"> Required</label><button class="btn" type="submit">Link Product</button></form></div></section>
    <section class="card"><div class="card-h"><h2>Lesson Files</h2><span class="badge b-info">{{ number_format($lessonFiles->count()) }}</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>File</th><th>Lesson</th><th>Type</th><th>URL</th></tr></thead><tbody>@forelse($lessonFiles as $file)<tr><td>{{ $file->title }}</td><td>{{ $file->lesson_title }}</td><td>{{ $file->file_type }}</td><td class="mono">{{ $file->file_url }}</td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No lesson files</h3></div></td></tr>@endforelse</tbody></table></div></section>
</div>

<section class="card stack-gap"><div class="card-h"><h2>Enrollments & Certificates</h2><span class="badge b-info">issue / revoke</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Learner</th><th>Progress</th><th>Status</th><th>Action</th></tr></thead><tbody>@forelse($enrollments as $e)<tr><td>{{ $e->email ?: ('user#'.$e->user_id) }}</td><td class="tnum">{{ number_format((float)$e->progress_percent,2) }}%</td><td><span class="badge b-muted">{{ $e->status }}</span></td><td>@if((float)$e->progress_percent >= 100)<form method="post" action="/admin/lms/enrollments/{{ $e->id }}/certificate">@csrf<button class="btn btn-ghost" type="submit">Issue Certificate</button></form>@endif</td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No enrollments</h3></div></td></tr>@endforelse</tbody></table></div></section>

<section class="card stack-gap"><div class="card-h"><h2>Certificates</h2><span class="badge b-info">{{ number_format($certificates->count()) }}</span></div><div class="scroll-x"><table class="tbl"><thead><tr><th>Number</th><th>Email</th><th>Status</th><th>Action</th></tr></thead><tbody>@forelse($certificates as $cert)<tr><td class="mono">{{ $cert->certificate_number }}</td><td>{{ $cert->email }}</td><td><span class="badge {{ $cert->status==='issued'?'b-ok':'b-muted' }}">{{ $cert->status }}</span></td><td>@if($cert->status==='issued')<form method="post" action="/admin/lms/certificates/{{ $cert->id }}/revoke" onsubmit="return confirm('Revoke this certificate?')">@csrf<button class="btn btn-ghost danger" type="submit">Revoke</button></form>@endif</td></tr>@empty<tr><td colspan="4"><div class="empty"><h3>No certificates</h3></div></td></tr>@endforelse</tbody></table></div></section>
@endsection
