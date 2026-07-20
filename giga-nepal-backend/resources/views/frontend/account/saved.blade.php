@extends('frontend.layout')
@section('title', 'Saved Products — NeoGiga')

@section('content')
<div class="section" style="max-width:800px;margin:40px auto">
    <h1>Saved Products</h1>

    @if($saved->isEmpty())
        <div class="panel" style="padding:40px;text-align:center">
            <p class="sub">No saved products yet. Browse the catalog and click the heart icon to save items.</p>
            <a href="/en/products" class="btn btn-primary">Browse Products</a>
        </div>
    @else
        <div class="panel" style="padding:16px">
            @foreach($saved as $item)
                <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border)">
                    <div style="flex:1">
                        <a href="/en/products/{{ $item->slug }}"><strong>{{ $item->name }}</strong></a>
                        <div class="sub" style="font-size:12px">
                            {{ $item->mpn ?: $item->sku ?: '' }}
                            @if($item->list_name !== 'default')
                                · <span class="badge b-muted">{{ $item->list_name }}</span>
                            @endif
                            · Saved {{ \Carbon\Carbon::parse($item->saved_at)->diffForHumans() }}
                        </div>
                    </div>
                    @if($item->list_price)
                        <strong style="margin-right:12px">${{ number_format($item->list_price, 2) }}</strong>
                    @endif
                    <div style="display:flex;gap:6px">
                        <button class="btn btn-ghost btn-sm" onclick="removeSaved({{ $item->id }}, this.closest('div').parentElement)">Remove</button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<script>
async function removeSaved(productId, row) {
    await fetch('/api/v1/products/' + productId + '/save', {method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+ (document.querySelector('meta[name="api-token"]')?.content||'')}});
    row.remove();
    if (!document.querySelectorAll('[onclick^="removeSaved"]').length) location.reload();
}
</script>
@endsection
