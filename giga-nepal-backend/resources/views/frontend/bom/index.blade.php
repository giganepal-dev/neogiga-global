@extends('frontend.layout')
@section('title','BOM Upload — MPN Matching | NeoGiga')
@section('description','Upload or paste your bill of materials. NeoGiga matches manufacturer part numbers (MPN) to the live catalog with stock, price, and RFQ sourcing.')
@section('content')

<section style="padding:28px 0 64px">
<div class="wrap">
<h1 class="page-title" style="margin:0 0 8px">BOM MPN Matching</h1>
<p class="lead" style="max-width:72ch">Paste your bill of materials (CSV or tab-separated). Our catalog matches manufacturer part numbers to available stock, datasheets, and RFQ sourcing.</p>

<form method="post" action="/en/bom" style="margin:20px 0">
    @csrf
    <div style="display:grid;gap:10px">
        <textarea class="control" name="bom" rows="10" placeholder="MPN,Quantity,Manufacturer (optional)&#10;NE555P,10,TI&#10;LM358N,25,STMicro&#10;GRM31CR71H106KA12L,100,Murata&#10;…or paste CSV / tab-separated data"
          style="font-family:ui-monospace,SFMono-Regular,monospace;font-size:.82rem;min-height:160px;width:100%;border:1px solid var(--line);border-radius:10px;padding:12px;background:var(--s1);color:var(--on);resize:vertical"
        >{{ old('bom', $input ?? '') }}</textarea>
        <div style="display:flex;gap:8px;align-items:center">
            <button class="btn btn-primary" type="submit">Match MPNs</button>
            <span style="font-size:.78rem;color:var(--muted)">CSV or tab-separated. First line can be a header.</span>
        </div>
    </div>
</form>

@if(isset($error))
    <div class="card" style="padding:16px;border-color:rgba(239,68,68,.3);margin-bottom:16px">
        <p style="color:#ef4444;margin:0">{{ $error }}</p>
    </div>
@endif

@if(!empty($results))
    <div class="card" style="margin-bottom:16px">
        <div style="display:flex;gap:16px;flex-wrap:wrap;padding:16px">
            <div><strong>{{ $totalLines }}</strong> lines</div>
            <div style="color:#34d399"><strong>{{ $matched }}</strong> matched</div>
            @if($partial)<div style="color:var(--gold)"><strong>{{ $partial }}</strong> partial</div>@endif
            @if($unmatched > 0)<div style="color:#ef4444"><strong>{{ $unmatched }}</strong> unmatched</div>@endif
        </div>
    </div>

    <div class="table-wrap" style="overflow-x:auto">
        <table class="table" style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="border-bottom:2px solid var(--line)">
                    <th style="padding:10px 12px;text-align:left;font-size:.74rem;color:var(--faint)">#</th>
                    <th style="padding:10px 12px;text-align:left;font-size:.74rem;color:var(--faint)">MPN</th>
                    <th style="padding:10px 12px;text-align:left;font-size:.74rem;color:var(--faint)">Mfr</th>
                    <th style="padding:10px 12px;text-align:left;font-size:.74rem;color:var(--faint)">Qty</th>
                    <th style="padding:10px 12px;text-align:left;font-size:.74rem;color:var(--faint)">Match</th>
                    <th style="padding:10px 12px;text-align:left;font-size:.74rem;color:var(--faint)">Stock</th>
                    <th style="padding:10px 12px;text-align:right;font-size:.74rem;color:var(--faint)">Price</th>
                </tr>
            </thead>
            <tbody>
            @foreach($results as $i => $r)
                @php $product = ($r['product_id'] ?? null) ? \App\Models\Marketplace\Product::find($r['product_id']) : null; @endphp
                <tr style="border-bottom:1px solid var(--line)">
                    <td style="padding:8px 12px;font-size:.78rem;color:var(--faint)">{{ $i + 1 }}</td>
                    <td style="padding:8px 12px;font-family:monospace;font-size:.82rem"><strong>{{ $r['mpn'] ?? '—' }}</strong></td>
                    <td style="padding:8px 12px;font-size:.78rem;color:var(--muted)">{{ $r['manufacturer'] ?? '—' }}</td>
                    <td style="padding:8px 12px;font-size:.82rem">{{ $r['quantity'] ?? 1 }}</td>
                    <td style="padding:8px 12px">
                        @if($product)
                            <a href="/en/products/{{ $product->slug }}" style="color:var(--cyan);font-weight:600;font-size:.82rem">{{ \Illuminate\Support\Str::limit($product->name, 40) }}</a>
                            <div style="font-size:.7rem;color:var(--faint)">{{ $product->sku }}</div>
                        @elseif(($r['confidence'] ?? '') === 'partial')
                            <span class="badge b-warn" style="font-size:.68rem">Partial match</span>
                        @else
                            <span class="badge b-muted" style="font-size:.68rem">No match</span>
                        @endif
                    </td>
                    <td style="padding:8px 12px">
                        @if($product && ($product->stock_quantity ?? 0) > 0)
                            <span class="badge b-ok" style="font-size:.68rem">{{ $product->stock_quantity }} in stock</span>
                        @elseif($product)
                            <span class="badge b-warn" style="font-size:.68rem">RFQ</span>
                        @else
                            <span style="font-size:.68rem;color:var(--faint)">—</span>
                        @endif
                    </td>
                    <td style="padding:8px 12px;text-align:right">
                        @if($product && $product->base_price)
                            <strong style="font-size:.82rem">${{ number_format($product->base_price, 2) }}</strong>
                        @else
                            <span style="font-size:.68rem;color:var(--faint)">RFQ</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @if($matched > 0)
    <div style="display:flex;gap:10px;margin-top:16px;flex-wrap:wrap">
        <form method="post" action="/cart/bom" style="margin:0">@csrf
            <button class="btn btn-primary" type="submit">Add Matched to Cart</button>
        </form>
        <a href="/en/rfq" class="btn btn-ghost">Request RFQ for Unmatched</a>
    </div>
    @endif
@endif
</div>
</section>
@endsection
