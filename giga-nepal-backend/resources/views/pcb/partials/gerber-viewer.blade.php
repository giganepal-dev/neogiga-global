@php
    $viewerId = 'gerber-viewer-'.$project->id;
    $viewerLayers = $run->detectedLayers
        ->filter(fn($layer) => isset($layerUrls[$layer->id]))
        ->map(fn($layer) => [
            'name' => $layer->filename,
            'type' => $layer->detected_type,
            'url' => $layerUrls[$layer->id],
        ])->values();
@endphp
<div class="card" style="margin-bottom:16px">
    <div class="card-head"><div><h2>Gerber viewer</h2><div class="muted" style="font-size:.78rem">Individual archive layers · zoom · pan · toggle</div></div><span class="badge b-info">Private preview</span></div>
    <div class="card-body">
        <div id="{{ $viewerId }}" class="gerber-viewer">
            <div class="gv-toolbar" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;align-items:center">
                <button type="button" class="btn btn-ghost" data-gv-action="zoom-in" style="min-height:32px;padding:0 10px;font-size:.8rem" aria-label="Zoom in">+</button>
                <button type="button" class="btn btn-ghost" data-gv-action="zoom-out" style="min-height:32px;padding:0 10px;font-size:.8rem" aria-label="Zoom out">−</button>
                <button type="button" class="btn btn-ghost" data-gv-action="fit" style="min-height:32px;padding:0 10px;font-size:.8rem">Fit</button>
                <span class="gv-scale" style="font-size:.72rem;color:var(--muted)">100%</span>
                <span class="gv-status" style="font-size:.72rem;color:var(--faint);margin-left:auto">Preparing layers…</span>
            </div>
            <div class="gv-toggles" style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:8px"></div>
            <div class="gv-wrap" style="position:relative;overflow:hidden;background:#020617;border:1px solid var(--line);border-radius:8px;min-height:420px;cursor:grab;touch-action:none">
                <div class="gv-grid" style="position:absolute;inset:0;background-image:linear-gradient(rgba(148,163,184,.08) 1px,transparent 1px),linear-gradient(90deg,rgba(148,163,184,.08) 1px,transparent 1px);background-size:24px 24px"></div>
                <div class="gv-canvas" style="position:absolute;inset:24px;transform-origin:center center"></div>
                <div class="gv-loading" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(2,6,23,.82);color:var(--cyan);font-size:.9rem;z-index:2">Rendering Gerber layers…</div>
            </div>
            <p class="muted" style="font-size:.76rem;margin:9px 0 0">Preview geometry is advisory only. Manufacturing output remains subject to NeoGiga engineering review.</p>
        </div>
    </div>
