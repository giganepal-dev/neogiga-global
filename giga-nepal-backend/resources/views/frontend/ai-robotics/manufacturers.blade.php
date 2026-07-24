@extends('frontend.layout')
@section('title', 'AI & Robotics Manufacturers — NeoGiga')
@section('meta_description', 'Browse AI and robotics manufacturers, integrators, and technology partners.')
@section('content')
<div style="max-width:1200px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / Manufacturers</div>
    <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:24px">Manufacturers & Partners</h1>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        @forelse($manufacturers as $mfr)
        <a href="{{ url($localePrefix ?? '/en') }}/ai/manufacturers/{{ $mfr->slug }}" style="display:flex;gap:16px;padding:20px;background:#fff;border:1px solid #e2e8f0;border-radius:12px;text-decoration:none;color:inherit">
            @if($mfr->logo)<img src="{{ $mfr->logo }}" alt="{{ $mfr->name }}" style="width:48px;height:48px;object-fit:contain;border-radius:8px;background:#f8fafc">@else<div style="width:48px;height:48px;background:#e2e8f0;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0">🏭</div>@endif
            <div>
                <div style="font-weight:700">{{ $mfr->name }}</div>
                <div style="font-size:.85rem;color:#64748b">{{ $mfr->country ?? 'Global' }} · {{ $mfr->robot_models_count ?? 0 }} robots</div>
                <div style="margin-top:6px;display:flex;gap:4px;flex-wrap:wrap">
                    @if($mfr->is_robot_manufacturer)<span style="padding:1px 6px;background:#eff6ff;color:#2563eb;border-radius:3px;font-size:.65rem">Robots</span>@endif
                    @if($mfr->is_ai_hardware_manufacturer)<span style="padding:1px 6px;background:#f0fdf4;color:#16a34a;border-radius:3px;font-size:.65rem">AI Hardware</span>@endif
                    @if($mfr->is_software_provider)<span style="padding:1px 6px;background:#fef3c7;color:#92400e;border-radius:3px;font-size:.65rem">Software</span>@endif
                </div>
            </div>
        </a>
        @empty
        <div style="grid-column:1/-1;text-align:center;padding:48px;color:#64748b">No manufacturers listed yet.</div>
        @endforelse
    </div>
</div>
@endsection
