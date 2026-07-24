@extends('frontend.layout')
@section('title', $integrator->name . ' — NeoGiga AI & Robotics')
@section('meta_description', Str::limit($integrator->description, 160))
@section('content')
<div style="max-width:1000px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / <a href="{{ url($localePrefix ?? '/en') }}/ai/integrators" style="color:#3b82f6;text-decoration:none">Integrators</a> / {{ $integrator->name }}</div>
    <div style="display:flex;gap:16px;align-items:center;margin-bottom:24px">
        @if($integrator->logo)<img src="{{ $integrator->logo }}" alt="{{ $integrator->name }}" style="width:64px;height:64px;object-fit:contain;border-radius:12px;background:#f8fafc">@endif
        <div><h1 style="font-size:1.8rem;font-weight:800;margin-bottom:4px">{{ $integrator->name }}</h1><p style="color:#64748b">{{ $integrator->country ?? 'Global' }}</p></div>
    </div>
    @if($integrator->description)<p style="color:#475569;line-height:1.6;margin-bottom:24px">{{ $integrator->description }}</p>@endif
    @if($integrator->services && count($integrator->services))
    <div style="margin-bottom:24px"><h3 style="font-weight:700;margin-bottom:8px">Services</h3><div style="display:flex;gap:6px;flex-wrap:wrap">@foreach($integrator->services as $s)<span style="padding:4px 12px;background:#f0fdf4;color:#16a34a;border-radius:6px;font-size:.85rem">{{ $s }}</span>@endforeach</div></div>
    @endif
    @if($integrator->regions_served && count($integrator->regions_served))
    <div style="margin-bottom:24px"><h3 style="font-weight:700;margin-bottom:8px">Regions Served</h3><div style="display:flex;gap:6px;flex-wrap:wrap">@foreach($integrator->regions_served as $r)<span style="padding:4px 12px;background:#eff6ff;color:#2563eb;border-radius:6px;font-size:.85rem">{{ $r }}</span>@endforeach</div></div>
    @endif
    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:24px">
        <a href="{{ url($localePrefix ?? '/en') }}/rfq?integrator={{ $integrator->slug }}" style="padding:12px 24px;background:#3b82f6;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Request Quotation</a>
        <a href="mailto:{{ $integrator->contact_email }}" style="padding:12px 24px;background:#10b981;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Contact</a>
        @if($integrator->website_url)<a href="{{ $integrator->website_url }}" target="_blank" style="padding:12px 24px;background:#f1f5f9;color:#475569;border-radius:8px;text-decoration:none;font-weight:600">Website</a>@endif
    </div>
</div>
@endsection
