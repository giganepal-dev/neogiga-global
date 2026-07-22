@extends('frontend.layout')
@section('title', 'SMD Marking Code Identifier | Find Semiconductor MPNs – NeoGiga')
@section('description', 'Identify unknown SMD components by their top marking code. Search across manufacturers, packages, and functions to find the correct part number.')

@section('content')
<div class="section" style="max-width:900px;margin:40px auto">
    <h1>SMD Marking Code Identifier</h1>
    <p class="sub">Enter the top marking from an SMD component to identify possible part numbers. Results include manufacturer, package, function, and availability on NeoGiga.</p>

    <div class="panel" style="padding:20px;margin-bottom:24px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div>
                <label class="form-label">Marking Code *</label>
                <input type="text" id="marking" class="control" placeholder="e.g. 0A, LTH7, A19T" maxlength="20" autofocus
                       style="font-family:monospace;font-size:18px;text-align:center;letter-spacing:4px">
            </div>
            <div>
                <label class="form-label">Package</label>
                <select id="package" class="control">
                    <option value="">Any / Unknown</option>
                    <option value="SOT-23">SOT-23</option>
                    <option value="SOT-23-5">SOT-23-5</option>
                    <option value="SOD-123">SOD-123</option>
                    <option value="SOD-323">SOD-323</option>
                    <option value="SOD-523">SOD-523</option>
                    <option value="SOT-89">SOT-89</option>
                    <option value="SOT-223">SOT-223</option>
                    <option value="SC-70">SC-70</option>
                    <option value="DFN1010-4">DFN1010-4</option>
                </select>
            </div>
            <div>
                <label class="form-label">Number of Pins</label>
                <input type="number" id="pins" class="control" placeholder="e.g. 3, 5, 8" min="1" max="100">
            </div>
            <div>
                <label class="form-label">Manufacturer (if known)</label>
                <input type="text" id="manufacturer" class="control" placeholder="e.g. Texas Instruments">
            </div>
            <div>
                <label class="form-label">Component Type</label>
                <select id="function" class="control">
                    <option value="">Any</option>
                    <option value="Voltage Regulator">Voltage Regulator</option>
                    <option value="Zener Diode">Zener Diode</option>
                    <option value="Transistor">Transistor</option>
                    <option value="MOSFET">MOSFET</option>
                    <option value="Voltage Detector">Voltage Detector</option>
                    <option value="Schottky Diode">Schottky Diode</option>
                    <option value=" Operational Amplifier">Operational Amplifier</option>
                    <option value="DC-DC Converter">DC-DC Converter</option>
                </select>
            </div>
            <div style="display:flex;align-items:end">
                <button onclick="search()" class="btn btn-primary" style="width:100%">Identify Component</button>
            </div>
        </div>
    </div>

    <div id="results" style="display:none">
        <h2 id="resultCount" style="margin-bottom:16px"></h2>
        <div id="resultList"></div>
    </div>
    <div id="empty" class="panel" style="padding:40px;text-align:center">
        <p class="sub">Enter a marking code above to search the SMD identification database.</p>
    </div>

    <div class="panel" style="padding:16px;margin-top:24px;background:#fef3c7;border:1px solid #f59e0b">
        <strong style="color:#92400e">⚠ Identification Disclaimer</strong>
        <p style="color:#92400e;margin:8px 0 0;font-size:13px">SMD markings are not unique identifiers. The same code can belong to different components from different manufacturers. Always verify with datasheets before use in production. Confirmed matches require manufacturer or datasheet evidence.</p>
    </div>
</div>

<script nonce="{{ $csp_nonce ?? '' }}">
async function search() {
    const marking = document.getElementById('marking').value.trim();
    if (!marking) return;

    const params = new URLSearchParams({marking});
    const pkg = document.getElementById('package').value;
    const pins = document.getElementById('pins').value;
    const mfr = document.getElementById('manufacturer').value.trim();
    const func = document.getElementById('function').value;

    if (pkg) params.set('package', pkg);
    if (pins) params.set('pins', pins);
    if (mfr) params.set('manufacturer', mfr);
    if (func) params.set('function', func);

    try {
        const res = await fetch('/api/v1/smd-markings/search?' + params);
        const data = await res.json();
        render(data.data || []);
    } catch(e) {
        document.getElementById('resultList').innerHTML = '<p class="sub">Search unavailable. Try again.</p>';
        document.getElementById('results').style.display = 'block';
        document.getElementById('empty').style.display = 'none';
    }
}

function render(matches) {
    document.getElementById('results').style.display = 'block';
    document.getElementById('empty').style.display = 'none';
    document.getElementById('resultCount').textContent = matches.length + ' candidate' + (matches.length !== 1 ? 's' : '') + ' found';

    if (!matches.length) {
        document.getElementById('resultList').innerHTML = '<div class="panel" style="padding:24px;text-align:center"><p>No matches found. Try a different marking code or fewer filters.</p></div>';
        return;
    }

    document.getElementById('resultList').innerHTML = matches.map(m => {
        const badge = m.confidence_score >= 90 ? 'b-ok' : m.confidence_score >= 75 ? 'b-info' : m.confidence_score >= 50 ? 'b-warn' : 'b-muted';
        return `<div class="panel" style="padding:16px;margin-bottom:12px">
            <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:8px">
                <div>
                    <strong style="font-size:16px">${esc(m.mpn)}</strong>
                    <div style="font-size:13px;color:var(--muted)">${esc(m.manufacturer || 'Unknown')}</div>
                </div>
                <span class="badge ${badge}">${m.confidence_score}% · ${m.confidence_class.replace('_',' ')}</span>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-size:13px;margin-bottom:8px">
                <div><strong>Package:</strong> ${esc(m.package || '—')} ${m.pins ? '('+m.pins+' pins)' : ''}</div>
                <div><strong>Function:</strong> ${esc(m.function || '—')}</div>
                ${m.characteristics ? `<div style="grid-column:span 2"><strong>Characteristics:</strong> ${esc(m.characteristics)}</div>` : ''}
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                ${m.has_product ? `<a href="/en/products/${m.product_slug}" class="btn btn-primary btn-sm">View Product</a>` : ''}
                <a href="/en/rfq?mpn=${encodeURIComponent(m.mpn)}" class="btn btn-ghost btn-sm">Request Sourcing</a>
                <button onclick="report(${m.id})" class="btn btn-ghost btn-sm" style="color:var(--danger)">Report Incorrect</button>
            </div>
        </div>`;
    }).join('');
}

function report(id) { fetch('/api/v1/smd-identification/report', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({match_id:id,reason:'User reported incorrect match'})}).then(() => alert('Reported. Thank you.')); }
function esc(s) { return (s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/"/g,'&quot;'); }
document.getElementById('marking').addEventListener('keydown', e => { if (e.key === 'Enter') search(); });
</script>
@endsection
