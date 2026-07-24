@extends('frontend.layout')
@section('title', 'AI & Robotics Integrators — NeoGiga')
@section('meta_description', 'Find robotics integrators, solution providers, and technology partners.')
@section('content')
<div style="max-width:1200px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / Integrators</div>
    <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:24px">Integrators & Solution Providers</h1>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px">
        @forelse($integrators as $intg)
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px">
            <div style="display:flex;gap:12px;align-items:center;margin-bottom:12px">
                @if($intg->logo)<img src="{{ $intg->logo }}" alt="{{ $intg->name }}" style="width:40px;height:40px;object-fit:contain;border-radius:8px">@else<div style="width:40px;height:40px;background:#e2e8f0;border-radius:8px;display:flex;align-items:center;justify-content:center">🔧</div>@endif
                <div><div style="font-weight:700">{{ $intg->name }}</div><div style="font-size:.8rem;color:#64748b">{{ $intg->country ?? 'Global' }}</div></div>
            </div>
            <p style="font-size:.85rem;color:#64748b;margin-bottom:12px">{{ Str::limit($intg->description, 150) }}</p>
            @if($intg->services && count($intg->services))<div style="display:flex;gap:4px;flex-wrap:wrap">@foreach(array_slice($intg->services, 0, 4) as $s)<span style="padding:2px 8px;background:#f0fdf4;color:#16a34a;border-radius:4px;font-size:.7rem">{{ $s }}</span>@endforeach</div>@endif
        </div>
        @empty
        <div style="grid-column:1/-1;text-align:center;padding:48px;color:#64748b">No integrators listed yet.</div>
        @endforelse
    </div>
</div>
@endsection
