@extends('frontend.layout')
@section('title','Order Placed — NeoGiga')
@section('description','Your NeoGiga order has been received and is being processed.')
@section('content')
<div style="max-width:680px;margin:40px auto;padding:0 16px">
    <div style="text-align:center;padding:40px 32px;background:var(--s1);border:1px solid var(--line);border-radius:16px">
        <div style="font-size:3rem;margin-bottom:12px">✅</div>
        <h1 style="font-size:1.5rem;margin:0 0 8px;color:var(--on)">Order Placed Successfully</h1>
        <div style="display:inline-block;padding:8px 18px;background:var(--bg2);border:1px solid var(--line);border-radius:8px;font-family:ui-monospace,monospace;font-size:1.1rem;font-weight:700;color:var(--cyan);margin:12px 0">{{ $order->order_number }}</div>
        <p style="color:var(--muted);margin:0 0 20px;line-height:1.6">Thank you for your order. NeoGiga has received it and will notify you as it progresses.</p>

        <div style="display:grid;gap:8px;max-width:400px;margin:16px auto;text-align:left">
            <div style="display:flex;justify-content:space-between;gap:16px;padding:8px 0;border-bottom:1px solid var(--line);font-size:.9rem"><span style="color:var(--muted)">Order Number</span><strong>{{ $order->order_number }}</strong></div>
            <div style="display:flex;justify-content:space-between;gap:16px;padding:8px 0;border-bottom:1px solid var(--line);font-size:.9rem"><span style="color:var(--muted)">Date</span><strong>{{ $order->created_at?->format('Y-m-d H:i') }}</strong></div>
            <div style="display:flex;justify-content:space-between;gap:16px;padding:8px 0;border-bottom:1px solid var(--line);font-size:.9rem"><span style="color:var(--muted)">Items</span><strong>{{ $order->items->sum('quantity') }}</strong></div>
            <div style="display:flex;justify-content:space-between;gap:16px;padding:8px 0;border-bottom:1px solid var(--line);font-size:.9rem"><span style="color:var(--muted)">Total</span><strong>{{ $order->currency_code }} {{ number_format((float)$order->grand_total, 2) }}</strong></div>
            <div style="display:flex;justify-content:space-between;gap:16px;padding:8px 0;border-bottom:1px solid var(--line);font-size:.9rem"><span style="color:var(--muted)">Status</span><strong>{{ ucfirst($order->status) }}</strong></div>
            @if($order->payment_status)<div style="display:flex;justify-content:space-between;gap:16px;padding:8px 0;border-bottom:1px solid var(--line);font-size:.9rem"><span style="color:var(--muted)">Payment</span><strong>{{ ucfirst($order->payment_status) }}</strong></div>@endif
        </div>

        @if($order->payment_status === 'pending')
            <p style="color:var(--muted);font-size:.9rem;margin:12px 0">Your order has been created and is awaiting payment confirmation.</p>
        @endif

        <p style="color:var(--muted);font-size:.88rem;margin:8px 0">📨 Confirmation email queued</p>

        <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin:20px 0">
            <a class="btn btn-primary" href="/en/account/orders">View Order</a>
            <a class="btn btn-ghost" href="/en/products">Continue Shopping</a>
            <a class="btn btn-ghost" href="/en/rfq">Submit RFQ</a>
        </div>

        <div style="margin-top:20px;font-size:.82rem;color:var(--faint)">
            <p>Need help? <a href="/en/contact" style="color:var(--cyan)">Contact NeoGiga Support</a></p>
        </div>
    </div>

    @if($order->items->isNotEmpty())
    <div class="panel" style="margin-top:20px;padding:20px">
        <h3 style="margin:0 0 12px">Order Items</h3>
        <table style="width:100%;border-collapse:collapse;font-size:.9rem">
            @foreach($order->items as $item)
            <tr style="border-bottom:1px solid var(--line)">
                <td style="padding:10px 0;color:var(--on)">{{ $item->product_name }}</td>
                <td style="padding:10px 0;text-align:right;color:var(--muted)">{{ $item->quantity }} × {{ $order->currency_code }} {{ number_format((float)$item->unit_price, 2) }}</td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif
</div>
@endsection
