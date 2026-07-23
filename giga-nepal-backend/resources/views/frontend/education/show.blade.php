@extends('frontend.layout')

@section('title', $project->title . ' - NeoGiga STEM Education')
@section('description', $project->summary)

@push('head')
<style nonce="{{ $csp_nonce ?? '' }}">
.proj-hero{padding:40px 0 24px;background:linear-gradient(135deg,#e9f1fd,#f8fafd 55%,#fff);border-bottom:1px solid var(--line)}
.proj-hero h1{font-size:clamp(1.6rem,4vw,2.4rem);font-weight:800;margin:8px 0 12px}
.proj-badges{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
.proj-badges span{font-size:.72rem;font-weight:700;padding:4px 10px;border-radius:999px;text-transform:uppercase}
.proj-grid{display:grid;grid-template-columns:1fr 380px;gap:28px;margin-top:28px}
.proj-sidebar{position:sticky;top:100px;align-self:start}
.proj-card{background:#fff;border:1px solid var(--line);border-radius:14px;padding:20px;margin-bottom:16px}
.proj-card h3{font-size:1rem;font-weight:700;margin:0 0 12px}
.proj-stat{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem}
.proj-stat:last-child{border-bottom:none}
.proj-stat .label{color:var(--muted)}
.proj-stat .value{font-weight:700}
.proj-actions{display:flex;flex-direction:column;gap:8px;margin-top:16px}
.proj-actions .btn{width:100%;justify-content:center}
.proj-section{background:#fff;border:1px solid var(--line);border-radius:14px;padding:24px;margin-bottom:20px}
.proj-section h2{font-size:1.25rem;font-weight:800;margin:0 0 16px;padding-bottom:12px;border-bottom:1px solid var(--line)}
.proj-section h3{font-size:1rem;font-weight:700;margin:16px 0 8px}
.proj-section p,.proj-section li{color:var(--muted);font-size:.92rem;line-height:1.6}
.proj-section ul{padding-left:20px;margin:8px 0}
.proj-section ul li{margin-bottom:4px}
.bom-table{width:100%;border-collapse:collapse;font-size:.85rem}
.bom-table th{text-align:left;padding:10px 12px;background:var(--s2);border-bottom:2px solid var(--line);font-weight:700;color:var(--muted);font-size:.78rem;text-transform:uppercase;letter-spacing:.04em}
.bom-table td{padding:10px 12px;border-bottom:1px solid var(--line);vertical-align:middle}
.bom-table tr:hover{background:#f8fafc}
.bom-table .mpn{font-family:ui-monospace,monospace;font-weight:600;color:var(--on)}
.bom-table .price{font-weight:700;color:var(--cyan)}
.bom-table .stock-badge{font-size:.72rem;padding:2px 8px;border-radius:999px;font-weight:600}
.stock-in{background:#dcfce7;color:#166534}
.stock-out{background:#fee2e2;color:#991b1b}
.bom-summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin:16px 0;padding:16px;background:var(--s2);border-radius:10px}
.bom-summary .stat{text-align:center}
.bom-summary .stat .num{font-size:1.4rem;font-weight:800;color:var(--cyan)}
.bom-summary .stat .lbl{font-size:.76rem;color:var(--muted)}
.code-block{background:#1e293b;color:#e2e8f0;border-radius:10px;padding:16px;font-family:ui-monospace,monospace;font-size:.82rem;overflow-x:auto;white-space:pre;line-height:1.5}
.code-header{display:flex;justify-content:space-between;align-items:center;padding:8px 16px;background:#0f172a;border-radius:10px 10px 0 0;color:#94a3b8;font-size:.8rem}
.code-header button{background:rgba(255,255,255,.1);border:0;color:#e2e8f0;padding:4px 10px;border-radius:6px;cursor:pointer;font-size:.78rem}
.step-list{counter-reset:step}
.step-list li{counter-increment:step;padding:12px 12px 12px 48px;position:relative;border-bottom:1px solid var(--line)}
.step-list li::before{content:counter(step);position:absolute;left:0;top:12px;width:32px;height:32px;background:var(--cyan);color:#fff;border-radius:50%;display:grid;place-items:center;font-weight:700;font-size:.85rem}
.tab-nav{display:flex;gap:4px;border-bottom:2px solid var(--line);margin-bottom:16px}
.tab-nav button{padding:10px 16px;border:0;background:none;color:var(--muted);font-weight:600;font-size:.88rem;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;transition:.15s}
.tab-nav button.active{color:var(--cyan);border-bottom-color:var(--cyan)}
.tab-panel{display:none}.tab-panel.active{display:block}
@media(max-width:900px){.proj-grid{grid-template-columns:1fr}.proj-sidebar{position:static}}
</style>
@endpush

@section('content')
<section class="proj-hero">
    <div class="wrap">
        <div class="proj-badges">
            <span class="badge-level" style="background:#dbeafe;color:#1d4ed8">{{ $project->skill_level }}</span>
            @if($project->main_controller)
                <span class="badge-controller" style="background:#dcfce7;color:#166534">{{ $project->main_controller }}</span>
            @endif
            @if($project->is_featured)
                <span class="badge-featured" style="background:#fef3c7;color:#92400e">Featured</span>
            @endif
            <span style="background:#f3e8ff;color:#6b21a8">{{ ucfirst($project->category) }}</span>
        </div>
        <h1>{{ $project->title }}</h1>
        <p class="lead">{{ $project->summary }}</p>
    </div>
</section>

<div class="wrap">
    <div class="proj-grid">
        <div class="proj-main">
            <div class="proj-section">
                <div class="tab-nav">
                    <button class="active" onclick="showTab('bom')">BOM</button>
                    <button onclick="showTab('code')">Code</button>
                    <button onclick="showTab('wiring')">Wiring</button>
                    <button onclick="showTab('assembly')">Assembly</button>
                    <button onclick="showTab('testing')">Testing</button>
                </div>

                <div class="tab-panel active" id="tab-bom">
                    <h2>Bill of Materials</h2>
                    <div class="bom-summary">
                        <div class="stat"><div class="num">{{ $bom['total_lines'] }}</div><div class="lbl">Components</div></div>
                        <div class="stat"><div class="num">{{ $bom['currency'] }} {{ number_format($bom['total_cost'], 2) }}</div><div class="lbl">Estimated Cost</div></div>
                        <div class="stat"><div class="num">{{ $bom['coverage_pct'] }}%</div><div class="lbl">Catalog Match</div></div>
                        <div class="stat"><div class="num">{{ $bom['required_lines'] }}</div><div class="lbl">Required Parts</div></div>
                    </div>
                    <table class="bom-table">
                        <thead><tr><th>#</th><th>Component</th><th>MPN</th><th>Qty</th><th>Price</th><th>Stock</th></tr></thead>
                        <tbody>
                            @foreach($bom['lines'] as $line)
                            <tr>
                                <td>{{ $line['line_no'] }}</td>
                                <td>
                                    <strong>{{ $line['component_role'] ?? $line['product_name'] ?? 'Unknown' }}</strong>
                                    @if(!$line['is_required'])<span style="color:var(--faint);font-size:.78rem"> (optional)</span>@endif
                                </td>
                                <td class="mpn">{{ $line['product_mpn'] ?? $line['preferred_mpn'] ?? '-' }}</td>
                                <td>{{ $line['quantity'] }}</td>
                                <td class="price">{{ $line['unit_price'] ? $bom['currency'] . ' ' . number_format($line['extended_price'] ?? 0, 2) : '-' }}</td>
                                <td>
                                    @if($line['in_local_stock'])
                                        <span class="stock-badge stock-in">In Stock</span>
                                    @elseif($line['in_global_stock'])
                                        <span class="stock-badge stock-in">Global</span>
                                    @else
                                        <span class="stock-badge stock-out">Unavailable</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="tab-panel" id="tab-code">
                    <h2>Source Code</h2>
                    @forelse($codeFiles as $code)
                        <div class="code-header">
                            <span>{{ $code['title'] }} ({{ $code['language'] }}) - {{ $code['target_board'] ?? 'Generic' }}</span>
                            <button onclick="copyCode(this)">Copy</button>
                        </div>
                        <pre class="code-block">{{ $code['source_code'] }}</pre>
                        @if($code['build_instructions'])
                            <h3>Build Instructions</h3>
                            <p>{{ $code['build_instructions'] }}</p>
                        @endif
                    @empty
                        <p class="muted">No code files available for this project yet.</p>
                    @endforelse
                </div>

                <div class="tab-panel" id="tab-wiring">
                    <h2>Wiring Instructions</h2>
                    @if($project->wiring_instructions)
                        <p>{!! nl2br(e($project->wiring_instructions)) !!}</p>
                    @else
                        <p class="muted">Wiring instructions will be available soon.</p>
                    @endif
                    @if($project->pin_mapping)
                        <h3>Pin Mapping</h3>
                        <table class="bom-table">
                            <thead><tr><th>Component</th><th>Pin</th><th>Controller Pin</th></tr></thead>
                            <tbody>
                                @foreach($project->pin_mapping as $pin)
                                <tr><td>{{ $pin['component'] ?? '' }}</td><td>{{ $pin['pin'] ?? '' }}</td><td>{{ $pin['controller_pin'] ?? '' }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

                <div class="tab-panel" id="tab-assembly">
                    <h2>Assembly Steps</h2>
                    @if($project->assembly_steps)
                        <ol class="step-list">
                            @foreach(explode("\n", $project->assembly_steps) as $step)
                                @if(trim($step))
                                    <li>{!! e(trim($step)) !!}</li>
                                @endif
                            @endforeach
                        </ol>
                    @else
                        <p class="muted">Assembly instructions will be available soon.</p>
                    @endif
                </div>

                <div class="tab-panel" id="tab-testing">
                    <h2>Testing & Calibration</h2>
                    @if($project->testing_procedure)
                        <h3>Testing Procedure</h3>
                        <p>{!! nl2br(e($project->testing_procedure)) !!}</p>
                    @endif
                    @if($project->calibration_procedure)
                        <h3>Calibration</h3>
                        <p>{!! nl2br(e($project->calibration_procedure)) !!}</p>
                    @endif
                    @if($project->troubleshooting)
                        <h3>Troubleshooting</h3>
                        <p>{!! nl2br(e($project->troubleshooting)) !!}</p>
                    @endif
                    @if(!$project->testing_procedure && !$project->calibration_procedure && !$project->troubleshooting)
                        <p class="muted">Testing guide will be available soon.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="proj-sidebar">
            <div class="proj-card">
                <h3>Project Info</h3>
                <div class="proj-stat"><span class="label">Difficulty</span><span class="value">{{ $project->difficulty_label }}</span></div>
                <div class="proj-stat"><span class="label">Duration</span><span class="value">{{ $project->duration_label }}</span></div>
                <div class="proj-stat"><span class="label">Cost</span><span class="value">{{ $project->currency }} {{ number_format($project->estimated_cost ?? 0, 2) }}</span></div>
                <div class="proj-stat"><span class="label">Controller</span><span class="value">{{ $project->main_controller ?? 'Various' }}</span></div>
                @if($project->grade_level)
                <div class="proj-stat"><span class="label">Grade Level</span><span class="value">{{ $project->grade_level }}</span></div>
                @endif
                <div class="proj-stat"><span class="label">Views</span><span class="value">{{ number_format($project->view_count) }}</span></div>
            </div>

            <div class="proj-card">
                <h3>Purchase Actions</h3>
                <div class="proj-actions">
                    <button class="btn btn-primary" onclick="addBomToCart({{ $project->id }})">Add Complete BOM to Cart</button>
                    <button class="btn btn-gold" onclick="createRfq({{ $project->id }})">Create RFQ for Missing Items</button>
                    <button class="btn btn-ghost" onclick="downloadBom({{ $project->id }})">Download BOM (CSV)</button>
                    <button class="btn btn-ghost" onclick="downloadCode({{ $project->id }})">Download Code (ZIP)</button>
                </div>
            </div>

            @if($project->lms_course_id)
            <div class="proj-card">
                <h3>Related Course</h3>
                <p style="font-size:.88rem;color:var(--muted);margin:0 0 12px">Learn this project step-by-step with guided lessons.</p>
                <a href="/lms/courses/{{ $project->lms_course_id }}" class="btn btn-primary" style="width:100%;justify-content:center">Enroll in Course</a>
            </div>
            @endif

            @if($project->safety_warnings)
            <div class="proj-card" style="border-color:#fca5a5;background:#fef2f2">
                <h3 style="color:#991b1b">⚠️ Safety Warnings</h3>
                <p style="font-size:.85rem;color:#991b1b;margin:0">{!! nl2br(e($project->safety_warnings)) !!}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('foot')
<script nonce="{{ $csp_nonce ?? '' }}">
function showTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-nav button').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).classList.add('active');
    event.target.classList.add('active');
}

function copyCode(btn) {
    const pre = btn.closest('.code-header').nextElementSibling;
    navigator.clipboard.writeText(pre.textContent);
    btn.textContent = 'Copied!';
    setTimeout(() => btn.textContent = 'Copy', 2000);
}

function addBomToCart(projectId) {
    if (!confirm('Add all BOM items to your cart?')) return;
    fetch('/api/v1/education/projects/' + projectId + '/bom', {headers:{Accept:'application/json'}})
        .then(r => r.json())
        .then(j => {
            const items = j.data.lines.filter(l => l.product_id);
            return Promise.all(items.map(i =>
                fetch('/api/v1/cart/items', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json', 'Accept':'application/json', 'Authorization': 'Bearer ' + (localStorage.getItem('token')||'')},
                    body: JSON.stringify({product_id: i.product_id, quantity: i.quantity})
                })
            ));
        })
        .then(() => { alert('BOM items added to cart!'); window.location.href = '/cart'; })
        .catch(e => alert('Error: ' + e.message));
}

function createRfq(projectId) {
    alert('RFQ creation for missing items - feature coming soon.');
}

function downloadBom(projectId) {
    window.open('/api/v1/education/projects/' + projectId + '/bom?format=csv', '_blank');
}

function downloadCode(projectId) {
    window.open('/api/v1/education/projects/' + projectId + '/code', '_blank');
}
</script>
@endpush
