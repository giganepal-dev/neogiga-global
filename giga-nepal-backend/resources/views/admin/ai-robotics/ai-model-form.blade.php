@extends('admin.layout')
@section('title', $model ? 'Edit AI Model' : 'Add AI Model')
@section('crumb', 'AI & Robotics / AI Models / ' . ($model ? 'Edit' : 'New'))
@section('content')
<div class="page-head">
    <div><h2>{{ $model ? 'Edit AI Model' : 'Add AI Model' }}</h2></div>
    <div class="page-actions"><a href="/admin/ai-robotics/ai-models" class="btn btn-ghost">Back</a></div>
</div>
@if($errors->any())<div class="note" style="background:#fee2e2;border-color:#fca5a5;color:#991b1b">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ $model ? '/admin/ai-robotics/ai-models/'.$model->id : '/admin/ai-robotics/ai-models' }}">
    @csrf @if($model)@method('PUT')@endif
    <div class="card">
        <div class="card-body">
            <div class="form-grid">
                <div class="field"><label>Name *</label><input class="control" name="name" value="{{ old('name', $model->name ?? '') }}" required></div>
                <div class="field"><label>Slug *</label><input class="control" name="slug" value="{{ old('slug', $model->slug ?? '') }}" required></div>
                <div class="field"><label>Provider</label><input class="control" name="provider" value="{{ old('provider', $model->provider ?? '') }}"></div>
                <div class="field"><label>Type</label><select class="control" name="model_type"><option value="">—</option>@foreach(['vision','nlp','speech','generative','reinforcement'] as $t)<option value="{{ $t }}" {{ old('model_type', $model->model_type ?? '') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>@endforeach</select></div>
                <div class="field"><label>License Type</label><select class="control" name="license_type"><option value="">—</option><option value="open_source" {{ old('license_type', $model->license_type ?? '') === 'open_source' ? 'selected' : '' }}>Open Source</option><option value="commercial" {{ old('license_type', $model->license_type ?? '') === 'commercial' ? 'selected' : '' }}>Commercial</option><option value="proprietary" {{ old('license_type', $model->license_type ?? '') === 'proprietary' ? 'selected' : '' }}>Proprietary</option></select></div>
                <div class="field"><label>License Name</label><input class="control" name="license_name" value="{{ old('license_name', $model->license_name ?? '') }}"></div>
            </div>
            <div class="field"><label>Description</label><textarea class="control" name="description" rows="4">{{ old('description', $model->description ?? '') }}</textarea></div>
            <div class="form-grid" style="margin-top:12px">
                <div class="field"><label>Documentation URL</label><input class="control" name="documentation_url" value="{{ old('documentation_url', $model->documentation_url ?? '') }}"></div>
                <div class="field"><label>Download URL</label><input class="control" name="download_url" value="{{ old('download_url', $model->download_url ?? '') }}"></div>
                <div class="field"><label>GitHub URL</label><input class="control" name="github_url" value="{{ old('github_url', $model->github_url ?? '') }}"></div>
            </div>
            <div class="form-grid" style="margin-top:12px">
                <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="edge_compatible" value="1" {{ old('edge_compatible', $model->edge_compatible ?? 0) ? 'checked' : '' }}> Edge Compatible</label></div>
                <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="cloud_compatible" value="1" {{ old('cloud_compatible', $model->cloud_compatible ?? 0) ? 'checked' : '' }}> Cloud Compatible</label></div>
                <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $model->is_active ?? 1) ? 'checked' : '' }}> Active</label></div>
                <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $model->is_featured ?? 0) ? 'checked' : '' }}> Featured</label></div>
            </div>
        </div>
    </div>
    <div style="margin-top:16px;display:flex;gap:8px">
        <button type="submit" class="btn btn-primary">{{ $model ? 'Update' : 'Create' }} AI Model</button>
        <a href="/admin/ai-robotics/ai-models" class="btn btn-ghost">Cancel</a>
    </div>
</form>
@endsection
