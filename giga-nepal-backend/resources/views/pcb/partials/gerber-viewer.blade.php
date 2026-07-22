{{-- PCB Gerber Layer Viewer --}}
<div class="card" style="margin-bottom:16px">
    <div class="card-head"><div><h2>Gerber viewer</h2><div class="muted" style="font-size:.78rem">Layer visualization · zoom · pan · toggle</div></div></div>
    <div class="card-body">
        <div id="gerber-viewer-{{ $project->id }}">
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;align-items:center">
                <button class="btn btn-ghost" onclick="window._gv_{{ $project->id }}.zoom(1.2)" style="min-height:32px;padding:0 10px;font-size:.8rem">+</button>
                <button class="btn btn-ghost" onclick="window._gv_{{ $project->id }}.zoom(0.8)" style="min-height:32px;padding:0 10px;font-size:.8rem">−</button>
                <button class="btn btn-ghost" onclick="window._gv_{{ $project->id }}.fit()" style="min-height:32px;padding:0 10px;font-size:.8rem">Fit</button>
                <span class="gv-scale" style="font-size:.72rem;color:var(--muted)">100%</span>
                <span class="gv-status" style="font-size:.72rem;color:var(--faint);margin-left:auto">Loading...</span>
            </div>
            <div class="gv-toggles" style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:8px"></div>
            <div class="gv-wrap" style="position:relative;overflow:hidden;background:#000;border:1px solid var(--line);border-radius:8px;min-height:400px;cursor:grab">
                <svg class="gv-svg" style="display:block;width:100%;height:100%;min-height:400px"></svg>
                <div class="gv-loading" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.75);color:var(--cyan);font-size:.9rem;z-index:2">Loading Gerber layers...</div>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script nonce="{{ $csp_nonce ?? '' }}" type="importmap">
{ "imports": { "gerber-to-svg": "https://cdn.jsdelivr.net/npm/gerber-to-svg@2/+esm" } }
</script>
<script nonce="{{ $csp_nonce ?? '' }}" type="module">
import gerberToSvg from 'gerber-to-svg';
const C = {top_copper:'#ef4444',bottom_copper:'#3b82f6',top_solder_mask:'#10b981',bottom_solder_mask:'#059669',top_silkscreen:'#f9bd2c',bottom_silkscreen:'#d97706',top_paste:'#94a3b8',bottom_paste:'#64748b',drill:'#ec4899',board_outline:'#f8fafc',unknown:'#4b5563'};
class V{constructor(id){this.e=document.getElementById(id);this.l=new Map();this.v=new Set();this.s=1;this.x=0;this.y=0;this.svg=this.e.querySelector('.gv-svg');this.ld=this.e.querySelector('.gv-loading');this.st=this.e.querySelector('.gv-status');this.sc=this.e.querySelector('.gv-scale');this.tg=this.e.querySelector('.gv-toggles');this._b();}
async loadLayer(n,u,t='unknown'){try{const r=await fetch(u);if(!r.ok)throw new Error(r.status);const b=await r.arrayBuffer();const c=gerberToSvg(new Uint8Array(b),{color:C[t]||'#4b5563'});this.l.set(n,{svg:c.svg({optimize:!0})});this.v.add(n)}catch(e){this.l.set(n,{svg:null,err:e.message})}this._t(n,!this.l.get(n).svg);this._r()}
_t(n,e){const b=document.createElement('button');b.className='badge '+(e?'b-muted':'b-ok');b.style.cssText='cursor:pointer;font-size:.68rem;opacity:1';b.textContent=(e?'⚠ ':'')+n;b.onclick=()=>{this.v.has(n)?this.v.delete(n):this.v.add(n);b.style.opacity=this.v.has(n)?'1':'.4';this._r()};this.tg.appendChild(b)}
zoom(f){this.s*=f;this._r()}fit(){this.s=1;this.x=0;this.y=0;this._r()}
_b(){const w=this.e.querySelector('.gv-wrap');let d=!1,sx,sy;w.onmousedown=e=>{d=!0;sx=e.clientX-this.x;sy=e.clientY-this.y;w.style.cursor='grabbing'};w.onmouseup=()=>{d=!1;w.style.cursor='grab'};w.onmouseleave=()=>{d=!1;w.style.cursor='grab'};w.onmousemove=e=>{if(d){this.x=e.clientX-sx;this.y=e.clientY-sy;this._r()}};w.onwheel=e=>{e.preventDefault();this.s*=e.deltaY<0?1.1:.9;this._r()}}
_r(){this.sc.textContent=Math.round(this.s*100)+'%';const ld=[...this.l.values()].filter(l=>l.svg).length;this.st.textContent=ld+'/'+this.l.size+' layers';this.ld.style.display=ld===0?'flex':'none';const g=document.createElementNS('http://www.w3.org/2000/svg','g');g.setAttribute('transform','translate('+this.x+','+this.y+') scale('+this.s+')');for(const[n,l]of this.l){if(!l.svg||!this.v.has(n))continue;const w=document.createElementNS('http://www.w3.org/2000/svg','g');w.innerHTML=l.svg;g.appendChild(w.firstElementChild||w)}this.svg.innerHTML='';this.svg.appendChild(g)}}
window._gv_{{ $project->id }} = new V('gerber-viewer-{{ $project->id }}');
@foreach($project->files->where('file_type', 'gerber') as $file)
window._gv_{{ $project->id }}.loadLayer('{{ \Illuminate\Support\Str::limit($file->filename_original, 30) }}', '{{ $downloadUrls[$file->id] }}', '{{ $file->file_type }}');
@endforeach
</script>
@endpush
