@extends('admin.layout')
@section('title', $model ? 'Edit Robot Model' : 'Add Robot Model')
@section('crumb', 'AI & Robotics / Robot Models / ' . ($model ? 'Edit' : 'New'))
@section('content')
<div class="page-head">
    <div><h2>{{ $model ? 'Edit Robot Model' : 'Add Robot Model' }}</h2></div>
    <div class="page-actions"><a href="/admin/ai-robotics/robot-models" class="btn btn-ghost">Back to List</a></div>
</div>
@if($errors->any())<div class="note" style="background:#fee2e2;border-color:#fca5a5;color:#991b1b">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ $model ? '/admin/ai-robotics/robot-models/'.$model->id : '/admin/ai-robotics/robot-models' }}">
    @csrf
    @if($model)@method('PUT')@endif
    <div class="card">
        <div class="card-h"><h2>Basic Information</h2></div>
        <div class="card-body">
            <div class="form-grid">
                <div class="field"><label>Name *</label><input class="control" name="name" value="{{ old('name', $model->name ?? '') }}" required></div>
                <div class="field"><label>Slug *</label><input class="control" name="slug" value="{{ old('slug', $model->slug ?? '') }}" required></div>
                <div class="field"><label>Model Number</label><input class="control" name="model_number" value="{{ old('model_number', $model->model_number ?? '') }}"></div>
                <div class="field"><label>Manufacturer</label><select class="control" name="manufacturer_id"><option value="">None</option>@foreach($manufacturers as $m)<option value="{{ $m->id }}" {{ old('manufacturer_id', $model->manufacturer_id ?? '') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>@endforeach</select></div>
                <div class="field"><label>Robot Type</label><select class="control" name="robot_type_id"><option value="">None</option>@foreach($types as $t)<option value="{{ $t->id }}" {{ old('robot_type_id', $model->robot_type_id ?? '') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>@endforeach</select></div>
                <div class="field"><label>Image URL</label><input class="control" name="image" value="{{ old('image', $model->image ?? '') }}"></div>
            </div>
            <div class="field"><label>Description</label><textarea class="control" name="description" rows="4">{{ old('description', $model->description ?? '') }}</textarea></div>
            <div class="field"><label>Short Description</label><textarea class="control" name="short_description" rows="2">{{ old('short_description', $model->short_description ?? '') }}</textarea></div>
        </div>
    </div>
    <div class="card" style="margin-top:16px">
        <div class="card-h"><h2>Physical Specifications</h2></div>
        <div class="card-body">
            <div class="form-grid">
                <div class="field"><label>Payload (kg)</label><input class="control" type="number" step="0.01" name="payload_kg" value="{{ old('payload_kg', $model->payload_kg ?? '') }}"></div>
                <div class="field"><label>Reach (mm)</label><input class="control" type="number" step="0.01" name="reach_mm" value="{{ old('reach_mm', $model->reach_mm ?? '') }}"></div>
                <div class="field"><label>DoF</label><input class="control" type="number" name="degrees_of_freedom" value="{{ old('degrees_of_freedom', $model->degrees_of_freedom ?? '') }}"></div>
                <div class="field"><label>Length (mm)</label><input class="control" type="number" step="0.01" name="length_mm" value="{{ old('length_mm', $model->length_mm ?? '') }}"></div>
                <div class="field"><label>Width (mm)</label><input class="control" type="number" step="0.01" name="width_mm" value="{{ old('width_mm', $model->width_mm ?? '') }}"></div>
                <div class="field"><label>Height (mm)</label><input class="control" type="number" step="0.01" name="height_mm" value="{{ old('height_mm', $model->height_mm ?? '') }}"></div>
                <div class="field"><label>Weight (kg)</label><input class="control" type="number" step="0.01" name="weight_kg" value="{{ old('weight_kg', $model->weight_kg ?? '') }}"></div>
                <div class="field"><label>Speed (m/s)</label><input class="control" type="number" step="0.01" name="speed_mps" value="{{ old('speed_mps', $model->speed_mps ?? '') }}"></div>
            </div>
        </div>
    </div>
    <div class="card" style="margin-top:16px">
        <div class="card-h"><h2>Compute & Software</h2></div>
        <div class="card-body">
            <div class="form-grid">
                <div class="field"><label>Compute Platform</label><input class="control" name="compute_platform" value="{{ old('compute_platform', $model->compute_platform ?? '') }}"></div>
                <div class="field"><label>AI Accelerator</label><input class="control" name="ai_accelerator" value="{{ old('ai_accelerator', $model->ai_accelerator ?? '') }}"></div>
                <div class="field"><label>Operating System</label><input class="control" name="operating_system" value="{{ old('operating_system', $model->operating_system ?? '') }}"></div>
                <div class="field"><label>Indoor/Outdoor</label><select class="control" name="indoor_outdoor"><option value="">—</option><option value="indoor" {{ old('indoor_outdoor', $model->indoor_outdoor ?? '') === 'indoor' ? 'selected' : '' }}>Indoor</option><option value="outdoor" {{ old('indoor_outdoor', $model->indoor_outdoor ?? '') === 'outdoor' ? 'selected' : '' }}>Outdoor</option><option value="both" {{ old('indoor_outdoor', $model->indoor_outdoor ?? '') === 'both' ? 'selected' : '' }}>Both</option></select></div>
                <div class="field"><label>IP Rating</label><input class="control" name="ip_rating" value="{{ old('ip_rating', $model->ip_rating ?? '') }}"></div>
            </div>
            <div class="form-grid" style="margin-top:12px">
                <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="ros_support" value="1" {{ old('ros_support', $model->ros_support ?? 0) ? 'checked' : '' }}> ROS Support</label></div>
                <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="ros2_support" value="1" {{ old('ros2_support', $model->ros2_support ?? 0) ? 'checked' : '' }}> ROS 2 Support</label></div>
                <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="sdk_available" value="1" {{ old('sdk_available', $model->sdk_available ?? 0) ? 'checked' : '' }}> SDK Available</label></div>
                <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="simulation_support" value="1" {{ old('simulation_support', $model->simulation_support ?? 0) ? 'checked' : '' }}> Simulation Support</label></div>
            </div>
        </div>
    </div>
    <div class="card" style="margin-top:16px">
        <div class="card-h"><h2>Commerce</h2></div>
        <div class="card-body">
            <div class="form-grid">
                <div class="field"><label>Global Price</label><input class="control" type="number" step="0.01" name="global_price" value="{{ old('global_price', $model->global_price ?? '') }}"></div>
                <div class="field"><label>Currency</label><input class="control" name="currency" value="{{ old('currency', $model->currency ?? 'USD') }}"></div>
            </div>
            <div class="form-grid" style="margin-top:12px">
                <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $model->is_active ?? 1) ? 'checked' : '' }}> Active</label></div>
                <div class="field"><label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $model->is_featured ?? 0) ? 'checked' : '' }}> Featured</label></div>
            </div>
        </div>
    </div>
    @if($applications->count())
    <div class="card" style="margin-top:16px">
        <div class="card-h"><h2>Applications</h2></div>
        <div class="card-body">
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                @php $selected = old('applications', $model->applications->pluck('id')->toArray() ?? []); @endphp
                @foreach($applications as $app)
                <label style="display:flex;align-items:center;gap:6px;padding:6px 12px;background:var(--bg);border-radius:6px;font-size:.88rem">
                    <input type="checkbox" name="applications[]" value="{{ $app->id }}" {{ in_array($app->id, $selected) ? 'checked' : '' }}> {{ $app->name }}
                </label>
                @endforeach
            </div>
        </div>
    </div>
    @endif
    <div style="margin-top:16px;display:flex;gap:8px">
        <button type="submit" class="btn btn-primary">{{ $model ? 'Update Robot Model' : 'Create Robot Model' }}</button>
        <a href="/admin/ai-robotics/robot-models" class="btn btn-ghost">Cancel</a>
    </div>
</form>
@endsection
