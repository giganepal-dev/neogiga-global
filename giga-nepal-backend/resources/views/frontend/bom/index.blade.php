@extends('frontend.layout')
@section('title','BOM Tool — Upload CSV / Excel for Instant MPN Matching | NeoGiga')
@section('description','Upload your bill of materials as CSV or Excel (XLSX), or paste it directly. NeoGiga matches every manufacturer part number against live catalog stock, pricing, and RFQ sourcing.')
@section('content')

<section class="section" style="padding-top:18px">
<div class="wrap">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="/en">Home</a><span><x-icon name="chevron-right" size="12"/></span><strong>BOM Tool</strong></nav>

    <div class="grid" style="grid-template-columns:minmax(0,1.55fr) minmax(280px,.45fr);align-items:start">
        <div>
            <p class="eyebrow">BOM Tool</p>
            <h1 class="page-title" style="font-size:clamp(1.9rem,4vw,2.9rem);margin:6px 0 10px">Upload your BOM, get instant matches</h1>
            <p class="lead">Drop in your bill of materials — NeoGiga matches every manufacturer part number against 600,000+ live parts with stock, pricing, datasheets and RFQ sourcing.</p>
            @auth
                <p class="sub" style="margin:10px 0 0"><strong>{{ $customer['name'] }}</strong> · {{ $customer['email'] }} · completed matches are saved to <a href="/account/bom" style="color:var(--cyan);font-weight:700">your BOM projects</a>.</p>
            @endauth

            <form method="post" action="/en/bom" enctype="multipart/form-data" style="margin:22px 0" id="bom-form">
                @csrf
                <div class="panel" style="padding:0;overflow:hidden">
                    <div style="display:flex;border-bottom:1px solid var(--line)" role="tablist" aria-label="BOM input method">
                        <button type="button" class="bom-tab active" id="tab-upload" aria-selected="true">Upload file</button>
                        <button type="button" class="bom-tab" id="tab-paste" aria-selected="false">Paste BOM</button>
                    </div>

                    <div id="pane-upload" style="padding:22px">
                        <label id="bom-drop" for="bom-file" style="display:grid;place-items:center;gap:8px;border:2px dashed #c7d4e6;border-radius:12px;padding:38px 18px;text-align:center;cursor:pointer;transition:border-color .15s,background .15s">
                            <x-icon name="rfq" size="34"/>
                            <strong style="font-size:1.02rem">Drag &amp; drop your BOM here, or click to browse</strong>
                            <span class="sub" style="font-size:.84rem">CSV, TXT, or Excel (.xlsx / .xls) · up to 5&nbsp;MB</span>
                            <span id="bom-file-name" class="badge b-info" style="display:none"></span>
                        </label>
                        <input type="file" id="bom-file" name="bom_file" accept=".csv,.txt,.tsv,.xlsx,.xls" style="display:none">
                        <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;margin-top:14px">
                            <button class="btn btn-primary" type="submit"><x-icon name="search" size="16"/> Match my BOM</button>
                            <a class="sub" style="font-size:.84rem;text-decoration:underline" download="neogiga-bom-template.csv" href="data:text/csv;charset=utf-8,MPN%2CQuantity%2CManufacturer%0ANE555P%2C10%2CTexas%20Instruments%0ALM358N%2C25%2CSTMicroelectronics%0AGRM31CR71H106KA12L%2C100%2CMurata">Download CSV template</a>
                        </div>
                    </div>

                    <div id="pane-paste" style="padding:22px;display:none">
                        <textarea class="control" name="bom" rows="9" placeholder="MPN,Quantity,Manufacturer (optional)&#10;NE555P,10,TI&#10;LM358N,25,STMicro&#10;GRM31CR71H106KA12L,100,Murata&#10;…or paste CSV / tab-separated data. First line can be a header."
                            style="font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.84rem;min-height:170px;resize:vertical">{{ old('bom', $input ?? '') }}</textarea>
                        <div style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;margin-top:14px">
                            <button class="btn btn-primary" type="submit"><x-icon name="search" size="16"/> Match my BOM</button>
                            <span class="sub" style="font-size:.84rem">CSV or tab-separated · columns in any order</span>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <aside class="info-card">
            <h2 style="margin-top:0;font-size:1.05rem">How it works</h2>
            <ol class="sub" style="margin:0;padding-left:18px;display:grid;gap:8px;font-size:.88rem">
                <li>Upload a CSV / Excel BOM or paste it directly.</li>
                <li>We match each line by MPN, SKU and manufacturer against the live catalog.</li>
                <li>Review stock, unit pricing and datasheets per line.</li>
                <li>Add matched parts to cart, or push unmatched lines to RFQ sourcing.</li>
            </ol>
            <div style="border-top:1px solid var(--line);margin-top:14px;padding-top:14px">
                <p class="sub" style="font-size:.82rem;margin:0">Need volume pricing or hard-to-find parts? <a href="/en/rfq" style="color:var(--cyan);font-weight:600">Open a bulk RFQ</a> — our sourcing desk answers every request.</p>
            </div>
        </aside>
    </div>

    @if(isset($error) && $error)
        <div class="panel" style="padding:16px 18px;border-color:#f3b9b9;background:#fdf2f2;margin:4px 0 16px">
            <p style="color:#b42318;margin:0;font-weight:600">{{ $error }}</p>
        </div>
    @endif

    @if(!empty($results))
        @if(!empty($savedImport))
            <div class="panel" style="padding:14px 18px;border-color:#9bd5bd;background:#effaf5;margin:4px 0 16px">
                <p style="color:#067a55;margin:0;font-weight:700">Saved as BOM #{{ $savedImport->id }} for {{ $customer['email'] }}.</p>
            </div>
        @endif
        <div class="panel" style="margin:8px 0 16px">
            <div style="display:flex;gap:22px;flex-wrap:wrap;padding:16px 18px;align-items:center">
                <div><strong style="font-size:1.2rem">{{ $totalLines }}</strong> <span class="sub">lines</span></div>
                <div style="color:#067a55"><strong style="font-size:1.2rem">{{ $matched }}</strong> matched</div>
                @if($partial)<div style="color:#92400e"><strong style="font-size:1.2rem">{{ $partial }}</strong> near matches</div>@endif
                @if($unmatched > 0)<div style="color:#b42318"><strong style="font-size:1.2rem">{{ $unmatched }}</strong> unmatched</div>@endif
                @if($matched > 0)
                    <div style="margin-left:auto;display:flex;gap:10px;flex-wrap:wrap">
                        <form method="post" action="/cart/bom" style="margin:0">@csrf<button class="btn btn-primary" type="submit"><x-icon name="cart" size="16"/> Add matched to cart</button></form>
                        <a href="/en/rfq" class="btn btn-ghost">RFQ unmatched lines</a>
                    </div>
                @endif
            </div>
        </div>

        <div class="panel" style="overflow-x:auto">
            <table class="spec-table" style="min-width:760px">
                <thead>
                    <tr>
                        <th style="width:44px">#</th>
                        <th>MPN</th>
                        <th>Manufacturer</th>
                        <th style="width:70px">Qty</th>
                        <th style="width:34%">Catalog match</th>
                        <th>Stock</th>
                        <th style="text-align:right">Unit price</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($results as $i => $r)
                    @php $product = ($r['product_id'] ?? null) ? \App\Models\Marketplace\Product::find($r['product_id']) : null; @endphp
                    <tr>
                        <td class="sub">{{ $loop->iteration }}</td>
                        <td><strong>{{ $r['mpn'] ?? '—' }}</strong></td>
                        <td class="sub">{{ $r['manufacturer'] ?? '—' }}</td>
                        <td>{{ $r['quantity'] ?? 1 }}</td>
                        <td>
                            @if($product)
                                <a href="/en/products/{{ $product->slug }}" style="color:var(--cyan);font-weight:600">{{ \Illuminate\Support\Str::limit($product->name, 48) }}</a>
                                <div class="sub" style="font-size:.72rem">{{ $product->sku }}</div>
                            @else
                                @php $near = array_slice(array_merge($r['candidates'] ?? [], $r['suggestions'] ?? []), 0, 3); @endphp
                                @if($near !== [])
                                    <span class="badge b-warn">{{ ($r['status'] ?? '') === 'multiple' ? 'Multiple matches' : 'Close matches' }}</span>
                                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px">
                                        @foreach($near as $s)
                                            <a class="badge b-info" href="{{ !empty($s['slug']) ? '/en/products/'.$s['slug'] : '/en/products?q='.urlencode($s['mpn'] ?? '') }}" title="{{ $s['name'] ?? '' }}">{{ $s['mpn'] ?? ($s['sku'] ?? 'view') }}</a>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="badge b-muted">No match</span>
                                @endif
                            @endif
                        </td>
                        <td>
                            @if($product && ($product->stock_quantity ?? 0) > 0)
                                <span class="badge b-ok">{{ number_format($product->stock_quantity) }} in stock</span>
                            @elseif($product)
                                <span class="badge b-warn">RFQ</span>
                            @else
                                <span class="sub">—</span>
                            @endif
                        </td>
                        <td style="text-align:right">
                            @if($product && $product->base_price)
                                <strong>${{ number_format($product->base_price, 2) }}</strong>
                            @else
                                <span class="sub" style="font-size:.78rem">RFQ</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
