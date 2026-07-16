@extends('frontend.layout')

@section('title', $title)
@section('description', $description)
@push('head')
<script type="application/ld+json">@json($jsonLd, JSON_UNESCAPED_SLASHES)</script>
<style>
.page-hero{padding:44px 0 22px}.eyebrow{color:var(--gold);font-weight:900;letter-spacing:.12em;text-transform:uppercase;font-size:.76rem}
.two-col{display:grid;grid-template-columns:1.1fr .9fr;gap:24px;align-items:start}.panel{background:rgba(13,34,64,.86);border:1px solid var(--line);border-radius:12px;padding:22px}
.feature-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;margin:24px 0}.feature{border:1px solid var(--line);border-radius:10px;padding:16px;background:rgba(255,255,255,.025)}
.feature h3{margin:0 0 6px;font-size:1rem}.muted{color:var(--muted)}.chips{display:flex;flex-wrap:wrap;gap:8px;margin:16px 0}.chips span{border:1px solid rgba(25,211,245,.28);border-radius:999px;padding:6px 10px;color:var(--soft);font-size:.88rem}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.form-grid .full{grid-column:1/-1}label{display:block;color:var(--soft);font-weight:800;font-size:.82rem;margin-bottom:5px}
input,select,textarea{width:100%;border:1px solid rgba(255,255,255,.16);border-radius:8px;background:#07182d;color:#fff;padding:11px 12px;font:inherit}textarea{min-height:100px}.notice{margin-top:12px;font-size:.9rem}.ok{color:var(--green)}.err{color:#ffb4b4}
@media(max-width:820px){.two-col,.form-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<section class="page-hero">
    <p class="eyebrow"><x-icon name="sellers" size="14"/> Seller Early Access</p>
    <h1>Sell on NeoGiga</h1>
    <p class="lead">Reach engineers, makers, schools, labs, workshops, resellers, and B2B buyers across South Asia.</p>
</section>
<section class="two-col">
    <div>
        <div class="panel">
            <h2>Structured multi-marketplace selling</h2>
            <p class="muted">NeoGiga helps distributors, manufacturers, importers, and local electronics shops sell through a structured multi-marketplace platform with per-country approval, regional stock visibility, transparent settlement, RFQ support, and future AI-assisted product discovery.</p>
            <div class="feature-grid">
                @foreach (['Sell across Nepal, India, and future South Asian marketplaces','Per-marketplace approval and compliance control','Region-wise stock and warehouse visibility','Product catalog with variants, attributes, datasheets, warranty, and country of origin','RFQ and B2B bulk order support','Transparent order, commission, and settlement tracking','Distributor and local shop network support','AI commerce discovery inside project/BOM suggestions','LMS/project linking for robotics, IoT, solar, smart farming, and electronics kits'] as $benefit)
                    <div class="feature"><h3>{{ $benefit }}</h3></div>
                @endforeach
            </div>
            <h2>Who should apply</h2>
            <div class="chips">
                @foreach (['Manufacturers','Authorized distributors','Importers','Regional resellers','Local electronics shops','Repair/service centers','School/lab suppliers','Industrial automation suppliers'] as $type)
                    <span>{{ $type }}</span>
                @endforeach
            </div>
            <p class="muted">Seller portal is launching soon. Early applicants will be reviewed first for Nepal and India marketplace onboarding.</p>
        </div>
    </div>
    <div class="panel" id="apply">
        @include('frontend.partials.seller-application-form')
    </div>
</section>
@endsection
