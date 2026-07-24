@extends('frontend.layout')
@section('title', $model->name . ' — AI Model Library')
@section('meta_description', Str::limit($model->description, 160))
@section('content')
<div style="max-width:1000px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / <a href="{{ url($localePrefix ?? '/en') }}/ai/ai-models" style="color:#3b82f6;text-decoration:none">AI Models</a> / {{ $model->name }}</div>
    <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:8px">{{ $model->name }}</h1>
    <p style="color:#64748b;margin-bottom:20px">{{ $model->provider ?? '' }} · {{ ucfirst($model->model_type ?? '') }}</p>
    @if($model->description)<p style="color:#475569;line-height:1.6;margin-bottom:24px">{{ $model->description }}</p>@endif

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:32px">
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px">
            <h3 style="font-weight:700;margin-bottom:12px">Details</h3>
            @php $details = ['License'=>$model->license_name??$model->license_type,'Edge Compatible'=>$model->edge_compatible?'Yes':'No','Cloud Compatible'=>$model->cloud_compatible?'Yes':'No']; @endphp
            @foreach($details as $k=>$v)<div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #e2e8f0;font-size:.9rem"><span style="color:#64748b">{{ $k }}</span><span style="font-weight:500">{{ $v }}</span></div>@endforeach
        </div>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px">
            <h3 style="font-weight:700;margin-bottom:12px">Links</h3>
            @if($model->documentation_url)<a href="{{ $model->documentation_url }}" target="_blank" style="display:block;padding:6px 0;color:#3b82f6;text-decoration:none;font-size:.9rem">Documentation →</a>@endif
            @if($model->download_url)<a href="{{ $model->download_url }}" target="_blank" style="display:block;padding:6px 0;color:#3b82f6;text-decoration:none;font-size:.9rem">Download →</a>@endif
            @if($model->github_url)<a href="{{ $model->github_url }}" target="_blank" style="display:block;padding:6px 0;color:#3b82f6;text-decoration:none;font-size:.9rem">GitHub →</a>@endif
        </div>
    </div>

    @if($model->supported_tasks && count($model->supported_tasks))
    <div style="margin-bottom:24px"><h3 style="font-weight:700;margin-bottom:8px">Supported Tasks</h3><div style="display:flex;gap:6px;flex-wrap:wrap">@foreach($model->supported_tasks as $t)<span style="padding:4px 10px;background:#eff6ff;color:#2563eb;border-radius:6px;font-size:.85rem">{{ $t }}</span>@endforeach</div></div>
    @endif

    @if($model->hardware->count())
    <div style="margin-bottom:24px"><h3 style="font-weight:700;margin-bottom:8px">Compatible Hardware</h3><div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:8px">@foreach($model->hardware as $hw)<div style="padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:.9rem">{{ $hw->name }}</div>@endforeach</div></div>
    @endif
</div>
@endsection