</section>

<style nonce="{{ $csp_nonce ?? '' }}">
.bom-tab{flex:1;border:0;background:#f4f7fb;color:var(--muted);font-weight:700;font-size:.9rem;padding:14px 12px;border-bottom:2px solid transparent;transition:.15s}
.bom-tab.active{background:#fff;color:var(--cyan);border-bottom-color:var(--cyan)}
#bom-drop.dragover{border-color:var(--cyan);background:#f3f8ff}
</style>
<script nonce="{{ $csp_nonce ?? '' }}">
(function(){
var tabU=document.getElementById('tab-upload'),tabP=document.getElementById('tab-paste'),
    paneU=document.getElementById('pane-upload'),paneP=document.getElementById('pane-paste'),
    drop=document.getElementById('bom-drop'),input=document.getElementById('bom-file'),
    nameEl=document.getElementById('bom-file-name');
function activate(upload){
    tabU.classList.toggle('active',upload);tabP.classList.toggle('active',!upload);
    tabU.setAttribute('aria-selected',upload);tabP.setAttribute('aria-selected',!upload);
    paneU.style.display=upload?'':'none';paneP.style.display=upload?'none':'';
}
tabU.addEventListener('click',function(){activate(true)});
tabP.addEventListener('click',function(){activate(false)});
@if(old('bom', $input ?? '') !== '') activate(false); @endif
function showName(){
    if(input.files&&input.files.length){nameEl.textContent=input.files[0].name;nameEl.style.display='inline-flex';}
}
input.addEventListener('change',showName);
['dragenter','dragover'].forEach(function(ev){drop.addEventListener(ev,function(e){e.preventDefault();drop.classList.add('dragover')})});
['dragleave','drop'].forEach(function(ev){drop.addEventListener(ev,function(e){e.preventDefault();drop.classList.remove('dragover')})});
drop.addEventListener('drop',function(e){
    if(e.dataTransfer&&e.dataTransfer.files&&e.dataTransfer.files.length){input.files=e.dataTransfer.files;showName();}
});
})();
</script>
@endsection
