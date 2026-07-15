@extends('pcb.layout')

@section('title', 'PCB Design Rules — NeoGiga PCB')
@section('description', 'Design rule recommendations for PCB fabrication: trace width, spacing, annular ring, solder mask expansion, silkscreen, drill specs, and panelization guidelines.')

@section('content')
<section style="padding:28px 0 64px">
    <div class="wrap">
        <nav class="crumbs"><a href="/en">PCB Platform</a><span>/</span><span>Design rules</span></nav>
        <header style="margin-bottom:28px"><div class="eyebrow">Design guidelines</div><h1 class="page-title" style="margin:5px 0 6px">PCB design rules</h1><p class="lead">Recommended design parameters for reliable fabrication. Values are confirmed during engineering review.</p></header>

        <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(340px,1fr))">
            <div class="card"><div class="card-body">
                <h2 style="font-size:1.1rem;margin:0 0 14px">Trace width & spacing</h2>
                <table class="table"><tbody>
                    <tr><td style="color:var(--faint);width:50%">1 oz outer, min trace</td><td>0.10 mm (4 mil)</td></tr>
                    <tr><td style="color:var(--faint)">1 oz outer, min spacing</td><td>0.10 mm (4 mil)</td></tr>
                    <tr><td style="color:var(--faint)">2 oz outer, min trace</td><td>0.16 mm (6.5 mil)</td></tr>
                    <tr><td style="color:var(--faint)">2 oz outer, min spacing</td><td>0.16 mm (6.5 mil)</td></tr>
                    <tr><td style="color:var(--faint)">0.5 oz inner, min trace</td><td>0.09 mm (3.5 mil)</td></tr>
                    <tr><td style="color:var(--faint)">Trace width tolerance</td><td>±20%</td></tr>
                </tbody></table>
            </div></div>

            <div class="card"><div class="card-body">
                <h2 style="font-size:1.1rem;margin:0 0 14px">Annular ring & drill</h2>
                <table class="table"><tbody>
                    <tr><td style="color:var(--faint);width:50%">Min annular ring (outer)</td><td>0.13 mm</td></tr>
                    <tr><td style="color:var(--faint)">Min annular ring (inner)</td><td>0.15 mm</td></tr>
                    <tr><td style="color:var(--faint)">Min drill-to-copper</td><td>0.25 mm</td></tr>
                    <tr><td style="color:var(--faint)">Drill hole tolerance (PTH)</td><td>+0.13 / -0.08 mm</td></tr>
                    <tr><td style="color:var(--faint)">Drill hole tolerance (NPTH)</td><td>±0.05 mm</td></tr>
                    <tr><td style="color:var(--faint)">Min castellated hole</td><td>0.50 mm</td></tr>
                </tbody></table>
            </div></div>

            <div class="card"><div class="card-body">
                <h2 style="font-size:1.1rem;margin:0 0 14px">Solder mask & silkscreen</h2>
                <table class="table"><tbody>
                    <tr><td style="color:var(--faint);width:50%">Mask expansion</td><td>0.05 mm per side</td></tr>
                    <tr><td style="color:var(--faint)">Min mask bridge</td><td>0.10 mm (green)</td></tr>
                    <tr><td style="color:var(--faint)">Mask bridge (black/white)</td><td>0.13 mm</td></tr>
                    <tr><td style="color:var(--faint)">Min silkscreen line</td><td>0.15 mm</td></tr>
                    <tr><td style="color:var(--faint)">Min text height</td><td>1.0 mm (40 mil)</td></tr>
                    <tr><td style="color:var(--faint)">Character width ratio</td><td>1:6 or greater</td></tr>
                </tbody></table>
            </div></div>

            <div class="card"><div class="card-body">
                <h2 style="font-size:1.1rem;margin:0 0 14px">Board outline & routing</h2>
                <table class="table"><tbody>
                    <tr><td style="color:var(--faint);width:50%">Outline layer</td><td>Required (GKO / GM1)</td></tr>
                    <tr><td style="color:var(--faint)">Route tool diameter</td><td>1.6 mm (standard), 0.8 mm (fine)</td></tr>
                    <tr><td style="color:var(--faint)">Board edge clearance</td><td>≥0.3 mm copper to edge</td></tr>
                    <tr><td style="color:var(--faint)">V-score</td><td>1/3 thickness remaining</td></tr>
                    <tr><td style="color:var(--faint)">Mouse bites</td><td>5 holes, 0.5 mm, 0.8 mm pitch</td></tr>
                    <tr><td style="color:var(--faint)">Dimension tolerance</td><td>±0.15 mm</td></tr>
                </tbody></table>
            </div></div>

            <div class="card"><div class="card-body">
                <h2 style="font-size:1.1rem;margin:0 0 14px">Impedance & stackup</h2>
                <table class="table"><tbody>
                    <tr><td style="color:var(--faint);width:50%">Impedance tolerance</td><td>±10%</td></tr>
                    <tr><td style="color:var(--faint)">Dielectric constant</td><td>4.2-4.6 (FR-4)</td></tr>
                    <tr><td style="color:var(--faint)">Min prepreg thickness</td><td>0.10 mm</td></tr>
                    <tr><td style="color:var(--faint)">Min core thickness</td><td>0.10 mm</td></tr>
                    <tr><td style="color:var(--faint)">2-layer stackup</td><td>1.6mm: copper / core / copper</td></tr>
                    <tr><td style="color:var(--faint)">4-layer stackup</td><td>Signal / GND / PWR / Signal</td></tr>
                </tbody></table>
            </div></div>

            <div class="card"><div class="card-body">
                <h2 style="font-size:1.1rem;margin:0 0 14px">Panelization guidelines</h2>
                <table class="table"><tbody>
                    <tr><td style="color:var(--faint);width:50%">Min panel border</td><td>10 mm per side</td></tr>
                    <tr><td style="color:var(--faint)">Fiducial marks</td><td>1.0 mm dia, 3 per panel</td></tr>
                    <tr><td style="color:var(--faint)">Tooling holes</td><td>3.2 mm dia, 4 corners</td></tr>
                    <tr><td style="color:var(--faint)">V-score angle</td><td>30°</td></tr>
                    <tr><td style="color:var(--faint)">Tab routing</td><td>1.5 mm tab width, 5 tabs per side</td></tr>
                    <tr><td style="color:var(--faint)">Max panel size</td><td>450 × 350 mm</td></tr>
                </tbody></table>
            </div></div>
        </div>

        <div style="margin-top:28px;display:flex;gap:12px;flex-wrap:wrap">
            <a class="btn btn-primary" href="/en/register">Start PCB project</a>
            <a class="btn btn-ghost" href="/en/capabilities">View capabilities</a>
        </div>

        <div class="card" style="margin-top:28px"><div class="card-head"><h2>File preparation checklist</h2></div><div class="card-body">
            <div style="display:grid;gap:10px;color:var(--muted);font-size:.88rem">
                <div>✓ Gerber files: RS-274X format. Include all layers (copper, mask, silkscreen, outline, drill).</div>
                <div>✓ Drill file: Excellon format (.drl or .txt). Include tool list with sizes.</div>
                <div>✓ Board outline: Include on a dedicated mechanical layer or the Gerber outline layer.</div>
                <div>✓ BOM: CSV or Excel format. Include reference designator, MPN, manufacturer, quantity, footprint.</div>
                <div>✓ CPL (Pick &amp; Place): CSV format. Include designator, X/Y in mm, rotation, side (top/bottom).</div>
                <div>✓ Schematic: PDF format for engineering review reference.</div>
                <div>✓ ZIP all files: Single archive, max {{ config('pcb.max_file_size_mb', 100) }} MB.</div>
                <div>✓ Naming: Use consistent layer naming — .GTL (top copper), .GBL (bottom copper), .GTS (top mask), .GBS (bottom mask), .GTO (top silkscreen), .GBO (bottom silkscreen), .GKO (outline), .TXT (drill).</div>
            </div>
        </div></div>
    </div>
</section>
@endsection