</div>
@push('scripts')
<script nonce="{{ $csp_nonce ?? '' }}" src="https://cdn.jsdelivr.net/npm/gerber-to-svg@4.2.8/dist/gerber-to-svg.min.js"></script>
<script nonce="{{ $csp_nonce ?? '' }}">
(() => {
    const root = document.getElementById(@js($viewerId));
    const layerDefinitions = @js($viewerLayers);
    const colors = {top_copper:'#ef4444',bottom_copper:'#3b82f6',inner_copper:'#8b5cf6',top_solder_mask:'#10b981',bottom_solder_mask:'#059669',top_silkscreen:'#f9bd2c',bottom_silkscreen:'#d97706',top_paste:'#94a3b8',bottom_paste:'#64748b',drill:'#ec4899',board_outline:'#f8fafc',mechanical:'#6b7280',unknown:'#4b5563'};
    const canvas = root.querySelector('.gv-canvas');
    const viewport = root.querySelector('.gv-wrap');
    const loading = root.querySelector('.gv-loading');
    const status = root.querySelector('.gv-status');
    const scaleLabel = root.querySelector('.gv-scale');
    const toggles = root.querySelector('.gv-toggles');
    const layers = new Map();
    let scale = 1;
    let translateX = 0;
    let translateY = 0;
    let completed = 0;
    let dragging = false;
    let dragX = 0;
    let dragY = 0;

    const applyTransform = () => {
        canvas.style.transform = `translate(${translateX}px,${translateY}px) scale(${scale})`;
        scaleLabel.textContent = `${Math.round(scale * 100)}%`;
    };
    const sanitizeSvg = markup => {
        const documentNode = new DOMParser().parseFromString(String(markup), 'image/svg+xml');
        if (documentNode.querySelector('parsererror')) throw new Error('Invalid SVG output');
        documentNode.querySelectorAll('script,foreignObject,iframe,object,embed').forEach(node => node.remove());
        documentNode.querySelectorAll('*').forEach(node => [...node.attributes].forEach(attribute => {
            const name = attribute.name.toLowerCase();
            if (name.startsWith('on') || ((name === 'href' || name === 'xlink:href') && !attribute.value.startsWith('#'))) node.removeAttribute(attribute.name);
        }));
        return documentNode.documentElement.outerHTML;
    };
    const convert = source => new Promise((resolve, reject) => {
        if (typeof window.gerberToSvg !== 'function') return reject(new Error('Gerber renderer unavailable'));
        let settled = false;
        const finish = (error, svg) => {
            if (settled) return;
            settled = true;
            error ? reject(error) : resolve(svg);
        };
        try {
            const converter = window.gerberToSvg(source, {}, finish);
            if (typeof converter === 'string') finish(null, converter);
            else if (converter && typeof converter.on === 'function') {
                let output = '';
                converter.on('data', chunk => output += chunk);
                converter.on('error', error => finish(error));
                converter.on('end', () => finish(null, output));
            }
        } catch (error) { finish(error); }
    });
    const updateStatus = () => {
        const rendered = [...layers.values()].filter(layer => layer.rendered).length;
        status.textContent = `${rendered}/${layerDefinitions.length} layers rendered`;
        if (completed === layerDefinitions.length) {
            loading.style.display = 'none';
            if (!rendered) status.textContent = 'Preview unavailable—download files or contact engineering';
        }
    };
    const addToggle = layer => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `badge ${layer.rendered ? 'b-ok' : 'b-warn'}`;
        button.style.cssText = 'cursor:pointer;font-size:.68rem;opacity:1';
        button.textContent = `${layer.rendered ? '' : '⚠ '}${layer.name}`;
        button.disabled = !layer.rendered;
        button.addEventListener('click', () => {
            layer.visible = !layer.visible;
            layer.element.hidden = !layer.visible;
            button.style.opacity = layer.visible ? '1' : '.4';
        });
        toggles.appendChild(button);
    };
    const loadLayer = async definition => {
        const layer = {name:definition.name, visible:true, rendered:false, element:null};
        try {
            const response = await fetch(definition.url, {credentials:'same-origin', headers:{Accept:'text/plain'}});
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            const markup = sanitizeSvg(await convert(await response.text()));
            const element = document.createElement('div');
            element.style.cssText = `position:absolute;inset:0;color:${colors[definition.type] || colors.unknown};mix-blend-mode:screen`;
            element.innerHTML = markup;
            const svg = element.querySelector('svg');
            if (svg) { svg.style.width = '100%'; svg.style.height = '100%'; svg.setAttribute('preserveAspectRatio','xMidYMid meet'); }
            canvas.appendChild(element);
            layer.element = element;
            layer.rendered = true;
        } catch (error) {
            layer.error = error instanceof Error ? error.message : String(error);
        }
        layers.set(definition.name, layer);
        completed++;
        addToggle(layer);
        updateStatus();
    };

    root.querySelector('[data-gv-action="zoom-in"]').addEventListener('click', () => { scale = Math.min(8, scale * 1.2); applyTransform(); });
    root.querySelector('[data-gv-action="zoom-out"]').addEventListener('click', () => { scale = Math.max(.2, scale * .8); applyTransform(); });
    root.querySelector('[data-gv-action="fit"]').addEventListener('click', () => { scale = 1; translateX = 0; translateY = 0; applyTransform(); });
    viewport.addEventListener('pointerdown', event => { dragging = true; dragX = event.clientX - translateX; dragY = event.clientY - translateY; viewport.setPointerCapture(event.pointerId); viewport.style.cursor = 'grabbing'; });
    viewport.addEventListener('pointermove', event => { if (!dragging) return; translateX = event.clientX - dragX; translateY = event.clientY - dragY; applyTransform(); });
    viewport.addEventListener('pointerup', event => { dragging = false; viewport.releasePointerCapture(event.pointerId); viewport.style.cursor = 'grab'; });
    viewport.addEventListener('wheel', event => { event.preventDefault(); scale = Math.max(.2, Math.min(8, scale * (event.deltaY < 0 ? 1.1 : .9))); applyTransform(); }, {passive:false});

    if (layerDefinitions.length) layerDefinitions.forEach(loadLayer);
    else { completed = 0; loading.style.display = 'none'; status.textContent = 'No renderable layers detected'; }
})();
</script>
@endpush
