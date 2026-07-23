@extends('frontend.layout')

@section('title', 'AI Project Builder - NeoGiga STEM')
@section('description', 'Describe your project idea and get a complete BOM, code, wiring guide, and purchase options.')

@push('head')
<style nonce="{{ $csp_nonce ?? '' }}">
.builder-hero{padding:48px 0 28px;background:linear-gradient(135deg,#0f172a,#1e293b);color:#e2e8f0}
.builder-hero h1{font-size:clamp(1.8rem,4vw,2.8rem);font-weight:800;margin:10px 0 12px;color:#fff}
.builder-hero p{color:#94a3b8;font-size:1.05rem;max-width:64ch}
.builder-grid{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-top:28px}
.builder-input{background:#fff;border:1px solid var(--line);border-radius:14px;padding:24px}
.builder-input h2{font-size:1.15rem;font-weight:700;margin:0 0 16px}
.builder-input textarea{width:100%;border:1px solid #c7d4e6;border-radius:10px;padding:14px;font-size:.95rem;min-height:140px;resize:vertical;font-family:inherit}
.builder-input textarea:focus{border-color:var(--cyan);outline:none}
.builder-input .examples{display:flex;flex-wrap:wrap;gap:8px;margin:14px 0}
.builder-input .examples button{border:1px solid #c7d4e6;border-radius:999px;background:#f8fafc;padding:7px 14px;font-size:.82rem;cursor:pointer;transition:.15s}
.builder-input .examples button:hover{border-color:var(--cyan);color:var(--cyan)}
.builder-input .options{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin:16px 0}
.builder-input .options label{font-size:.82rem;color:var(--muted);font-weight:600}
.builder-input .options select{width:100%;border:1px solid #c7d4e6;border-radius:8px;padding:8px 12px;font-size:.88rem}
.builder-result{background:#fff;border:1px solid var(--line);border-radius:14px;padding:24px;max-height:70vh;overflow-y:auto}
.builder-result h2{font-size:1.15rem;font-weight:700;margin:0 0 16px}
.result-loading{text-align:center;padding:40px;color:var(--faint)}
.result-loading .spinner{width:32px;height:32px;border:3px solid var(--line);border-top-color:var(--cyan);border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 12px}
@keyframes spin{to{transform:rotate(360deg)}}
.result-project{margin-bottom:20px}
.result-project h3{font-size:1.1rem;font-weight:700;margin:0 0 8px;color:var(--cyan)}
.result-project .meta{display:flex;gap:14px;flex-wrap:wrap;font-size:.82rem;color:var(--muted);margin-bottom:12px}
.result-bom{margin:16px 0}
.result-bom table{width:100%;border-collapse:collapse;font-size:.82rem}
.result-bom th{text-align:left;padding:8px 10px;background:var(--s2);border-bottom:2px solid var(--line);font-weight:700;font-size:.76rem;color:var(--muted);text-transform:uppercase}
.result-bom td{padding:8px 10px;border-bottom:1px solid var(--line)}
.result-bom .match{color:#16a34a;font-weight:600}
.result-bom .suggest{color:#d97706;font-weight:600}
.result-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:16px;padding-top:16px;border-top:1px solid var(--line)}
.result-actions .btn{min-height:38px;font-size:.84rem;padding:0 14px}
.result-code{background:#1e293b;color:#e2e8f0;border-radius:10px;padding:14px;font-family:ui-monospace,monospace;font-size:.8rem;overflow-x:auto;white-space:pre;line-height:1.5;margin-top:16px}
.result-steps{margin:16px 0}
.result-steps li{padding:8px 0;border-bottom:1px solid var(--line);font-size:.88rem;color:var(--muted)}
.disclaimer{background:#fef3c7;border:1px solid #fcd34d;border-radius:10px;padding:12px 16px;font-size:.82rem;color:#92400e;margin-top:16px}
@media(max-width:900px){.builder-grid{grid-template-columns:1fr}.builder-input .options{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<section class="builder-hero">
    <div class="wrap">
        <p class="eyebrow" style="color:var(--gold)">AI Project Builder</p>
        <h1>Describe Your Project, We Build the BOM</h1>
        <p>Tell NeoGiga what you want to build and receive a complete component list, code, wiring guide, and purchase options.</p>
    </div>
</section>

<section class="wrap">
    <div class="builder-grid">
        <div class="builder-input">
            <h2>What do you want to build?</h2>
            <textarea id="builder-prompt" placeholder="Example: Build a 4WD obstacle-avoiding robot for a Grade 8 student in Nepal using ESP32"></textarea>

            <div class="examples" id="builder-examples">
                <button onclick="fillPrompt(this)">4WD robot for Grade 8</button>
                <button onclick="fillPrompt(this)">ESP32 weather station</button>
                <button onclick="fillPrompt(this)">Smart irrigation system</button>
                <button onclick="fillPrompt(this)">RFID attendance system</button>
                <button onclick="fillPrompt(this)">Line-following robot</button>
                <button onclick="fillPrompt(this)">GPS tracker with LoRa</button>
            </div>

            <div class="options">
                <div>
                    <label>Preferred Controller</label>
                    <select id="builder-controller">
                        <option value="">Auto-detect</option>
                        <option value="ESP32">ESP32</option>
                        <option value="Arduino">Arduino</option>
                        <option value="Raspberry Pi">Raspberry Pi</option>
                        <option value="STM32">STM32</option>
                        <option value="Micro:bit">Micro:bit</option>
                    </select>
                </div>
                <div>
                    <label>Budget Limit</label>
                    <select id="builder-budget">
                        <option value="">No limit</option>
                        <option value="1000">Under $10</option>
                        <option value="2500">Under $25</option>
                        <option value="5000">Under $50</option>
                        <option value="10000">Under $100</option>
                    </select>
                </div>
            </div>

            <button class="btn btn-primary" id="builder-submit" style="width:100%;justify-content:center;margin-top:12px">
                Build Project
            </button>
        </div>

        <div class="builder-result">
            <h2>Project Result</h2>
            <div id="builder-output">
                <p class="muted" style="text-align:center;padding:40px 0">Enter your project description and click Build to get started.</p>
            </div>
        </div>
    </div>
</section>
@endsection

@push('foot')
<script nonce="{{ $csp_nonce ?? '' }}">
const promptBox = document.getElementById('builder-prompt');
const outputBox = document.getElementById('builder-output');

function fillPrompt(btn) {
    promptBox.value = btn.textContent;
    promptBox.focus();
}

document.getElementById('builder-submit').onclick = async () => {
    const prompt = promptBox.value.trim();
    if (!prompt) { alert('Please describe your project.'); return; }

    outputBox.innerHTML = '<div class="result-loading"><div class="spinner"></div>Analyzing project and matching components...</div>';

    try {
        const res = await fetch('/api/v1/education/ai-build', {
            method: 'POST',
            headers: {'Content-Type':'application/json', 'Accept':'application/json'},
            body: JSON.stringify({ prompt })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Build failed');

        const r = data.data;
        let html = '';

        // Project header
        html += '<div class="result-project">';
        html += '<h3>' + (r.project?.title || 'Project') + '</h3>';
        html += '<div class="meta">';
        html += '<span>Difficulty: ' + (r.project?.difficulty || 'Beginner') + '</span>';
        html += '<span>Duration: ' + (r.project?.estimated_duration || '4-8 hours') + '</span>';
        html += '<span>Controller: ' + (r.project?.controller || 'ESP32') + '</span>';
        if (r.project?.grade_level) html += '<span>Grade: ' + r.project.grade_level + '</span>';
        html += '</div>';
        if (r.project?.summary) html += '<p style="color:var(--muted);font-size:.88rem">' + r.project.summary + '</p>';
        html += '</div>';

        // BOM
        if (r.bom && r.bom.length > 0) {
            html += '<div class="result-bom"><h3>Bill of Materials</h3>';
            html += '<table><thead><tr><th>Component</th><th>Role</th><th>Qty</th><th>Status</th></tr></thead><tbody>';
            r.bom.forEach(item => {
                const statusClass = item.match_type === 'catalog_match' ? 'match' : 'suggest';
                const statusText = item.match_type === 'catalog_match' ? 'Catalog Match' : 'Suggestion';
                html += '<tr>';
                html += '<td><strong>' + item.name + '</strong></td>';
                html += '<td>' + (item.role || '') + '</td>';
                html += '<td>' + item.quantity + '</td>';
                html += '<td class="' + statusClass + '">' + statusText + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table></div>';
        }

        // Code
        if (r.code) {
            html += '<h3>Sample Code (' + (r.code.language || 'Arduino') + ')</h3>';
            html += '<div class="result-code">' + escapeHtml(r.code.source_code || '') + '</div>';
        }

        // Build guide
        if (r.build_guide?.steps) {
            html += '<div class="result-steps"><h3>Build Steps</h3><ol>';
            r.build_guide.steps.forEach(step => {
                html += '<li><strong>' + step.title + '</strong> - ' + step.description + '</li>';
            });
            html += '</ol></div>';
        }

        // Actions
        html += '<div class="result-actions">';
        html += '<button class="btn btn-primary" onclick="alert(\'BOM will be added to cart once products are matched to catalog.\')">Add BOM to Cart</button>';
        html += '<button class="btn btn-ghost" onclick="alert(\'RFQ creation for missing items.\')">Create RFQ</button>';
        html += '<button class="btn btn-ghost" onclick="alert(\'Save project to your account.\')">Save Project</button>';
        html += '</div>';

        // Disclaimer
        if (r.disclaimer) {
            html += '<div class="disclaimer">' + r.disclaimer + '</div>';
        }

        outputBox.innerHTML = html;

    } catch (e) {
        outputBox.innerHTML = '<div class="disclaimer" style="border-color:#fca5a5;background:#fef2f2;color:#991b1b">Error: ' + e.message + '</div>';
    }
};

function escapeHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
@endpush
