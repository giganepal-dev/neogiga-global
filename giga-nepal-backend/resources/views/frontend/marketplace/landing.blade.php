@extends('frontend.layout')
@section('title', ($marketplace->regional_brand_name ?: $marketplace->name).' — NeoGiga')
@section('description', 'NeoGiga engineering marketplace for '.$marketplace->name.'.')

@push('head')
@if($isPreview)
<meta name="robots" content="noindex,follow">
@endif
@endpush

@section('content')
<style nonce="{{ $csp_nonce ?? '' }}">
    .mkt-card{max-width:560px;margin:48px auto;padding:32px;border:1px solid rgba(15,23,42,.12);border-radius:14px;background:#fff;text-align:center}
    .mkt-card h1{font-size:1.5rem;margin:0 0 6px}
    .mkt-card .sub{color:#64748B;margin:0 0 20px}
    .mkt-badge{display:inline-block;background:#FEF9C3;color:#854D0E;border-radius:999px;padding:4px 14px;font-size:.8rem;font-weight:700;margin-bottom:16px}
    .mkt-badge.live{background:#ECFDF5;color:#065F46}
    .mkt-meta{display:flex;justify-content:center;gap:24px;margin:20px 0;color:#475569;font-size:.9rem}
    .mkt-switch{margin-top:24px;padding-top:20px;border-top:1px solid rgba(15,23,42,.08)}
    .mkt-switch a{color:#0369A1;text-decoration:none;font-size:.88rem;margin:0 8px}
</style>
<div class="wrap">
    <div class="mkt-card">
        <span class="mkt-badge {{ $isPreview ? '' : 'live' }}">{{ $isPreview ? 'Coming soon' : 'Live' }}</span>
        <h1>{{ $marketplace->regional_brand_name ?: $marketplace->name }}</h1>
        <p class="sub">
            @if($isPreview)
                NeoGiga is preparing local pricing, stock and support for this marketplace. Browse the global catalog in the meantime.
            @else
                Local pricing, stock and support for this marketplace.
            @endif
        </p>
        <div class="mkt-meta">
            <span>Currency: {{ $marketplace->currency->code ?? 'USD' }}</span>
            @if($marketplace->country)<span>Country: {{ $marketplace->country->name }}</span>@endif
        </div>

        @if($brandedUrl)
            <a class="btn btn-primary" href="{{ $brandedUrl }}">Visit {{ $marketplace->regional_brand_name ?: $marketplace->name }}</a>
        @else
            <a class="btn btn-primary" href="/products">Browse the global catalog</a>
        @endif

        <div class="mkt-switch">
            <a href="/">Stay on NeoGiga Global</a>
        </div>
    </div>
</div>
@endsection
