@extends('admin.layout')
@section('title', $path ? 'Edit Learning Path' : 'Add Learning Path')
@section('crumb', 'AI & Robotics / Learning Paths / ' . ($path ? 'Edit' : 'New'))
@section('content')
<div class="page-head"><div><h2>{{ $path ? 'Edit' : 'Add' }} Learning Path</h2></div><div class="page-actions"><a href="/admin/ai-robotics/learning-paths" class="btn btn-ghost">Back</a></div></div>
@if($errors->any())<div class="note" style="background:#fee2e2;border-color:#fca5a5;color:#991b1b">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ $path ? '/admin/ai-robotics/learning-paths/'.$path->id : '/admin/ai-robotics/learning-paths' }}">
    @csrf @if($path)@method('PUT')@endif
    <div class="card"><div class="card-body">
        <div class="form-grid">
            <div class="field"><label>Name *</label><input class="control" name="name" value="{{ old('name', $path->name ?? '') }}" required></div>
            <div class="field"><label>Slug *</label><input class="control" name="slug" value="{{ old('slug', $path->slug ?? '') }}" required></div>
            <div class="field"><label>Level *</label><select class="control" name="level"><option value="beginner" {{ old('level', $path->level ?? '') === 'beginner' ? 'selected' : '' }}>Beginner</option><option value="intermediate" {{ old('level', $path->level ?? '') === 'intermediate' ? 'selected' : '' }}>Intermediate</option><option value="advanced" {{ old('level', $path->level ?? '') === 'advanced' ? 'selected' : '' }}>Advanced</option></select></div>
            <div class="field"><label>Estimated Hours</label><input class="control" type="number" name="estimated_hours" value="{{ old('estimated_hours', $path->estimated_hours ?? '') }}"></div>
        </div>
        <div class="field"><label>Description</label><textarea class="control" name="description" rows="3">{{ old('description', $path->description ?? '') }}</textarea></div>
        <div class="form-grid" style="margin-top:12px">
            <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $path->is_active ?? 1) ? 'checked' : '' }}> Active</label></div>
            <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $path->is_featured ?? 0) ? 'checked' : '' }}> Featured</label></div>
        </div>
        @if($courses->count())
        <div class="field" style="margin-top:16px"><label>Courses</label>
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                @php $selected = old('courses', $path->courses->pluck('id')->toArray() ?? []); @endphp
                @foreach($courses as $c)<label style="display:flex;align-items:center;gap:6px;padding:6px 12px;background:var(--bg);border-radius:6px;font-size:.85rem"><input type="checkbox" name="courses[]" value="{{ $c->id }}" {{ in_array($c->id, $selected) ? 'checked' : '' }}> {{ $c->title ?? $c->name }}</label>@endforeach
            </div>
        </div>
        @endif
    </div></div>
    <div style="margin-top:16px;display:flex;gap:8px"><button type="submit" class="btn btn-primary">{{ $path ? 'Update' : 'Create' }}</button><a href="/admin/ai-robotics/learning-paths" class="btn btn-ghost">Cancel</a></div>
</form>
@endsection
