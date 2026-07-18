@extends('frontend.layout')
@section('title','Compare Products | NeoGiga')
@section('content')
<section style="padding:28px 0 64px"><div class="wrap">
<h1 class="page-title" style="margin:0 0 8px">Compare Products</h1>
<p class="lead" style="max-width:72ch">Select products to compare technical specifications side by side. Add MPNs or product names below.</p>

<form method="get" action="/en/compare" style="margin:20px 0">
    <input class="control" name="p" value="{{ implode(',', $slugs) }}" placeholder="Enter product slugs or MPNs, comma-separated (e.g., sunlord-gz1608d601tf-1002, ne555p)" style="font-family:monospace;font-size:.82rem;width:100%;max-width:600px;border:1px solid var(--line);border-radius:10px;min-height:44px;padding:8px 12px;background:var(--s1);color:var(--on)">
    <button class="btn btn-primary" type="submit" style="margin-top:8px">Compare</button>
</form>

@if($products->isNotEmpty())
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:.82rem">
            <thead>
                <tr style="border-bottom:2px solid var(--line)">
                    <th style="padding:10px 12px;text-align:left;min-width:140px;color:var(--faint);font-size:.7rem;text-transform:uppercase">Specification</th>
                    @foreach($products as $p)
                        <th style="padding:10px 12px;text-align:center;min-width:180px;border-left:1px solid var(--line)">
                            @php($img = $p->images->first())
                            <img src="{{ $img?->publicUrl() ?: url('/images/products/neogiga-product-placeholder-2026.png') }}" style="width:80px;height:60px;object-fit:contain;background:#081527;border-radius:6px;margin:0 auto 8px">
                            <a href="/en/products/{{ $p->slug }}" style="color:var(--cyan);font-weight:600;font-size:.85rem">{{ \Illuminate\Support\Str::limit($p->name, 40) }}</a>
                            <div style="font-size:.7rem;color:var(--faint);margin-top:4px">{{ $p->mpn }} · {{ $p->brand->name ?? '' }}</div>
                            @if($p->base_price)<div style="font-weight:700;margin-top:4px">${{ number_format($p->base_price, 2) }}</div>@endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @php $rows = ['mpn'=>'MPN','sku'=>'SKU','manufacturer_name'=>'Manufacturer','brand.name'=>'Brand','category.name'=>'Category','stock_quantity'=>'Stock','base_price'=>'Price','description'=>'Description']; @endphp
                @foreach($rows as $field => $label)
                    <tr style="border-bottom:1px solid var(--line)">
                        <td style="padding:8px 12px;color:var(--muted);font-weight:600">{{ $label }}</td>
                        @foreach($products as $p)
                            <td style="padding:8px 12px;text-align:center;border-left:1px solid var(--line);font-size:.8rem">
                                @php $val = data_get($p, $field); @endphp
                                @if($field === 'base_price') ${{ number_format((float)$val, 2) }}
                                @elseif($field === 'stock_quantity') {{ $val > 0 ? number_format($val).' in stock' : 'RFQ' }}
                                @elseif($field === 'description') {{ \Illuminate\Support\Str::limit(strip_tags($val), 100) }}
                                @else {{ $val ?: '—' }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                @foreach($specs as $attr => $group)
                    <tr style="border-bottom:1px solid var(--line)">
                        <td style="padding:8px 12px;color:var(--muted);font-weight:600;font-size:.78rem">{{ $attr }}</td>
                        @foreach($products as $p)
                            @php $match = $group->firstWhere('product_id', $p->id); @endphp
                            <td style="padding:8px 12px;text-align:center;border-left:1px solid var(--line);font-size:.78rem">{{ $match->value ?? $match->attribute_value ?? '—' }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@elseif(request()->has('p'))
    <div class="card" style="padding:40px;text-align:center"><p style="color:var(--muted)">No products found. Try entering product slugs from the URL.</p></div>
@endif
</div></section>
@endsection
