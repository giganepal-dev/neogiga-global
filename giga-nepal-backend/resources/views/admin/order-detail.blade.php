@extends('admin.layout')
@section('title','Order '.$order->order_number)
@section('crumb','Order detail')
@section('content')

@php
    $statusBadge = fn($s) => match($s) {
        'delivered' => 'b-ok',
        'confirmed', 'processing', 'shipped' => 'b-info',
        default => 'b-muted',
    };
    $statuses = ['pending','confirmed','processing','shipped','delivered','cancelled','refunded','failed'];
    $addr = function ($a) {
        if (is_string($a)) { $d = json_decode($a, true); $a = $d ?: $a; }
        if (is_array($a)) { return implode(', ', array_filter(array_map(fn($v) => is_scalar($v) ? (string)$v : null, $a))); }
        return $a ?: '—';
    };
@endphp

<div class="card" style="margin-bottom:16px">
    <div class="card-h">
        <div>
            <h2 class="mono">{{ $order->order_number }}</h2>
            <div class="sub">Placed {{ $order->created_at?->format('Y-m-d H:i') }} · {{ $order->marketplace->name ?? '—' }}</div>
        </div>
        <div style="display:flex;gap:8px;align-items:center">
            <span class="badge {{ $statusBadge($order->status) }}">{{ $order->status }}</span>
            <span class="badge {{ $order->payment_status === 'paid' ? 'b-ok' : 'b-muted' }}">payment: {{ $order->payment_status }}</span>
            <a class="btn btn-ghost" href="/admin/orders/{{ $order->id }}/invoice" target="_blank">Invoice</a>
            <a class="btn btn-ghost" href="/admin/orders">Back</a>
        </div>
    </div>
    <div style="padding:16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px">
        <div><div class="sub">Customer</div><strong>{{ $order->user->name ?? '—' }}</strong><div class="sub">{{ $order->user->email ?? '' }}</div></div>
        <div><div class="sub">Billing</div>{{ $addr($order->billing_address) }}</div>
        <div><div class="sub">Shipping</div>{{ $addr($order->shipping_address) }}</div>
        <div><div class="sub">Tracking</div>{{ $order->tracking_number ?? '—' }}</div>
    </div>
</div>

<div class="grid dashboard-split">
    <div class="card">
        <div class="card-h"><h2>Items</h2></div>
        <div class="scroll-x"><table class="tbl">
            <thead><tr><th>Product</th><th>SKU</th><th class="num">Qty</th><th class="num">Unit</th><th class="num">Total</th></tr></thead>
            <tbody>
            @forelse ($order->items as $it)
                <tr>
                    <td><strong>{{ $it->product_name }}</strong></td>
                    <td class="mono">{{ $it->product_sku ?? '—' }}</td>
                    <td class="num tnum">{{ number_format($it->quantity) }}</td>
                    <td class="num tnum">{{ number_format($it->unit_price, 2) }}</td>
                    <td class="num tnum">{{ number_format($it->total_price, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="empty"><h3>No items</h3></div></td></tr>
            @endforelse
            </tbody>
            <tfoot>
                <tr><td colspan="4" class="num">Subtotal</td><td class="num tnum">{{ number_format($order->subtotal, 2) }}</td></tr>
                <tr><td colspan="4" class="num">Discount</td><td class="num tnum">-{{ number_format($order->discount_total, 2) }}</td></tr>
                <tr><td colspan="4" class="num">Tax</td><td class="num tnum">{{ number_format($order->tax_total, 2) }}</td></tr>
                <tr><td colspan="4" class="num">Shipping</td><td class="num tnum">{{ number_format($order->shipping_total, 2) }}</td></tr>
                <tr><td colspan="4" class="num"><strong>Grand total</strong></td><td class="num tnum"><strong>{{ number_format($order->grand_total, 2) }} {{ $order->currency_code }}</strong></td></tr>
                <tr><td colspan="4" class="num">Paid / Due</td><td class="num tnum">{{ number_format($order->amount_paid, 2) }} / {{ number_format($order->amount_due, 2) }}</td></tr>
            </tfoot>
        </table></div>
    </div>

    <div>
        <div class="card" style="margin-bottom:16px">
            <div class="card-h"><h2>Update status</h2></div>
            <form method="post" action="/admin/orders/{{ $order->id }}/status" class="form-stack" style="padding:16px">@csrf
                <select class="control" name="status" required>
                    @foreach ($statuses as $s)<option value="{{ $s }}" @selected($order->status===$s)>{{ ucfirst($s) }}</option>@endforeach
                </select>
                <input class="control" name="notes" maxlength="1000" placeholder="Note (optional, kept in audit trail)">
                <button class="btn btn-primary" type="submit">Save status</button>
            </form>
        </div>

        <div class="card" style="margin-bottom:16px">
            <div class="card-h"><h2>Fulfilment</h2><span class="sub">Carrier and shipment notes</span></div>
            <form method="post" action="/admin/orders/{{ $order->id }}/tracking" class="form-stack" style="padding:16px">@csrf
                <input class="control" name="carrier" maxlength="120" value="{{ $order->carrier }}" placeholder="Carrier">
                <input class="control" name="tracking_number" maxlength="190" value="{{ $order->tracking_number }}" placeholder="Tracking number">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                    <input class="control" type="date" name="shipped_at" value="{{ optional($order->shipped_at)->format('Y-m-d') }}">
                    <input class="control" type="date" name="delivered_at" value="{{ optional($order->delivered_at)->format('Y-m-d') }}">
                </div>
                <textarea class="control" name="vendor_notes" rows="3" maxlength="2000" placeholder="Internal fulfilment notes">{{ $order->vendor_notes }}</textarea>
                <button class="btn btn-primary" type="submit">Save fulfilment</button>
            </form>
        </div>

        <div class="card" style="margin-bottom:16px">
            <div class="card-h"><h2>Payments</h2></div>
            <div class="scroll-x"><table class="tbl">
                <thead><tr><th>Method</th><th class="num">Amount</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($order->payments as $p)
                    <tr>
                        <td>{{ $p->method ?? $p->payment_method ?? '—' }}</td>
                        <td class="num tnum">{{ number_format($p->amount, 2) }}</td>
                        <td><span class="badge {{ ($p->status ?? '') === 'captured' ? 'b-ok' : 'b-muted' }}">{{ $p->status ?? '—' }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="3"><div class="empty"><h3>No payments</h3></div></td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>

        <div class="card">
            <div class="card-h"><h2>Timeline</h2><span class="sub">Status audit trail</span></div>
            <div style="padding:12px 16px">
            @forelse ($history as $h)
                <div style="padding:8px 0;border-bottom:1px solid var(--line)">
                    <span class="badge {{ $statusBadge($h->status) }}">{{ $h->status }}</span>
                    <span class="sub">from {{ $h->previous_status ?? '—' }} · {{ $h->created_at }}</span>
                    @if($h->notes)<div class="sub">{{ $h->notes }}</div>@endif
                </div>
            @empty
                <div class="empty"><h3>No status changes recorded</h3></div>
            @endforelse
            </div>
        </div>
    </div>
</div>

@endsection
