@extends('frontend.layout')
@section('title', 'AI Model Library — NeoGiga AI & Robotics')
@section('meta_description', 'Browse AI models for robotics, computer vision, NLP, and edge deployment.')

@section('content')
<div style="max-width:1200px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / AI Models</div>
    <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:24px">AI Model Library</h1>

    <form method="GET" style="display:flex;gap:12px;margin-bottom:24px;flex-wrap:wrap">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search AI models..." style="flex:1;min-width:200px;padding:10px 12px;border:1px solid #d1d5db;border-radius:6px">
        <select name="type" style="padding:10px 12px;border:1px solid #d1d5db;border-radius:6px">
            <option value="">All Types</option>
            @foreach(['vision','nlp','speech','generative','reinforcement'] as $t)
                <option value="{{ $t }}" {{ request('type')===$t ? 'selected' : '' }}>{{ ucfirst($t) }}</option>
            @endforeach
        </select>
        <button type="submit" style="padding:10px 24px;background:#3b82f6;color:#fff;border:none;border-radius:6px;font-weight:600">Search</button>
    </form>

    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px">
        @forelse($models as $model)
        <a href="{{ url($localePrefix ?? '/en') }}/ai/ai-models/{{ $model->slug }}" style="display:block;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px;text-decoration:none;color:inherit">
            <div style="display:flex;justify-content:space-between;align-items:start">
                <div style="font-weight:700">{{ $model->name }}</div>
                <span style="padding:2px 8px;background:{{ $model->license_type==='open_source'?'#f0fdf4':'#fef3c7' }};color:{{ $model->license_type==='open_source'?'#16a34a':'#92400e' }};border-radius:4px;font-size:.7rem">{{ $model->license_type ?? 'Unknown' }}</span>
            </div>
            <div style="font-size:.85rem;color:#64748b;margin-top:4px">{{ $model->provider ?? '' }}</div>
            <div style="font-size:.85rem;color:#475569;margin-top:8px">{{ Str::limit($model->description, 120) }}</div>
            <div style="margin-top:12px;display:flex;gap:6px;flex-wrap:wrap">
                @if($model->model_type)
                    <span style="padding:2px 8px;background:#eff6ff;color:#2563eb;border-radius:4px;font-size:.7rem">{{ ucfirst($model->model_type) }}</span>
                @endif
                @if($model->edge_compatible)
                    <span style="padding:2px 8px;background:#f0fdf4;color:#16a34a;border-radius:4px;font-size:.7rem">Edge</span>
                @endif
                @if($model->cloud_compatible)
                    <span style="padding:2px 8px;background:#f0f7ff;color:#0369a1;border-radius:4px;font-size:.7rem">Cloud</span>
                @endif
            </div>
        </a>
        @empty
        <div style="grid-column:1/-1;text-align:center;padding:48px;color:#64748b">No AI models found.</div>
        @endforelse
    </div>
    <div style="margin-top:32px;text-align:center">{{ $models->withQueryString()->links() }}</div>
</div>
@endsection
