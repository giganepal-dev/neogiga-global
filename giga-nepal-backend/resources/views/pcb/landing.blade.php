@extends('pcb.layout')

@section('title', 'NeoGiga PCB — Secure PCB Fabrication Projects and Engineering Quotes')
@section('description', 'Create a secure PCB project, upload Gerber, BOM and CPL files, submit board specifications, receive an engineering-reviewed quote and track production.')

@push('styles')
<style>
    .pcb-hero{position:relative;min-height:min(680px,calc(100vh - 77px));display:flex;align-items:center;background:#081626;color:#fff;overflow:hidden}.pcb-hero>img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center}.pcb-hero::after{content:"";position:absolute;inset:0;background:rgba(3,14,26,.22)}.hero-copy{position:relative;z-index:1;width:min(650px,100%);padding:64px 0}.hero-copy .eyebrow{color:var(--gold)}.hero-copy h1{font-size:clamp(2.7rem,7vw,5.8rem);line-height:.93;margin:12px 0 20px;max-width:10ch}.hero-copy p{font-size:1.08rem;color:#d5e1ed;max-width:57ch}.hero-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:28px}.trust-row{display:flex;gap:18px;flex-wrap:wrap;margin-top:32px;color:#c1cedb;font-size:.8rem;font-weight:750}.trust-row span{display:flex;align-items:center;gap:7px}.trust-row i{width:7px;height:7px;border-radius:50%;background:var(--cyan)}
    .band{padding:58px 0}.band.white{background:#fff}.section-head{display:flex;align-items:end;justify-content:space-between;gap:24px;margin-bottom:24px}.section-head h2{font-size:clamp(1.7rem,4vw,2.7rem);line-height:1.05;margin:0}.section-head p{margin:0;color:var(--muted);max-width:58ch}.capabilities{grid-template-columns:repeat(4,minmax(0,1fr))}.capability{padding:20px;border-left:3px solid var(--cyan);background:#fff}.capability:nth-child(2n){border-left-color:var(--gold)}.capability b{display:block;font-size:1.05rem;margin-bottom:6px}.capability p{margin:0;color:var(--muted);font-size:.88rem}.workflow{grid-template-columns:repeat(4,minmax(0,1fr));counter-reset:step}.step{position:relative;padding:18px 18px 18px 54px;border-top:1px solid var(--line);counter-increment:step}.step::before{content:counter(step);position:absolute;left:0;top:14px;width:38px;height:38px;display:grid;place-items:center;background:var(--navy);color:var(--cyan);border-radius:7px;font-weight:900}.step b{display:block;margin-bottom:5px}.step p{margin:0;color:var(--muted);font-size:.86rem}.cta-band{background:#0d2741;color:#fff}.cta-row{display:flex;align-items:center;justify-content:space-between;gap:28px}.cta-row h2{font-size:clamp(1.8rem,4vw,3rem);margin:0 0 8px}.cta-row p{margin:0;color:#c8d5e2}.cta-row .actions{flex:none}
    @media(max-width:900px){.capabilities,.workflow{grid-template-columns:repeat(2,1fr)}.pcb-hero>img{object-position:62% center}.pcb-hero::after{background:rgba(3,14,26,.5)}}
    @media(max-width:600px){.pcb-hero{min-height:620px}.pcb-hero>img{object-position:68% center}.pcb-hero::after{background:rgba(3,14,26,.68)}.hero-copy{padding:48px 0}.hero-actions{display:grid}.trust-row{display:grid;gap:9px}.capabilities,.workflow{grid-template-columns:1fr}.section-head,.cta-row{display:block}.section-head p{margin-top:10px}.cta-row .actions{margin-top:20px}}
</style>
@endpush

@section('content')
@if(session('status'))<div class="wrap" style="padding-top:16px"><div class="notice">{{ session('status') }}</div></div>@endif
<section class="pcb-hero" aria-labelledby="pcb-title">
    <img src="/images/pcb/pcb-workbench-hero.webp" width="1672" height="941" alt="Detailed multilayer PCB assembly on a precision electronics workbench" fetchpriority="high">
    <div class="wrap">
        <div class="hero-copy">
            <div class="eyebrow">From Gerber to production</div>
            <h1 id="pcb-title">Build your next board.</h1>
            <p>One secure workspace for PCB fabrication requirements, private design files, BOM and CPL review, engineering quotes, approvals and production tracking.</p>
            <div class="hero-actions">
                <a class="btn btn-primary" href="/en/register">Create PCB project</a>
                <a class="btn btn-light" href="/en/login">Open workspace</a>
            </div>
            <div class="trust-row" aria-label="Security and workflow highlights">
                <span><i></i>Private file storage</span>
                <span><i></i>Signed downloads</span>
                <span><i></i>Engineering-reviewed quotes</span>
            </div>
        </div>
    </div>
</section>

<section class="band white" id="capabilities" aria-labelledby="capabilities-title">
    <div class="wrap">
        <div class="section-head">
            <h2 id="capabilities-title">One controlled engineering workspace</h2>
            <p>Manufacturing capability and commercial terms are confirmed by a NeoGiga engineer after file and specification review.</p>
        </div>
        <div class="grid capabilities">
            <article class="capability"><b>PCB fabrication</b><p>Rigid, multilayer, flex, rigid-flex, aluminum and engineering-review board types.</p></article>
            <article class="capability"><b>Assembly preparation</b><p>Gerber, BOM, CPL, schematic, fabrication drawing and STEP file organization.</p></article>
            <article class="capability"><b>Quality controls</b><p>Electrical test, AOI, finish, copper, stack-up and advanced-process requirements.</p></article>
            <article class="capability"><b>Regional delivery</b><p>Destination, quantity, required date and currency remain attached to the project.</p></article>
        </div>
    </div>
</section>

<section class="band" id="workflow" aria-labelledby="workflow-title">
    <div class="wrap">
        <div class="section-head">
            <h2 id="workflow-title">A traceable path to production</h2>
            <p>Every upload, review, quote and approval is recorded against the project.</p>
        </div>
        <div class="grid workflow">
            <article class="step"><b>Create project</b><p>Set quantity, delivery destination, confidentiality and target date.</p></article>
            <article class="step"><b>Upload files</b><p>Add private Gerber ZIP, BOM, CPL and supporting engineering documents.</p></article>
            <article class="step"><b>Review quote</b><p>Receive pricing and lead time only after engineering review.</p></article>
            <article class="step"><b>Track order</b><p>Approve the quote and continue through the shared NeoGiga order workflow.</p></article>
        </div>
    </div>
</section>

<section class="band cta-band">
    <div class="wrap cta-row">
        <div><h2>Start with your design requirements.</h2><p>No public file links. No automatic charge. Commercial approval remains explicit.</p></div>
        <div class="actions"><a class="btn btn-gold" href="/en/register">Start secure project</a></div>
    </div>
</section>
@endsection
