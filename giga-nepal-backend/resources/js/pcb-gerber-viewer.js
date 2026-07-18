import gerberToSvg from 'gerber-to-svg';

/**
 * PCB Gerber Layer Viewer
 * Renders Gerber files to SVG canvas with layer toggling, zoom, and pan.
 * ponytail: gerber-to-svg renders one layer at a time; we composite them on a canvas.
 */
class PcbGerberViewer {
  constructor(containerId) {
    this.container = document.getElementById(containerId);
    this.layers = new Map();
    this.visible = new Set();
    this.scale = 1;
    this.offsetX = 0;
    this.offsetY = 0;
    this.layerColors = {
      'top_copper': '#ef4444', 'bottom_copper': '#3b82f6',
      'top_solder_mask': '#10b981', 'bottom_solder_mask': '#059669',
      'top_silkscreen': '#f9bd2c', 'bottom_silkscreen': '#d97706',
      'top_paste': '#94a3b8', 'bottom_paste': '#64748b',
      'drill': '#ec4899', 'board_outline': '#f8fafc', 'unknown': '#4b5563',
    };
    this.init();
  }

  init() {
    this.container.innerHTML = `
      <div class="gv-toolbar" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;align-items:center">
        <button class="btn btn-ghost gv-zoom-in" style="min-height:32px;padding:0 10px;font-size:.8rem" title="Zoom in">+</button>
        <button class="btn btn-ghost gv-zoom-out" style="min-height:32px;padding:0 10px;font-size:.8rem" title="Zoom out">−</button>
        <button class="btn btn-ghost gv-zoom-fit" style="min-height:32px;padding:0 10px;font-size:.8rem" title="Fit to view">Fit</button>
        <span class="gv-scale" style="font-size:.72rem;color:var(--muted)">100%</span>
        <span class="gv-status" style="font-size:.72rem;color:var(--faint);margin-left:auto"></span>
      </div>
      <div class="gv-layer-toggles" style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:8px"></div>
      <div class="gv-canvas-wrap" style="position:relative;overflow:hidden;background:#000;border:1px solid var(--line);border-radius:8px;min-height:400px;cursor:grab">
        <svg class="gv-canvas" style="display:block;width:100%;height:100%;min-height:400px"></svg>
        <div class="gv-loading" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.7);color:var(--cyan);font-size:.9rem">Loading layers...</div>
      </div>`;
    this.svg = this.container.querySelector('.gv-canvas');
    this.loading = this.container.querySelector('.gv-loading');
    this.status = this.container.querySelector('.gv-status');
    this.scaleLabel = this.container.querySelector('.gv-scale');
    this.toggles = this.container.querySelector('.gv-layer-toggles');
    this.bindControls();
  }

  bindControls() {
    this.container.querySelector('.gv-zoom-in').onclick = () => this.zoom(1.2);
    this.container.querySelector('.gv-zoom-out').onclick = () => this.zoom(0.8);
    this.container.querySelector('.gv-zoom-fit').onclick = () => this.fitToView();
    const wrap = this.container.querySelector('.gv-canvas-wrap');
    let dragging = false, startX, startY;
    wrap.onmousedown = (e) => { dragging = true; startX = e.clientX - this.offsetX; startY = e.clientY - this.offsetY; wrap.style.cursor = 'grabbing'; };
    wrap.onmouseup = () => { dragging = false; wrap.style.cursor = 'grab'; };
    wrap.onmouseleave = () => { dragging = false; wrap.style.cursor = 'grab'; };
    wrap.onmousemove = (e) => { if (dragging) { this.offsetX = e.clientX - startX; this.offsetY = e.clientY - startY; this.render(); } };
    wrap.onwheel = (e) => { e.preventDefault(); this.zoom(e.deltaY < 0 ? 1.1 : 0.9, e.offsetX, e.offsetY); };
  }

  async loadLayer(name, url, type) {
    try {
      const response = await fetch(url);
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
      const buffer = await response.arrayBuffer();
      const converter = gerberToSvg(new Uint8Array(buffer), { color: this.layerColors[type] || '#4b5563' });
      const svgString = converter.svg({ optimize: true });
      this.layers.set(name, { name, type, svg: svgString });
      this.visible.add(name);
      this.addToggle(name, type);
      this.render();
    } catch (err) {
      this.layers.set(name, { name, type, svg: null, error: err.message });
      this.addToggle(name, type, true);
    }
  }

  addToggle(name, type, errored = false) {
    const color = this.layerColors[type] || '#4b5563';
    const btn = document.createElement('button');
    btn.className = 'badge ' + (errored ? 'b-muted' : 'b-ok');
    btn.style.cssText = `cursor:pointer;background:${errored?'rgba(255,255,255,.06)':color}22;border-color:${color}44;color:${errored?'var(--muted)':color};font-size:.68rem`;
    btn.textContent = (errored ? '⚠ ' : '') + name;
    btn.onclick = () => {
      if (this.visible.has(name)) { this.visible.delete(name); btn.style.opacity = '0.5'; }
      else { this.visible.add(name); btn.style.opacity = '1'; }
      this.render();
    };
    this.toggles.appendChild(btn);
  }

  zoom(factor, cx, cy) {
    this.scale *= factor;
    if (cx !== undefined) { this.offsetX = cx - (cx - this.offsetX) * factor; this.offsetY = cy - (cy - this.offsetY) * factor; }
    this.scaleLabel.textContent = Math.round(this.scale * 100) + '%';
    this.render();
  }

  fitToView() { this.scale = 1; this.offsetX = 0; this.offsetY = 0; this.scaleLabel.textContent = '100%'; this.render(); }

  render() {
    const loaded = Array.from(this.layers.values()).filter(l => l.svg).length;
    const total = this.layers.size;
    this.status.textContent = `${loaded}/${total} layers`;
    this.loading.style.display = loaded === 0 ? 'flex' : 'none';

    const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    g.setAttribute('transform', `translate(${this.offsetX},${this.offsetY}) scale(${this.scale})`);
    for (const [name, layer] of this.layers) {
      if (!layer.svg || !this.visible.has(name)) continue;
      const wrapper = document.createElementNS('http://www.w3.org/2000/svg', 'g');
      wrapper.innerHTML = layer.svg;
      g.appendChild(wrapper.firstElementChild || wrapper);
    }
    this.svg.innerHTML = '';
    this.svg.appendChild(g);
  }
}

window.PcbGerberViewer = PcbGerberViewer;
