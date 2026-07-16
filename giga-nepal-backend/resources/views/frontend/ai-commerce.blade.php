@extends('frontend.layout')

@section('title', $title)
@section('description', $description)
@push('head')
<script type="application/ld+json">@json($jsonLd, JSON_UNESCAPED_SLASHES)</script>
<style>
.page-hero{padding:44px 0 22px}.eyebrow{color:var(--gold);font-weight:900;letter-spacing:.12em;text-transform:uppercase;font-size:.76rem}.panel{background:rgba(13,34,64,.86);border:1px solid var(--line);border-radius:12px;padding:22px}.demo{display:grid;grid-template-columns:.9fr 1.1fr;gap:20px}.examples{display:flex;flex-wrap:wrap;gap:8px;margin:16px 0}.examples button{border:1px solid rgba(25,211,245,.28);border-radius:999px;background:#07182d;color:#fff;padding:8px 10px;cursor:pointer}textarea{width:100%;border:1px solid rgba(255,255,255,.16);border-radius:8px;background:#07182d;color:#fff;padding:11px 12px;font:inherit;min-height:130px}.result{display:grid;gap:10px}.item{border:1px solid var(--line);border-radius:10px;padding:12px}.muted{color:var(--muted)}.notice{font-size:.9rem;margin-top:10px}.err{color:#ffb4b4}.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin:22px 0}.cards div{border:1px solid var(--line);border-radius:10px;padding:14px}@media(max-width:820px){.demo{grid-template-columns:1fr}}
</style>
@endpush
@section('content')
<section class="page-hero">
    <p class="eyebrow"><x-icon name="ai-search" size="14"/> Local Rule Engine Demo</p>
    <h1><x-icon name="ai-engineer" size="28"/> AI Commerce for Engineers and Makers</h1>
    <p class="lead">From idea to component list, quote, cart, and learning guide.</p>
    <p class="muted">NeoGiga's AI commerce layer is designed to help users describe a project and instantly receive suggested components, compatible alternatives, datasheets, tutorials, sample code, stock status, and buying options.</p>
</section>
<section class="cards">
    @foreach (['Recommended components','Required and optional parts','Quantity and reason','Datasheet and warranty links','Compatible alternatives','Region-wise stock','Estimated total','Add BOM to cart','Request B2B quote','Link LMS tutorial','Sample code placeholder'] as $card)
        <div>{{ $card }}</div>
    @endforeach
</section>
<section class="panel demo">
    <div>
        <h2>Try AI Project Builder</h2>
        <p class="muted">This demo uses NeoGiga local rules. It does not call a paid AI API and does not create orders or payments.</p>
        <div class="examples" id="ai-examples"></div>
        <textarea id="ai-prompt" placeholder="I want to build a 4WD robot car"></textarea>
        <button class="btn btn-primary" id="ai-submit" type="button"><x-icon name="bom" size="16"/> Build BOM</button>
        <a class="btn btn-ghost" href="/sell-on-neogiga" style="margin-left:8px"><x-icon name="rfq" size="16"/> Request B2B BOM Quote</a>
        <p class="notice" id="ai-notice"></p>
    </div>
    <div>
        <h2>BOM Output</h2>
        <div class="result" id="ai-result"><p class="muted">Choose an example or enter a project prompt.</p></div>
    </div>
</section>
@endsection
@push('foot')
<script>
const examplesBox=document.getElementById('ai-examples'), promptBox=document.getElementById('ai-prompt'), resultBox=document.getElementById('ai-result'), notice=document.getElementById('ai-notice');
fetch('/api/commerce-ai/examples',{headers:{Accept:'application/json'}}).then(r=>r.json()).then(j=>{(j.data.examples||[]).forEach(p=>{const b=document.createElement('button');b.type='button';b.textContent=p;b.onclick=()=>{promptBox.value=p};examplesBox.appendChild(b)})});
document.getElementById('ai-submit').onclick=async()=>{notice.textContent='Building local BOM...';resultBox.innerHTML='';try{const r=await fetch('/api/commerce-ai/build-bom',{method:'POST',headers:{'Content-Type':'application/json',Accept:'application/json'},body:JSON.stringify({prompt:promptBox.value})});const j=await r.json();if(!r.ok)throw new Error(j.message||'Unable to build BOM');const b=j.data;notice.textContent=b.disclaimer;resultBox.innerHTML='<h3>'+b.title+'</h3>'+(b.items||[]).map(i=>'<div class="item"><strong>'+i.name+'</strong><br><span class="muted">Qty '+i.quantity+' - '+i.reason+'</span><br><span class="muted">'+i.availability_status+'</span></div>').join('')}catch(e){notice.textContent=e.message;notice.className='notice err'}};
</script>
@endpush
