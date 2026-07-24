@extends('admin.layout')
@section('title', $manufacturer ? 'Edit Manufacturer' : 'Add Manufacturer')
@section('crumb', 'AI & Robotics / Manufacturers / ' . ($manufacturer ? 'Edit' : 'New'))
@section('content')
<div class="page-head"><div><h2>{{ $manufacturer ? 'Edit' : 'Add' }} Manufacturer</h2></div><div class="page-actions"><a href="/admin/ai-robotics/manufacturers" class="btn btn-ghost">Back</a></div></div>
@if($errors->any())<div class="note" style="background:#fee2e2;border-color:#fca5a5;color:#991b1b">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ $manufacturer ? '/admin/ai-robotics/manufacturers/'.$manufacturer->id : '/admin/ai-robotics/manufacturers' }}">
    @csrf @if($manufacturer)@method('PUT')@endif
    <div class="card"><div class="card-body">
        <div class="form-grid">
            <div class="field"><label>Name *</label><input class="control" name="name" value="{{ old('name', $manufacturer->name ?? '') }}" required></div>
            <div class="field"><label>Slug *</label><input class="control" name="slug" value="{{ old('slug', $manufacturer->slug ?? '') }}" required></div>
            <div class="field"><label>Country</label><input class="control" name="country" value="{{ old('country', $manufacturer->country ?? '') }}"></div>
            <div class="field"><label>Website</label><input class="control" name="website_url" value="{{ old('website_url', $manufacturer->website_url ?? '') }}"></div>
        </div>
        <div class="field"><label>Description</label><textarea class="control" name="description" rows="3">{{ old('description', $manufacturer->description ?? '') }}</textarea></div>
        <div class="form-grid" style="margin-top:12px">
            <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="is_robot_manufacturer" value="1" {{ old('is_robot_manufacturer', $manufacturer->is_robot_manufacturer ?? 0) ? 'checked' : '' }}> Robot Manufacturer</label></div>
            <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="is_ai_hardware_manufacturer" value="1" {{ old('is_ai_hardware_manufacturer', $manufacturer->is_ai_hardware_manufacturer ?? 0) ? 'checked' : '' }}> AI Hardware</label></div>
            <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="is_software_provider" value="1" {{ old('is_software_provider', $manufacturer->is_software_provider ?? 0) ? 'checked' : '' }}> Software Provider</label></div>
            <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $manufacturer->is_active ?? 1) ? 'checked' : '' }}> Active</label></div>
        </div>
    </div></div>
    <div style="margin-top:16px;display:flex;gap:8px"><button type="submit" class="btn btn-primary">{{ $manufacturer ? 'Update' : 'Create' }}</button><a href="/admin/ai-robotics/manufacturers" class="btn btn-ghost">Cancel</a></div>
</form>
@endsection
