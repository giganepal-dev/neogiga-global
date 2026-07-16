@extends('pcb.layout')

@section('title', 'NeoGiga PCB — Instant PCB Quote, Fabrication & Assembly')
@section('description', 'Get an instant PCB fabrication quote. Upload Gerber files, review layers online, check DFM, and track production. PCB engineering platform from NeoGiga.')

@push('styles')
<style>
    .pcb-hero{position:relative;min-height:min(720px,calc(100vh - 77px));display:flex;align-items:center;background:#0c1215;overflow:hidden;border-bottom:1px solid var(--line)}
    .hero-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(410px,.9fr);gap:40px;align-items:center;position:relative;padding:56px 0}
    .hero-copy h1{font-size:clamp(2.4rem,5vw,4rem);line-height:1.02;margin:12px 0 16px}.hero-copy p{color:var(--muted);font-size:1.05rem;max-width:58ch;margin-bottom:0}
    .hero-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:24px}.hero-stat{border:1px solid var(--line);background:rgba(255,255,255,.025);padding:13px}.hero-stat b{display:block;font-size:1.25rem;color:var(--cyan);font-weight:800}.hero-stat span{color:var(--faint);font-size:.76rem}
    .hero-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:22px}.hero-actions .btn{width:auto}
    .workflow-rail{display:grid;gap:0;margin-top:28px;border:1px solid var(--line);background:rgba(255,255,255,.02)}.workflow-rail div{display:grid;grid-template-columns:42px 1fr auto;align-items:center;gap:12px;padding:12px 14px;border-bottom:1px solid var(--line)}.workflow-rail div:last-child{border-bottom:0}.workflow-rail b{font-size:.84rem}.workflow-rail span{color:var(--muted);font-size:.78rem}.workflow-rail em{font-style:normal;color:var(--cyan);font-size:.7rem;font-weight:700;text-transform:uppercase}.rail-number{color:var(--gold);font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-weight:800}

    .quote-panel{background:#151c20;border:1px solid rgba(40,216,251,.28);border-radius:var(--r);padding:24px;box-shadow:0 20px 60px rgba(0,0,0,.24)}
    .quote-panel h3{font-size:1.1rem;margin:0 0 16px;display:flex;align-items:center;gap:8px}
    .quote-panel h3 .icon{width:32px;height:32px;border-radius:8px;background:rgba(40,216,251,.16);display:grid;place-items:center;color:var(--cyan)}
    .quote-row{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:12px}
    .quote-row .field{margin-bottom:0}.quote-result{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.15);border-radius:10px;margin-top:16px}.quote-result .price{font-size:1.8rem;font-weight:800;color:#34d399}.quote-result small{color:var(--faint);font-size:.72rem}

    .section{padding:72px 0}.section-alt{background:var(--bg2);border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
    .section-head{display:flex;align-items:end;justify-content:space-between;gap:18px;margin-bottom:36px}.section-head h2{font-size:clamp(1.6rem,3vw,2.2rem);font-weight:700;letter-spacing:-.015em;line-height:1.1;margin:0}.section-head p{color:var(--muted);margin:0;max-width:56ch}

    .cap-grid{grid-template-columns:repeat(4,minmax(0,1fr))}.cap-card{background:var(--glass);border:1px solid var(--line);border-radius:var(--r);padding:24px;backdrop-filter:blur(12px);transition:border-color .2s,transform .2s}.cap-card:hover{border-color:rgba(40,216,251,.5);transform:translateY(-2px)}.cap-card .cap-icon{width:44px;height:44px;border-radius:12px;background:rgba(40,216,251,.12);color:var(--cyan);display:grid;place-items:center;font-weight:800;margin-bottom:16px}.cap-card b{display:block;font-size:1.02rem;margin-bottom:8px}.cap-card p{color:var(--muted);font-size:.86rem;margin:0}

    .work-grid{grid-template-columns:repeat(4,minmax(0,1fr))}.work-step{position:relative;padding:20px 20px 20px 56px;border:1px solid var(--line);border-radius:var(--r);background:var(--glass)}.work-step .step-num{position:absolute;left:14px;top:18px;width:30px;height:30px;display:grid;place-items:center;background:var(--cyan);color:#003640;border-radius:8px;font-weight:900;font-size:.82rem}.work-step b{display:block;margin-bottom:6px}.work-step p{color:var(--muted);font-size:.84rem;margin:0}

    .trust-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.trust-card{text-align:center;padding:28px 18px;background:var(--glass);border:1px solid var(--line);border-radius:var(--r)}.trust-card .trust-icon{width:48px;height:48px;border-radius:14px;background:rgba(40,216,251,.12);color:var(--cyan);display:grid;place-items:center;font-size:1.3rem;font-weight:800;margin:0 auto 14px}.trust-card b{display:block;font-size:.95rem;margin-bottom:6px}.trust-card p{color:var(--muted);font-size:.82rem;margin:0}

    .cta{background:#12191d;border-top:1px solid var(--line);border-bottom:1px solid var(--line)}.cta .wrap{display:flex;align-items:center;justify-content:space-between;gap:28px;padding:52px 0}.cta h2{font-size:clamp(1.8rem,3vw,2.5rem);margin:0 0 8px}.cta p{margin:0;color:var(--muted)}

    @media(max-width:1000px){.hero-grid{grid-template-columns:1fr;gap:28px}.quote-panel{max-width:640px}.cap-grid,.work-grid{grid-template-columns:repeat(2,1fr)}.trust-grid{grid-template-columns:1fr}}
    @media(max-width:620px){.pcb-hero{min-height:auto}.hero-grid{padding:40px 0}.hero-copy h1{font-size:2.2rem}.hero-stats{grid-template-columns:1fr 1fr}.hero-actions{display:grid}.hero-actions .btn{width:100%}.workflow-rail div{grid-template-columns:34px 1fr}.workflow-rail em{display:none}.quote-row{grid-template-columns:1fr}.cap-grid,.work-grid{grid-template-columns:1fr}.cta .wrap{display:block}.cta .wrap .btn{margin-top:16px}.section{padding:52px 0}}
</style>
@endpush

@section('content')
@if(session('status'))<div class="wrap" style="padding-top:16px"><div class="notice">{{ session('status') }}</div></div>@endif

<!-- Hero -->
<section class="pcb-hero" aria-labelledby="pcb-hero-title">
    <div class="wrap">
        <div class="hero-grid">
            <div class="hero-copy">
                <div class="eyebrow">Online PCB quote workspace</div>
                <h1 id="pcb-hero-title">Configure a build-ready board.</h1>
                <p>Start with a transparent fabrication estimate, then move into a private project workspace for Gerber validation, BOM matching, engineering review and production tracking.</p>
                <div class="hero-stats">
                    <div class="hero-stat"><b>1-64</b><span>PCB layers</span></div>
                    <div class="hero-stat"><b>SMT + TH</b><span>Assembly paths</span></div>
                    <div class="hero-stat"><b>15 min</b><span>Private link expiry</span></div>
                </div>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="#quote">Configure quote</a>
                    <a class="btn btn-ghost" href="/en/register">Create private project</a>
                </div>
                <div class="workflow-rail" aria-label="PCB production workflow">
                    <div><span class="rail-number">01</span><span><b>Configure</b><br>Fabrication and assembly inputs</span><em>Now</em></div>
                    <div><span class="rail-number">02</span><span><b>Upload securely</b><br>Gerber, BOM, CPL and documents</span><em>Project</em></div>
                    <div><span class="rail-number">03</span><span><b>Engineering review</b><br>DFM and commercial quote approval</span><em>Review</em></div>
                    <div><span class="rail-number">04</span><span><b>Track production</b><br>Order milestones in one workspace</span><em>Production</em></div>
                </div>
            </div>
            <div class="quote-panel" id="quote">
                <h3><span class="icon">Q</span> Quote configuration</h3>
                <p class="muted" style="font-size:.82rem;margin:0 0 14px">Estimate fabrication and PCBA cost. Engineering confirms specifications only after your private file review.</p>
                <form id="instant-quote" onsubmit="return false">
                    @csrf
                    <div class="quote-row">
                        <div class="field"><label>Layers</label><select class="control" id="q_layers" onchange="calculateQuote()"><option value="1">1 layer</option><option value="2" selected>2 layers</option><option value="4">4 layers</option><option value="6">6 layers</option><option value="8">8 layers</option></select></div>
                        <div class="field"><label>Width (mm)</label><input class="control" id="q_width" type="number" value="100" min="5" max="500" step="1" onchange="calculateQuote()"></div>
                        <div class="field"><label>Height (mm)</label><input class="control" id="q_height" type="number" value="100" min="5" max="500" step="1" onchange="calculateQuote()"></div>
                    </div>
                    <div class="quote-row">
                        <div class="field"><label>Quantity</label><select class="control" id="q_quantity" onchange="calculateQuote()"><option value="5">5</option><option value="10">10</option><option value="25">25</option><option value="50">50</option><option value="100">100</option><option value="250">250</option><option value="500">500</option><option value="1000">1000</option></select></div>
                        <div class="field"><label>Finish</label><select class="control" id="q_finish" onchange="calculateQuote()"><option value="HASL_Lead_Free">Lead-free HASL</option><option value="ENIG">ENIG</option><option value="HASL">HASL</option></select></div>
                        <div class="field"><label>Speed</label><select class="control" id="q_speed" onchange="calculateQuote()"><option value="standard">Standard</option><option value="fast">Fast</option><option value="express">Express</option></select></div>
                    </div>
                    <div class="quote-row">
                        <div class="field"><label>Color</label><select class="control" id="q_color" onchange="calculateQuote()"><option value="green">Green</option><option value="blue">Blue</option><option value="black">Black</option><option value="red">Red</option><option value="white">White</option></select></div>
                        <div class="field"><label>Copper</label><select class="control" id="q_copper" onchange="calculateQuote()"><option value="1">1 oz</option><option value="2">2 oz</option><option value="3">3 oz</option></select></div>
                        <div class="field"><label>Material</label><select class="control" id="q_material" onchange="calculateQuote()"><option value="FR-4">FR-4</option><option value="Flex">Flex</option><option value="Aluminum">Aluminum</option></select></div>
                    </div>
                    <div style="display:flex;gap:16px;margin-bottom:12px;font-size:.82rem;color:var(--muted)">
                        <label class="check" style="flex:1"><input type="checkbox" id="q_impedance" onchange="calculateQuote()"> Controlled impedance</label>
                        <label class="check" style="flex:1"><input type="checkbox" id="q_etest" onchange="calculateQuote()"> Electrical test</label>
                    </div>
                    <details style="margin-bottom:12px"><summary style="color:var(--cyan);font-size:.82rem;font-weight:700;cursor:pointer">+ PCBA Assembly options</summary>
                        <div style="margin-top:10px;display:grid;gap:10px">
                            <div class="quote-row">
                                <div class="field"><label>Assembly</label><select class="control" id="q_assembly" onchange="calculateQuote()"><option value="none">PCB only</option><option value="smt_top">SMT top side</option><option value="smt_both">SMT both sides</option><option value="mixed">Mixed SMT+TH</option></select></div>
                                <div class="field"><label>SMT pads</label><input class="control" id="q_smt_pads" type="number" value="50" min="0" max="10000" onchange="calculateQuote()"></div>
                                <div class="field"><label>TH joints</label><input class="control" id="q_th_joints" type="number" value="0" min="0" max="5000" onchange="calculateQuote()"></div>
                            </div>
                            <div style="display:flex;gap:16px;font-size:.78rem;color:var(--muted);flex-wrap:wrap">
                                <label class="check"><input type="checkbox" id="q_stencil" onchange="calculateQuote()" checked> Stencil</label>
                                <label class="check"><input type="checkbox" id="q_bga" onchange="calculateQuote()"> BGA assembly</label>
                                <label class="check"><input type="checkbox" id="q_coating" onchange="calculateQuote()"> Conformal coating</label>
                            </div>
                        </div>
                    </details>
                </form>
                <div id="quote-result" style="display:none">
                    <table class="table" style="margin-bottom:12px"><tbody>
                        <tr><td style="color:var(--faint)">Tier</td><td id="qr_tier" style="text-align:right;font-weight:600">—</td></tr>
                        <tr><td style="color:var(--faint)">Board area</td><td id="qr_area" style="text-align:right">—</td></tr>
                        <tr><td style="color:var(--faint)">Fab unit price</td><td id="qr_unit" style="text-align:right;font-weight:600">—</td></tr>
                        <tr><td style="color:var(--faint)">Fabrication total</td><td id="qr_fab" style="text-align:right;font-weight:600">—</td></tr>
                        <tr><td style="color:var(--faint)">Setup</td><td id="qr_setup" style="text-align:right">—</td></tr>
                        <tr><td style="color:var(--faint)">Engineering</td><td id="qr_eng" style="text-align:right">—</td></tr>
                        <tr style="display:none" id="qr_assembly_row"><td style="color:var(--cyan)">Assembly (PCBA)</td><td id="qr_assembly" style="text-align:right;color:var(--cyan);font-weight:600">—</td></tr>
                        <tr style="display:none" id="qr_stencil_row"><td style="color:var(--faint)">Stencil</td><td id="qr_stencil" style="text-align:right">—</td></tr>
                    </tbody></table>
                    <div class="quote-result">
                        <div><small>Estimated total (USD)</small><div class="price" id="quote-price">—</div></div>
                        <div style="text-align:right"><small>Lead time</small><div style="font-weight:700" id="quote-lead">—</div></div>
                    </div>
                </div>
                <p style="font-size:.72rem;color:var(--faint);margin:12px 0 0">Pricing is an estimate. Final quote requires Gerber file upload and engineering review. No automatic charge.</p>
            </div>
        </div>
    </div>
</section>

<script>
    async function calculateQuote() {
        const els = (id) => document.getElementById(id);
        const result = els('quote-result');
        const setText = (id, v) => { const e = els(id); if(e) e.textContent = v; };

        result.style.display = 'block';
        ['qr_tier','qr_area','qr_unit','qr_fab','qr_setup','qr_eng','quote-price','quote-lead'].forEach(id => setText(id, '...'));

        try {
            const resp = await fetch('/api/v1/quote/calculate', {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
                body: JSON.stringify({
                    layers: parseInt(els('q_layers').value),
                    width_mm: parseFloat(els('q_width').value),
                    height_mm: parseFloat(els('q_height').value),
                    quantity: parseInt(els('q_quantity').value),
                    surface_finish: els('q_finish').value,
                    solder_mask_color: els('q_color').value,
                    outer_copper_oz: els('q_copper').value,
                    board_material: els('q_material').value,
                    production_speed: els('q_speed').value,
                    impedance_control: els('q_impedance').checked,
                    electrical_test: els('q_etest').checked,
                    assembly_service: els('q_assembly').value,
                    smt_pads_per_board: parseInt(els('q_smt_pads').value) || 0,
                    through_hole_joints_per_board: parseInt(els('q_th_joints').value) || 0,
                    stencil_service: els('q_stencil').checked,
                    bga_assembly: els('q_bga').checked,
                    conformal_coating: els('q_coating').checked,
                })
            });

            if (resp.ok) {
                const d = (await resp.json()).data;
                if (d) {
                    setText('qr_tier', d.tier || 'Standard');
                    setText('qr_area', d.board_area_cm2 + ' cm²');
                    setText('qr_unit', '$' + d.fabrication_unit_price.toFixed(2) + ' / board');
                    setText('qr_fab', '$' + d.fabrication_total.toFixed(2));
                    setText('qr_setup', '$' + d.setup_fee.toFixed(2));
                    setText('qr_eng', '$' + (d.engineering_fee || 0).toFixed(2));
                    // Assembly
                    const asmRow = els('qr_assembly_row'), stnRow = els('qr_stencil_row');
                    if (d.assembly_cost > 0) {
                        asmRow.style.display = ''; setText('qr_assembly', '$' + d.assembly_cost.toFixed(2));
                        if (d.stencil_cost > 0) { stnRow.style.display = ''; setText('qr_stencil', '$' + d.stencil_cost.toFixed(2)); }
                        else stnRow.style.display = 'none';
                    } else { asmRow.style.display = 'none'; stnRow.style.display = 'none'; }
                    setText('quote-price', '$' + d.estimated_total.toFixed(2));
                    setText('quote-lead', d.lead_time_days + ' days');
                    return;
                }
            }
        } catch (e) {}

        ['qr_tier','qr_area','qr_unit','qr_fab','qr_setup','qr_eng','quote-price','quote-lead'].forEach(id => setText(id, '—'));
    }
    document.addEventListener('DOMContentLoaded', () => calculateQuote());
</script>

<!-- Capabilities -->
<section class="section" aria-labelledby="capabilities-title">
    <div class="wrap">
        <div class="section-head"><div><div class="eyebrow">Manufacturing</div><h2 id="capabilities-title">PCB fabrication capabilities</h2><p>Board types, materials, finishes and specifications confirmed during engineering review.</p></div><a class="btn btn-ghost" href="/en/capabilities">Full specs</a></div>
        <div class="grid cap-grid">
            <article class="cap-card"><div class="cap-icon">R</div><b>Rigid PCB</b><p>FR-4, 1-64 layers. Standard and high-Tg laminates. 0.4-5.0mm thickness.</p></article>
            <article class="cap-card"><div class="cap-icon">A</div><b>SMT Assembly</b><p>Full SMT and through-hole PCBA. BGA, 0201, lead-free. 24h turnaround.</p></article>
            <article class="cap-card"><div class="cap-icon">F</div><b>Flex & Rigid-Flex</b><p>Polyimide flexible circuits. Rigid-flex multilayer with stiffeners.</p></article>
            <article class="cap-card"><div class="cap-icon">S</div><b>Component Sourcing</b><p>NeoGiga catalog + global distributor network. MPN matching. BOM management.</p></article>
        </div>
    </div>
</section>

<!-- Workflow -->
<section class="section-alt section" aria-labelledby="workflow-title">
    <div class="wrap">
        <div class="section-head"><div><div class="eyebrow">Workflow</div><h2 id="workflow-title">Secure path to production</h2><p>Every upload, review, quote and approval is recorded against the project.</p></div><a class="btn btn-ghost" href="/en/register">Start now</a></div>
        <div class="grid work-grid">
            <article class="work-step"><span class="step-num">1</span><b>Create project</b><p>Set quantity, delivery destination, confidentiality and target date.</p></article>
            <article class="work-step"><span class="step-num">2</span><b>Upload design files</b><p>Private Gerber ZIP, BOM, CPL and engineering documents.</p></article>
            <article class="work-step"><span class="step-num">3</span><b>Review quote</b><p>Receive pricing and lead time only after engineering file review.</p></article>
            <article class="work-step"><span class="step-num">4</span><b>Track production</b><p>Approve the quote and track through the manufacturing workflow.</p></article>
        </div>
    </div>
</section>

<!-- Trust -->
<section class="section" aria-labelledby="trust-title">
    <div class="wrap">
        <div class="section-head"><div><div class="eyebrow">Platform</div><h2 id="trust-title">Private, secure, reviewed</h2><p>No public file links. No automatic charge. Commercial approval remains explicit.</p></div></div>
        <div class="grid trust-grid">
            <div class="trust-card"><div class="trust-icon">🔒</div><b>Private file storage</b><p>Design files stored in private buckets. Signed download links expire after 15 minutes.</p></div>
            <div class="trust-card"><div class="trust-icon">⚙️</div><b>Engineering-reviewed</b><p>Every quote requires manual engineering review before pricing is issued.</p></div>
            <div class="trust-card"><div class="trust-icon">📋</div><b>Full audit trail</b><p>File access, quote changes, status transitions — all recorded in the project log.</p></div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta">
    <div class="wrap">
        <div><h2>Start with your board requirements.</h2><p>Instant pricing estimate, secure file upload, engineering-reviewed quote.</p></div>
        <a class="btn btn-gold" href="/en/register">Create secure PCB project</a>
    </div>
</section>
@endsection
