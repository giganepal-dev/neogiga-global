@extends('frontend.layout')
@section('title', $package->name . ' — Institutional Lab Package')
@section('meta_description', Str::limit($package->short_description ?? $package->description, 160))
@section('content')
<div style="max-width:1000px;margin:0 auto;padding:24px">
    <div style="font-size:.85rem;color:#64748b;margin-bottom:16px"><a href="{{ url($localePrefix ?? '/en') }}/ai" style="color:#3b82f6;text-decoration:none">AI & Robotics</a> / <a href="{{ url($localePrefix ?? '/en') }}/ai/institutional" style="color:#3b82f6;text-decoration:none">Institutional</a> / {{ $package->name }}</div>
    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:24px">
        <div>
            <h1 style="font-size:1.8rem;font-weight:800;margin-bottom:8px">{{ $package->name }}</h1>
            @if($package->target_institution)<span style="padding:4px 12px;background:#eff6ff;color:#2563eb;border-radius:6px;font-size:.85rem">{{ ucfirst($package->target_institution) }}</span>@endif
        </div>
        @if($package->base_price)<div style="font-size:1.5rem;font-weight:700;color:#059669">{{ $package->currency }} {{ number_format($package->base_price, 2) }}</div>@endif
    </div>
    @if($package->description)<p style="color:#475569;line-height:1.6;margin-bottom:24px">{{ $package->description }}</p>@endif

    @if($package->includes && count($package->includes))
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:24px;margin-bottom:24px">
        <h3 style="font-weight:700;margin-bottom:12px">Package Includes</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            @foreach($package->includes as $inc)
                <div style="display:flex;align-items:center;gap:8px;padding:8px;font-size:.9rem">✓ {{ $inc }}</div>
            @endforeach
        </div>
    </div>
    @endif

    @if($package->products->count())
    <div style="margin-bottom:24px">
        <h3 style="font-weight:700;margin-bottom:12px">Equipment List ({{ $package->products->count() }} items)</h3>
        <div style="display:grid;gap:8px">
            @foreach($package->products as $product)
            <div style="display:flex;justify-content:space-between;padding:12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px">
                <div>
                    <a href="{{ url($localePrefix ?? '/en') }}/products/{{ $product->slug }}" style="font-weight:600;color:inherit;text-decoration:none">{{ $product->name }}</a>
                    @if($product->sku)<span style="color:#64748b;font-size:.8rem;margin-left:8px">SKU: {{ $product->sku }}</span>@endif
                </div>
                <div style="text-align:right">
                    <div style="font-weight:600">×{{ $product->pivot->quantity }}</div>
                    @if($product->pivot->is_required)<span style="font-size:.75rem;color:#dc2626">Required</span>@endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:32px">
        <a href="{{ url($localePrefix ?? '/en') }}/rfq?package={{ $package->slug }}" style="padding:14px 28px;background:#3b82f6;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Request Custom Quotation</a>
        <a href="{{ url($localePrefix ?? '/en') }}/ai/lab" style="padding:14px 28px;background:#10b981;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">Request Consultation</a>
    </div>
</div>
@endsection
