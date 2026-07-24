@extends('admin.layout')
@section('title', $integrator ? 'Edit Integrator' : 'Add Integrator')
@section('crumb', 'AI & Robotics / Integrators / ' . ($integrator ? 'Edit' : 'New'))
@section('content')
<div class="page-head"><div><h2>{{ $integrator ? 'Edit' : 'Add' }} Integrator</h2></div><div class="page-actions"><a href="/admin/ai-robotics/integrators" class="btn btn-ghost">Back</a></div></div>
@if($errors->any())<div class="note" style="background:#fee2e2;border-color:#fca5a5;color:#991b1b">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ $integrator ? '/admin/ai-robotics/integrators/'.$integrator->id : '/admin/ai-robotics/integrators' }}">
    @csrf @if($integrator)@method('PUT')@endif
    <div class="card"><div class="card-body">
        <div class="form-grid">
            <div class="field"><label>Name *</label><input class="control" name="name" value="{{ old('name', $integrator->name ?? '') }}" required></div>
            <div class="field"><label>Slug *</label><input class="control" name="slug" value="{{ old('slug', $integrator->slug ?? '') }}" required></div>
            <div class="field"><label>Country</label><input class="control" name="country" value="{{ old('country', $integrator->country ?? '') }}"></div>
            <div class="field"><label>Website</label><input class="control" name="website_url" value="{{ old('website_url', $integrator->website_url ?? '') }}"></div>
        </div>
        <div class="field"><label>Description</label><textarea class="control" name="description" rows="3">{{ old('description', $integrator->description ?? '') }}</textarea></div>
        <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $integrator->is_active ?? 1) ? 'checked' : '' }}> Active</label></div>
    </div></div>
    <div style="margin-top:16px;display:flex;gap:8px"><button type="submit" class="btn btn-primary">{{ $integrator ? 'Update' : 'Create' }}</button><a href="/admin/ai-robotics/integrators" class="btn btn-ghost">Cancel</a></div>
</form>
@endsection
