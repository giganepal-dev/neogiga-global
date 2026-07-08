@extends('frontend.layout')

@section('title', $title)
@section('description', $description)
@push('head')
<script type="application/ld+json">@json($jsonLd, JSON_UNESCAPED_SLASHES)</script>
<style>
.page-hero{padding:44px 0 22px}.eyebrow{color:var(--gold);font-weight:900;letter-spacing:.12em;text-transform:uppercase;font-size:.76rem}.two-col{display:grid;grid-template-columns:1fr 1fr;gap:24px}.panel{background:rgba(13,34,64,.86);border:1px solid var(--line);border-radius:12px;padding:22px}.list{display:grid;gap:10px;margin:18px 0}.list div{border:1px solid var(--line);border-radius:10px;padding:13px}.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.form-grid .full{grid-column:1/-1}label{display:block;color:var(--soft);font-weight:800;font-size:.82rem;margin-bottom:5px}input,select,textarea{width:100%;border:1px solid rgba(255,255,255,.16);border-radius:8px;background:#07182d;color:#fff;padding:11px 12px;font:inherit}textarea{min-height:100px}.notice{margin-top:12px;font-size:.9rem}.ok{color:var(--green)}.err{color:#ffb4b4}.muted{color:var(--muted)}@media(max-width:820px){.two-col,.form-grid{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<section class="page-hero">
    <p class="eyebrow">Distributor Network</p>
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
