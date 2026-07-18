@extends('frontend.layout')
@section('title','Compare Products | NeoGiga')
@section('content')
<section style="padding:28px 0 64px"><div class="wrap">
<h1 class="page-title" style="margin:0 0 8px">Compare Products</h1>
<p class="lead" style="max-width:72ch">Select products to compare side by side.</p>
<form method="get" action="/en/compare" style="margin:20px 0">
    <input class="control" name="p" value="{{ implode(',', $slugs) }}" placeholder="Product slugs, comma-separated" style="font-family:monospace;font-size:.82rem;width:100%;max-width:600px;border:1px solid var(--line);border-radius:10px;min-height:44px;padding:8px 12px;background:var(--s1);color:var(--on)">
    <button class="btn btn-primary" type="submit" style="margin-top:8px">Compare</button>
</form>
@if($products->isNotEmpty())
<div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:.82rem">
<thead><tr style="border-bottom:2px solid var(--line)">
<th style="padding:10px 12px;text-align:left;min-width:140px;color:var(--faint);font-size:.7rem;text-transform:uppercase">Spec</th>
@foreach($products as $p)
<th style="padding:10px 12px;text-align:center;min-width:180px;border-left:1px solid var(--line)">
@php($img = $p->images->first())
<img src="{{ $img?->publicUrl() ?: url('/images/products/neogiga-product-placeholder-2026.png') }}" style="width:80px;height:60px;object-fit:contain;background:#081527;border-radius:6px;margin:0 auto 8px" alt="">
<a href="/en/products/{{ $p->slug }}" style="color:var(--cyan);font-weight:600;font-size:.85rem">{{ \Illuminate\Support\Str::limit($p->name, 40) }}</a>
<div style="font-size:.7rem;color:var(--faint);margin-top:4px">{{ $p->mpn }}</div>
</th>@endforeach</tr></thead>
<tbody>
@foreach(['MPN'=>'mpn','SKU'=>'sku','Manufacturer'=>'manufacturer_name','Brand'=>'brand.name','Category'=>'category.name'] as $label => $field)
<tr style="border-bottom:1px solid var(--line)"><td style="padding:8px 12px;color:var(--muted);font-weight:600">{{ $label }}</td>
@foreach($products as $p)<td style="padding:8px 12px;text-align:center;border-left:1px solid var(--line);font-size:.8rem">{{ data_get($p, $field) ?: '—' }}</td>@endforeach</tr>@endforeach
<tr style="border-bottom:1px solid var(--line)"><td style="padding:8px 12px;color:var(--muted);font-weight:600">Stock</td>
@foreach($products as $p)<td style="padding:8px 12px;text-align:center;border-left:1px solid var(--line);font-size:.8rem">{{ ($p->stock_quantity ?? 0) > 0 ? number_format($p->stock_quantity).' in stock' : 'RFQ' }}</td>@endforeach</tr>
<tr style="border-bottom:1px solid var(--line)"><td style="padding:8px 12px;color:var(--muted);font-weight:600">Price</td>
@foreach($products as $p)<td style="padding:8px 12px;text-align:center;border-left:1px solid var(--line);font-size:.8rem">{{ $p->base_price ? '$'.number_format((float)$p->base_price, 2) : 'RFQ' }}</td>@endforeach</tr>
@foreach($specs as $attr => $group)
<tr style="border-bottom:1px solid var(--line)"><td style="padding:8px 12px;color:var(--muted);font-weight:600;font-size:.78rem">{{ $attr }}</td>
@foreach($products as $p)@php($m = $group->where('product_id', $p->id)->first())<td style="padding:8px 12px;text-align:center;border-left:1px solid var(--line);font-size:.78rem">{{ $m->value ?? $m->attribute_value ?? '—' }}</td>@endforeach</tr>@endforeach
</tbody></table></div>
@elseif(request()->has('p'))
<div class="card" style="padding:40px;text-align:center"><p style="color:var(--muted)">No products found.</p></div>
@endif
</div></section>
@endsection
