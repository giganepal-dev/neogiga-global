@extends('frontend.layout')
@section('title', 'AI & Robotics Lab — NeoGiga')
@section('meta_description', 'Physical and virtual robotics labs, demonstrations, workshops, and institutional solutions.')
@section('content')
<div style="max-width:1200px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / Lab</div>
    <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:8px">AI & Robotics Lab</h1>
    <p style="color:#64748b;margin-bottom:32px">Physical demonstrations, virtual simulations, and hands-on workshops</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:40px">
        {{-- Physical Lab --}}
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:32px">
            <div style="font-size:2rem;margin-bottom:8px">🔬</div>
            <h2 style="font-size:1.3rem;font-weight:700;margin-bottom:12px">Physical Lab</h2>
            <ul style="list-style:none;padding:0;margin:0 0 20px">
                @foreach(['Lab Demonstrations','Workshops & Training','Prototype Development','Product Testing','AI Deployment','Robot Programming','Research Collaboration','Installation & Commissioning'] as $item)
                    <li style="padding:6px 0;color:#475569;font-size:.9rem;border-bottom:1px solid #e2e8f0">✓ {{ $item }}</li>
                @endforeach
            </ul>
            <a href="{{ url($localePrefix ?? '/en') }}/ai/lab" style="display:inline-block;padding:12px 24px;background:#3b82f6;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Book a Session</a>
        </div>

        {{-- Virtual Lab --}}
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:32px">
            <div style="font-size:2rem;margin-bottom:8px">💻</div>
            <h2 style="font-size:1.3rem;font-weight:700;margin-bottom:12px">Virtual Lab</h2>
            <ul style="list-style:none;padding:0;margin:0 0 20px">
                @foreach(['ROS Simulation','Robot Simulation','Digital Twins','Virtual Electronics','Cloud Notebooks','Computer Vision Testing','Remote Robot Access','Student Workspaces'] as $item)
                    <li style="padding:6px 0;color:#475569;font-size:.9rem;border-bottom:1px solid #e2e8f0">✓ {{ $item }}</li>
                @endforeach
            </ul>
            <a href="{{ url($localePrefix ?? '/en') }}/ai/lab" style="display:inline-block;padding:12px 24px;background:#10b981;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Access Virtual Lab</a>
        </div>
    </div>

    @if(isset($packages) && $packages->count())
    <h2 style="font-size:1.3rem;font-weight:700;margin-bottom:16px">Institutional Lab Packages</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px">
        @foreach($packages as $pkg)
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px">
            <div style="font-weight:700;margin-bottom:4px">{{ $pkg->name }}</div>
            <div style="font-size:.85rem;color:#64748b;margin-bottom:8px">{{ $pkg->short_description ?? Str::limit($pkg->description, 120) }}</div>
            <div style="display:flex;gap:8px;align-items:center">
                @if($pkg->target_institution)<span style="padding:2px 8px;background:#f0fdf4;color:#16a34a;border-radius:4px;font-size:.75rem">{{ ucfirst($pkg->target_institution) }}</span>@endif
                @if($pkg->base_price)<span style="font-weight:600;color:#059669;font-size:.9rem">From {{ $pkg->currency }} {{ number_format($pkg->base_price, 2) }}</span>@endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection
