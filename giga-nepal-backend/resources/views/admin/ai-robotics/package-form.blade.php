@extends('admin.layout')
@section('title', $package ? 'Edit Package' : 'Add Package')
@section('crumb', 'AI & Robotics / Packages / ' . ($package ? 'Edit' : 'New'))
@section('content')
<div class="page-head"><div><h2>{{ $package ? 'Edit' : 'Add' }} Institutional Package</h2></div><div class="page-actions"><a href="/admin/ai-robotics/packages" class="btn btn-ghost">Back</a></div></div>
@if($errors->any())<div class="note" style="background:#fee2e2;border-color:#fca5a5;color:#991b1b">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ $package ? '/admin/ai-robotics/packages/'.$package->id : '/admin/ai-robotics/packages' }}">
    @csrf @if($package)@method('PUT')@endif
    <div class="card"><div class="card-body">
        <div class="form-grid">
            <div class="field"><label>Name *</label><input class="control" name="name" value="{{ old('name', $package->name ?? '') }}" required></div>
            <div class="field"><label>Slug *</label><input class="control" name="slug" value="{{ old('slug', $package->slug ?? '') }}" required></div>
            <div class="field"><label>Target Institution</label><select class="control" name="target_institution"><option value="">—</option>@foreach(['school','university','college','institute','government','industrial','research','startup'] as $t)<option value="{{ $t }}" {{ old('target_institution', $package->target_institution ?? '') === $t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>@endforeach</select></div>
            <div class="field"><label>Base Price</label><input class="control" type="number" step="0.01" name="base_price" value="{{ old('base_price', $package->base_price ?? '') }}"></div>
        </div>
        <div class="field"><label>Description</label><textarea class="control" name="description" rows="3">{{ old('description', $package->description ?? '') }}</textarea></div>
        <div class="field"><label>Short Description</label><textarea class="control" name="short_description" rows="2">{{ old('short_description', $package->short_description ?? '') }}</textarea></div>
        <div class="form-grid" style="margin-top:12px">
            <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $package->is_active ?? 1) ? 'checked' : '' }}> Active</label></div>
            <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $package->is_featured ?? 0) ? 'checked' : '' }}> Featured</label></div>
        </div>
    </div></div>
    <div style="margin-top:16px;display:flex;gap:8px"><button type="submit" class="btn btn-primary">{{ $package ? 'Update' : 'Create' }}</button><a href="/admin/ai-robotics/packages" class="btn btn-ghost">Cancel</a></div>
</form>
@endsection
