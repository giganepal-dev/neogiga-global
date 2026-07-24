@extends('admin.layout')
@section('title', $article ? 'Edit Article' : 'Add Article')
@section('crumb', 'AI & Robotics / Articles / ' . ($article ? 'Edit' : 'New'))
@section('content')
<div class="page-head"><div><h2>{{ $article ? 'Edit' : 'Add' }} Article</h2></div><div class="page-actions"><a href="/admin/ai-robotics/articles" class="btn btn-ghost">Back</a></div></div>
@if($errors->any())<div class="note" style="background:#fee2e2;border-color:#fca5a5;color:#991b1b">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ $article ? '/admin/ai-robotics/articles/'.$article->id : '/admin/ai-robotics/articles' }}">
    @csrf @if($article)@method('PUT')@endif
    <div class="card"><div class="card-body">
        <div class="form-grid">
            <div class="field"><label>Title *</label><input class="control" name="title" value="{{ old('title', $article->title ?? '') }}" required></div>
            <div class="field"><label>Slug *</label><input class="control" name="slug" value="{{ old('slug', $article->slug ?? '') }}" required></div>
            <div class="field"><label>Type *</label><select class="control" name="article_type" required><option value="">—</option>@foreach(['news','product_launch','research','case_study','press_release'] as $t)<option value="{{ $t }}" {{ old('article_type', $article->article_type ?? '') === $t ? 'selected' : '' }}>{{ str_replace('_',' ',ucfirst($t)) }}</option>@endforeach</select></div>
            <div class="field"><label>Status</label><select class="control" name="status"><option value="draft" {{ old('status', $article->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option><option value="published" {{ old('status', $article->status ?? '') === 'published' ? 'selected' : '' }}>Published</option></select></div>
        </div>
        <div class="field"><label>Excerpt</label><textarea class="control" name="excerpt" rows="2">{{ old('excerpt', $article->excerpt ?? '') }}</textarea></div>
        <div class="field"><label>Body</label><textarea class="control" name="body" rows="12">{{ old('body', $article->body ?? '') }}</textarea></div>
        <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $article->is_featured ?? 0) ? 'checked' : '' }}> Featured</label></div>
    </div></div>
    <div style="margin-top:16px;display:flex;gap:8px"><button type="submit" class="btn btn-primary">{{ $article ? 'Update' : 'Create' }}</button><a href="/admin/ai-robotics/articles" class="btn btn-ghost">Cancel</a></div>
</form>
@endsection
