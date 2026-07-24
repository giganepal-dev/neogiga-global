@extends('frontend.layout')
@section('title', 'Institutional Lab Packages — NeoGiga AI & Robotics')
@section('meta_description', 'Configure robotics and AI labs for schools, universities, and research institutions.')
@section('content')
<div style="max-width:1200px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / Institutional Solutions</div>
    <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:8px">Institutional Lab Packages</h1>
    <p style="color:#64748b;margin-bottom:32px">Complete lab solutions for schools, universities, and research institutions</p>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:20px">
        @forelse($packages as $pkg)
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:28px">
            <div style="font-weight:700;font-size:1.1rem;margin-bottom:4px">{{ $pkg->name }}</div>
            @if($pkg->target_institution)<span style="padding:3px 10px;background:#eff6ff;color:#2563eb;border-radius:6px;font-size:.75rem">{{ ucfirst($pkg->target_institution) }}</span>@endif
            <p style="color:#64748b;margin:12px 0;font-size:.9rem">{{ $pkg->short_description ?? $pkg->description }}</p>
            @if($pkg->includes && count($pkg->includes))
                <div style="margin:12px 0;font-size:.85rem;color:#475569">@foreach($pkg->includes as $inc) ✓ {{ $inc }}<br>@endforeach</div>
            @endif
            @if($pkg->base_price)<div style="font-weight:700;color:#059669;font-size:1.1rem;margin:12px 0">From {{ $pkg->currency }} {{ number_format($pkg->base_price, 2) }}</div>@endif
            <div style="display:flex;gap:8px;margin-top:16px">
                <a href="{{ url($localePrefix ?? '/en') }}/rfq?package={{ $pkg->slug }}" style="padding:10px 20px;background:#3b82f6;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:.9rem">Request Quotation</a>
                <a href="{{ url($localePrefix ?? '/en') }}/ai/lab" style="padding:10px 20px;background:#f1f5f9;color:#475569;border-radius:8px;text-decoration:none;font-weight:600;font-size:.9rem">Consult</a>
            </div>
        </div>
        @empty
        <div style="grid-column:1/-1;text-align:center;padding:48px;color:#64748b">Institutional packages are being prepared.</div>
        @endforelse
    </div>
</div>
@endsection
