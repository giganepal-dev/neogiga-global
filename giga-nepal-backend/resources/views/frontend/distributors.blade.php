@extends('frontend.layout')

@section('title', $title)
@section('description', $description)
@section('body_class', 'partner-page')
@push('head')
<script nonce="{{ $csp_nonce ?? '' }}" type="application/ld+json">@json($jsonLd, JSON_UNESCAPED_SLASHES)</script>
<style nonce="{{ $csp_nonce ?? '' }}">
.partner-page .page-hero,.partner-page .two-col{width:min(var(--max),calc(100% - 40px));margin-inline:auto}.partner-page .page-hero{padding:28px 0 18px}.eyebrow{color:var(--gold);font-weight:900;letter-spacing:.12em;text-transform:uppercase;font-size:.76rem}.partner-page .page-hero h1{margin:5px 0 6px;color:var(--on)}.partner-page .two-col{display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;padding-bottom:64px}.partner-page .panel{background:var(--s1);border:1px solid var(--line);border-radius:14px;padding:22px;box-shadow:0 10px 30px rgba(0,0,0,.12)}.list{display:grid;gap:10px;margin:18px 0}.list div{border:1px solid var(--line);border-radius:10px;padding:13px}.partner-page .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.partner-page .form-grid>div{min-width:0}.partner-page .form-grid .full{grid-column:1/-1}.partner-page .form-grid label{display:block;color:var(--on);font-weight:700;font-size:.82rem;margin-bottom:6px}.partner-page .form-grid input,.partner-page .form-grid select,.partner-page .form-grid textarea,.marketplace-picker__toggle{width:100%;min-height:46px;border:1px solid var(--line);border-radius:9px;background:var(--s2);color:var(--on);padding:11px 12px;font:inherit}.partner-page .form-grid input::placeholder,.partner-page .form-grid textarea::placeholder{color:var(--faint);opacity:1}.partner-page .form-grid input:focus,.partner-page .form-grid select:focus,.partner-page .form-grid textarea:focus,.marketplace-picker__toggle:focus{outline:0;border-color:var(--cyan);box-shadow:0 0 0 3px rgba(15,98,230,.18)}.partner-page textarea{min-height:100px}.partner-page small.muted{display:block;margin-top:6px;line-height:1.4;overflow-wrap:anywhere}.notice{margin-top:12px;font-size:.9rem}.ok{color:var(--success)}.err{color:#ffb4b4}.muted{color:var(--muted)}.marketplace-picker{position:relative}.marketplace-picker__toggle{display:flex;align-items:center;justify-content:space-between;text-align:left;cursor:pointer}.marketplace-picker__toggle::after{content:'⌄';font-size:1.1rem;margin-left:12px}.marketplace-picker__menu{position:absolute;z-index:25;top:calc(100% + 6px);left:0;right:0;max-height:260px;overflow:auto;display:grid;gap:2px;padding:7px;background:var(--s1);border:1px solid var(--line);border-radius:10px;box-shadow:0 18px 45px rgba(0,0,0,.28)}.marketplace-picker__menu[hidden]{display:none!important}.partner-page .marketplace-picker__option{display:flex;align-items:center;gap:10px;margin:0;padding:10px;border-radius:7px;color:var(--on);font-weight:600;cursor:pointer}.marketplace-picker__option:hover{background:var(--s3)}.partner-page .marketplace-picker__option input{width:18px;min-height:18px;height:18px;margin:0;accent-color:var(--cyan);box-shadow:none}@media(max-width:820px){.partner-page .two-col,.partner-page .form-grid{grid-template-columns:1fr}.partner-page .page-hero,.partner-page .two-col{width:min(var(--max),calc(100% - 24px))}.partner-page .page-hero{padding-top:22px}}
</style>
@endpush
@section('content')
<section class="page-hero">
    <p class="eyebrow"><x-icon name="warehouses" size="14"/> Distributor Network</p>
    <h1>Join NeoGiga Distributor Network</h1>
    <p class="lead">Build regional electronics, robotics, automation, solar, EV and maker supply with NeoGiga across Nepal, India and future South Asia marketplaces.</p>
</section>
<section class="two-col">
    <div class="panel">
        <h2>Network roles</h2>
        <div class="list">
            <div>Country and regional distributors</div>
            <div>City distributors and resellers</div>
            <div>Service partners and repair centers</div>
            <div>Institutional and school/lab suppliers</div>
        </div>
        <p class="muted">Approved network partners get onboarding review, territory planning, catalog coordination, RFQ support, and distributor workflow access when the portal launches.</p>
    </div>
    <div class="panel" id="apply">
        @include('frontend.partials.distributor-application-form')
    </div>
</section>
@endsection
