@extends('frontend.layout')
@section('title', $manufacturer->name . ' — NeoGiga AI & Robotics')
@section('meta_description', Str::limit($manufacturer->description, 160))
@section('content')
<div style="max-width:1000px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / <a href="{{ url($localePrefix ?? '/en') }}/ai/manufacturers" style="color:#3b82f6;text-decoration:none">Manufacturers</a> / {{ $manufacturer->name }}</div>
    <div style="display:flex;gap:16px;align-items:center;margin-bottom:24px">
        @if($manufacturer->logo)<img src="{{ $manufacturer->logo }}" alt="{{ $manufacturer->name }}" style="width:64px;height:64px;object-fit:contain;border-radius:12px;background:#f8fafc">@endif
        <div><h1 style="font-size:1.8rem;font-weight:800;margin-bottom:4px">{{ $manufacturer->name }}</h1><p style="color:#64748b">{{ $manufacturer->country ?? 'Global' }}</p></div>
    </div>
    @if($manufacturer->description)<p style="color:#475569;line-height:1.6;margin-bottom:24px">{{ $manufacturer->description }}</p>@endif
    @if($robots->count())
    <h2 style="font-size:1.2rem;font-weight:700;margin-bottom:16px">Robot Models ({{ $robots->count() }})</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        @foreach($robots as $robot)
        <a href="{{ url($localePrefix ?? '/en') }}/ai/robots/{{ $robot->slug }}" style="display:block;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:16px;text-decoration:none;color:inherit">
            <div style="font-weight:700">{{ $robot->name }}</div>
            <div style="font-size:.85rem;color:#64748b">{{ $robot->model_number ?? '' }}</div>
            @if($robot->global_price)<div style="margin-top:8px;font-weight:600;color:#059669">{{ $robot->currency }} {{ number_format($robot->global_price, 2) }}</div>@endif
        </a>
        @endforeach
    </div>
    @endif
</div>
@endsection
