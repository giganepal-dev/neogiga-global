@extends('pcb.layout')
@php $c = config('pcb-capabilities'); @endphp

@section('title', 'PCB Manufacturing Capabilities — NeoGiga PCB')
@section('description', 'Detailed PCB fabrication specifications: layer counts, materials, tolerances, surface finishes, copper weights, drill specs, and quality standards.')

@push('styles')
<style>
    .specs-section{margin-bottom:36px}.specs-section h2{font-size:1.25rem;margin:0 0 14px;padding-bottom:8px;border-bottom:1px solid var(--line)}
    .specs-table{width:100%;border-collapse:collapse}.specs-table th,.specs-table td{text-align:left;padding:9px 15px;border-bottom:1px solid var(--line);font-size:.86rem}.specs-table th{background:var(--s1);color:var(--faint);font-size:.71rem;text-transform:uppercase;font-weight:700;width:38%}.specs-table td{color:var(--on)}
    .specs-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:24px}
    .cap-chip{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:8px;font-size:.78rem;font-weight:600}.cap-yes{background:rgba(16,185,129,.12);color:#34d399}.cap-review{background:rgba(249,189,44,.12);color:var(--gold)}
    .lead-card{text-align:center;padding:20px;background:var(--glass);border:1px solid var(--line);border-radius:var(--r)}.lead-card .days{font-size:2rem;font-weight:800;color:var(--cyan)}.lead-card .label{color:var(--muted);font-size:.82rem;margin-top:4px}
</style>
@endpush

@section('content')
<section style="padding:28px 0 64px">
    <div class="wrap">
        <nav class="crumbs"><a href="/en">PCB Platform</a><span>/</span><span>Capabilities</span></nav>
        <header style="margin-bottom:28px">
            <div class="eyebrow">Manufacturing specifications</div>
            <h1 class="page-title" style="margin:5px 0 6px">PCB fabrication capabilities</h1>
            <p class="lead">Engineering-reviewed manufacturing. All specifications confirmed during quote review. Final capability, price, and lead time are subject to Gerber file analysis.</p>
        </header>

        <!-- Board types -->
        <div class="specs-section"><h2>Board types</h2>
            <div class="table-wrap"><table class="specs-table">
                <thead><tr><th>Type</th><th>Layers</th><th>Description</th></tr></thead>
                <tbody>@foreach($c['board_types'] as $bt)<tr><td style="font-weight:700">{{ $bt['label'] }}</td><td>{{ $bt['layers'] }}</td><td style="color:var(--muted)">{{ $bt['description'] }}</td></tr>@endforeach</tbody>
            </table></div>
        </div>

        <!-- Dimensions + Materials -->
        <div class="specs-grid">
            <div class="specs-section"><h2>Dimensions</h2><table class="specs-table">
                <tr><th>Min board size</th><td>{{ $c['dimensions']['min_board_size_mm'] }} mm</td></tr>
                <tr><th>Max board size</th><td>{{ $c['dimensions']['max_board_size_mm'] }} mm</td></tr>
                <tr><th>Thickness options</th><td>{{ implode(', ', $c['dimensions']['thickness_options_mm']) }} mm</td></tr>
                <tr><th>Thickness tolerance</th><td>{{ $c['dimensions']['thickness_tolerance'] }}</td></tr>
                <tr><th>Dimension tolerance</th><td>{{ $c['dimensions']['dimension_tolerance'] }}</td></tr>
                <tr><th style="color:var(--faint)">Note</th><td style="color:var(--muted)">{{ $c['dimensions']['max_board_size_note'] }}</td></tr>
            </table></div>

            <div class="specs-section"><h2>Materials</h2><table class="specs-table">
                <thead><tr><th>Material</th><th>Tg</th></tr></thead>
                <tbody>@foreach($c['materials'] as $m)<tr><td style="font-weight:600">{{ $m['name'] }}</td><td>{{ $m['tg'] }}</td></tr>@endforeach</tbody>
            </table></div>
        </div>

        <!-- Copper -->
        <div class="specs-section"><h2>Copper weights & trace/spacing</h2>
            <div class="table-wrap"><table class="specs-table">
                <thead><tr><th>Copper</th><th>Min trace</th><th>Min spacing</th><th>Location</th></tr></thead>
                <tbody>
                @foreach($c['copper']['min_trace_spacing'] as $oz => $spec)
                    <tr><td style="font-weight:600">{{ $oz }}</td><td>{{ $spec['trace_mm'] }} mm ({{ $spec['trace_mil'] }} mil)</td><td>{{ $spec['spacing_mm'] }} mm ({{ $spec['spacing_mil'] }} mil)</td><td>Outer</td></tr>
                @endforeach
                    <tr><td style="font-weight:600">0.5 oz</td><td>{{ $c['copper']['inner_trace_spacing']['trace_mm'] }} mm</td><td>{{ $c['copper']['inner_trace_spacing']['spacing_mm'] }} mm</td><td>Inner</td></tr>
                    <tr><th colspan="4">Available weights: Outer — {{ implode(', ', $c['copper']['outer_weights']) }} | Inner — {{ implode(', ', $c['copper']['inner_weights']) }} | Trace tolerance: {{ $c['copper']['trace_tolerance'] }}</td></tr>
                </tbody>
            </table></div>
        </div>

        <!-- Surface finishes -->
        <div class="specs-section"><h2>Surface finishes</h2>
            <div class="table-wrap"><table class="specs-table">
                <thead><tr><th>Finish</th><th>Notes</th></tr></thead>
                <tbody>@foreach($c['surface_finishes'] as $sf)<tr><td style="font-weight:600">{{ $sf['label'] }}</td><td style="color:var(--muted)">{{ $sf['note'] }}</td></tr>@endforeach</tbody>
            </table></div>
        </div>

        <!-- Solder mask + Silkscreen -->
        <div class="specs-grid">
            <div class="specs-section"><h2>Solder mask</h2><table class="specs-table">
                <tr><th>Type</th><td>{{ $c['solder_mask']['type'] }}</td></tr>
                <tr><th>Thickness</th><td>{{ $c['solder_mask']['thickness'] }}</td></tr>
                <tr><th>Colors</th><td>{{ implode(', ', $c['solder_mask']['colors']) }}</td></tr>
                <tr><th>Min bridge</th><td>@foreach($c['solder_mask']['bridge_min_mm'] as $color => $mm){{ $color }}: {{ $mm }}mm<br>@endforeach</td></tr>
            </table></div>

            <div class="specs-section"><h2>Silkscreen</h2><table class="specs-table">
                <tr><th>Colors</th><td>{{ implode(', ', $c['silkscreen']['colors']) }}</td></tr>
                <tr><th>Min line width</th><td>{{ $c['silkscreen']['min_line_width_mm'] }} mm</td></tr>
                <tr><th>Min text height</th><td>{{ $c['silkscreen']['min_text_height_mm'] }} mm</td></tr>
                <tr><th>Character ratio</th><td>{{ $c['silkscreen']['character_ratio'] }}</td></tr>
            </table></div>
        </div>

        <!-- Drilling -->
        <div class="specs-section"><h2>Drilling & vias</h2>
            <div class="table-wrap"><table class="specs-table">
                <tr><th>Min drill (multilayer)</th><td>{{ $c['drilling']['min_drill_mm']['multilayer'] }} mm</td></tr>
                <tr><th>Min drill (single layer)</th><td>{{ $c['drilling']['min_drill_mm']['single_layer'] }} mm</td></tr>
                <tr><th>Max drill</th><td>{{ $c['drilling']['max_drill_mm'] }} mm</td></tr>
                <tr><th>PTH tolerance</th><td>{{ $c['drilling']['hole_tolerance_pth'] }}</td></tr>
                <tr><th>NPTH tolerance</th><td>{{ $c['drilling']['hole_tolerance_npth'] }}</td></tr>
                <tr><th>Position tolerance</th><td>{{ $c['drilling']['position_tolerance'] }}</td></tr>
                <tr><th>Via types</th><td>{{ implode(', ', $c['drilling']['via_types']) }}</td></tr>
                <tr><th>Min castellated hole</th><td>{{ $c['drilling']['min_castellated_hole_mm'] }} mm</td></tr>
                <tr><th>Blind/buried vias</th><td>{{ $c['drilling']['blind_buried_vias'] }}</td></tr>
            </table></div>
        </div>

        <!-- Advanced processes -->
        <div class="specs-section"><h2>Advanced processes</h2>
            <div class="table-wrap"><table class="specs-table">
                <thead><tr><th>Process</th><th>Description</th></tr></thead>
                <tbody>@foreach($c['advanced'] as $adv)<tr><td style="font-weight:600">{{ $adv['name'] }} <span class="cap-chip {{ $adv['available'] ? 'cap-yes' : 'cap-review' }}">{{ $adv['available'] ? 'Available' : 'By review' }}</span></td><td style="color:var(--muted)">{{ $adv['description'] }}</td></tr>@endforeach</tbody>
            </table></div>
        </div>

        <!-- Testing -->
        <div class="specs-section"><h2>Testing & quality</h2>
            <div class="table-wrap"><table class="specs-table">
                <thead><tr><th>Test</th><th>Description</th><th>Default</th></tr></thead>
                <tbody>@foreach($c['testing'] as $t)<tr><td style="font-weight:600">{{ $t['name'] }}</td><td style="color:var(--muted)">{{ $t['description'] }}</td><td>@if($t['default'])<span class="cap-chip cap-yes">Standard</span>@else<span class="cap-chip cap-review">Optional</span>@endif</td></tr>@endforeach</tbody>
            </table></div>
            <div style="margin-top:14px;color:var(--muted);font-size:.86rem">
                <b style="color:var(--on)">Inspection:</b> {{ $c['quality']['inspection_standard'] }} |
                <b style="color:var(--on)">Certification:</b> {{ $c['quality']['quality_certification'] }} |
                <b style="color:var(--on)">Quality rate:</b> {{ $c['quality']['quality_rate'] }}
            </div>
        </div>

        <!-- PCBA / Assembly -->
        <div class="specs-section"><h2>PCB Assembly (PCBA)</h2>
            <div class="specs-grid">
                <div><h3 style="font-size:.95rem;margin:0 0 10px;color:var(--muted)">Assembly types</h3><table class="specs-table">
                    <thead><tr><th>Type</th><th>Description</th></tr></thead>
                    <tbody>@foreach($c['pcba']['assembly_types'] as $at)<tr><td style="font-weight:600">{{ $at['label'] }}</td><td style="color:var(--muted)">{{ $at['description'] }}</td></tr>@endforeach</tbody>
                </table></div>
                <div><h3 style="font-size:.95rem;margin:0 0 10px;color:var(--muted)">Component sourcing</h3><table class="specs-table">
                    <tbody>@foreach($c['pcba']['component_sourcing'] as $cs)<tr><td style="font-weight:600">{{ $cs['label'] }}</td><td style="color:var(--muted)">{{ $cs['description'] }}</td></tr>@endforeach</tbody>
                </table></div>
            </div>
            <div class="specs-grid" style="margin-top:12px">
                <div><h3 style="font-size:.95rem;margin:0 0 10px;color:var(--muted)">Stencil options</h3><table class="specs-table">
                    <tbody>@foreach($c['pcba']['stencil_options'] as $st)<tr><td style="font-weight:600">{{ $st['label'] }}</td><td style="color:var(--muted)">{{ $st['description'] }}</td></tr>@endforeach</tbody>
                </table></div>
                <div><h3 style="font-size:.95rem;margin:0 0 10px;color:var(--muted)">Testing</h3><table class="specs-table">
                    <thead><tr><th>Type</th><th>Default</th></tr></thead>
                    <tbody>@foreach($c['pcba']['testing_options'] as $to)<tr><td style="font-weight:600">{{ $to['label'] }}</td><td>@if($to['default'])<span class="cap-chip cap-yes">Standard</span>@else<span class="cap-chip cap-review">Optional</span>@endif</td></tr>@endforeach</tbody>
                </table></div>
            </div>
            <div style="margin-top:12px;color:var(--muted);font-size:.86rem">
                <b style="color:var(--on)">Min component:</b> {{ $c['pcba']['component_limits']['min_size'] }} |
                <b style="color:var(--on)">BGA:</b> {{ $c['pcba']['component_limits']['bga_supported'] ? 'Supported' : 'By review' }} |
                <b style="color:var(--on)">Lead-free:</b> {{ $c['pcba']['component_limits']['lead_free'] ? 'Standard' : 'By request' }}
            </div>
            <div style="margin-top:6px;color:var(--muted);font-size:.84rem">
                <b style="color:var(--on)">Turnaround:</b> {{ $c['pcba']['turnaround']['standard'] }} (standard) |
                {{ $c['pcba']['turnaround']['express'] }} (express)
            </div>
            <div class="notice" style="margin-top:10px;font-size:.84rem">{{ $c['pcba']['component_limits']['note'] }}</div>
        </div>

        <!-- Lead times -->
        <div class="specs-section"><h2>Lead times</h2>
            <div class="grid" style="grid-template-columns:repeat(3,minmax(0,1fr))">
                @foreach($c['lead_times'] as $key => $lt)
                    <div class="lead-card"><div class="days">{{ $lt['min_days'] }}-{{ $lt['max_days'] }}d</div><div class="label">{{ ucfirst($key) }} — {{ $lt['description'] }}</div></div>
                @endforeach
            </div>
        </div>

        <div class="notice" style="margin-top:28px">
            <b style="color:var(--cyan)">Engineering review required.</b> All specifications are confirmed during the quote process after Gerber file analysis. Contact <a href="mailto:pcb@neogiga.com" style="color:var(--cyan);font-weight:700">pcb@neogiga.com</a> for capabilities not listed here.
        </div>
    </div>
</section>
@endsection
