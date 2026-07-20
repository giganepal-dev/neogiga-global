@extends('b2b.layout')
@section('title','Quotation '.$quote->quotation_number)
@section('content')
<div class="page-intro page-intro--row">
    <div>
        <h1>{{ $quote->quotation_number }}</h1>
        <p>{{ ucfirst($account->type ?? 'institutional') }} quotation · Valid until {{ $quote->valid_until?->format('M j, Y') }}</p>
    </div>
    <span class="badge {{ $quote->status === 'accepted' ? 'b-ok' : 'b-info' }}">{{ $quote->status }}</span>
</div>

<div class="kpi-grid">
    <div class="kpi"><div class="t">Subtotal</div><div class="v tnum">{{ $quote->currency_code }} {{ number_format($quote->subtotal, 2) }}</div></div>
    <div class="kpi"><div class="t">Tax</div><div class="v tnum">{{ $quote->currency_code }} {{ number_format($quote->tax_total, 2) }}</div></div>
    <div class="kpi"><div class="t">Shipping</div><div class="v tnum">{{ $quote->currency_code }} {{ number_format($quote->shipping_total, 2) }}</div></div>
    <div class="kpi"><div class="t">Grand total</div><div class="v tnum">{{ $quote->currency_code }} {{ number_format($quote->grand_total, 2) }}</div></div>
</div>

@if(data_get($quote->metadata, 'institutional_discount_percent'))
    <div class="card"><div class="card-body"><span class="badge b-ok">Institutional discount applied: {{ data_get($quote->metadata, 'institutional_discount_percent') }}%</span></div></div>
@endif

<div class="card">
    <div class="card-h"><h2>Line items</h2></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Item</th><th>SKU</th><th>Qty</th><th class="num">Unit</th><th class="num">Line total</th></tr></thead>
        <tbody>
            @foreach($quote->items as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td class="mono sub">{{ $item->sku ?? '—' }}</td>
                <td class="tnum">{{ $item->quantity }}</td>
                <td class="num tnum">{{ number_format($item->unit_price, 2) }}</td>
                <td class="num tnum">{{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table></div>
</div>

<div class="card">
    <div class="card-h"><h2>Actions</h2></div>
    <div class="card-body actions-row">
        @if(in_array($quote->status, ['sent', 'draft'], true))
            <form method="post" action="/b2b/quotations/{{ $quote->id }}/accept">@csrf
                <button type="submit" class="btn btn-primary">Accept quotation</button>
            </form>
        @endif

        @if($quote->status === 'accepted' && $quote->payment_status === 'unlocked' && ! $quote->order_id)
            <form method="post" action="/b2b/quotations/{{ $quote->id }}/pay" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                @csrf
                <select name="payment_method" class="control" required style="min-width:200px">
                    <option value="">Select regional payment method</option>
                    @foreach($paymentMethods as $method)
                        <option value="{{ $method['code'] }}">{{ $method['name'] }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary">Proceed to payment</button>
            </form>
        @endif

        @if($quote->order_id)
            <a href="/b2b/orders" class="btn btn-ghost">View order</a>
        @endif
    </div>
</div>
@endsection
