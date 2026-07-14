@extends('admin.layout')
@section('title','POS Sale')
@section('crumb','Operations / POS / Sale Detail')

@section('page_actions')
<a class="btn btn-ghost" href="/admin/pos">Back to POS</a>
<details class="modal">
    <summary class="btn btn-primary">Record Refund</summary>
    <div class="modal-panel">
        <div class="modal-h"><h3>Record Refund</h3><span class="badge b-warn">local log</span></div>
        <form class="modal-b form-stack" method="post" action="/admin/pos/sales/{{ $sale->id }}/refunds">
            @csrf
            <input type="hidden" name="idempotency_key" value="pos-refund-{{ \Illuminate\Support\Str::uuid() }}">
            <div class="form-grid">
                <div class="field"><label>Amount</label><input class="control" type="number" step="0.01" min="0.01" max="{{ $sale->total_amount }}" name="amount" required></div>
                <div class="field"><label>Method</label><select class="control" name="refund_method">@forelse($paymentMethods as $m)<option value="{{ $m->code }}">{{ $m->name }}</option>@empty<option value="cash">Cash</option>@endforelse</select></div>
            </div>
            <div class="field"><label>Reason</label><textarea class="control" name="reason" required></textarea></div>
            <button class="btn btn-primary" type="submit">Record Refund</button>
        </form>
    </div>
</details>
@endsection

@section('content')
<div class="grid kpis">
    <div class="kpi"><div class="t">Sale</div><div class="v mono">{{ $sale->sale_reference ?? ('#'.$sale->id) }}</div><div class="s">{{ $sale->completed_at ?: $sale->created_at }}</div></div>
    <div class="kpi"><div class="t">Total</div><div class="v tnum">{{ number_format((float) $sale->total_amount, 2) }}</div><div class="s">{{ $sale->currency_code }}</div></div>
    <div class="kpi"><div class="t">Payment</div><div class="v">{{ ucfirst(str_replace('_',' ', $sale->payment_status)) }}</div><div class="s">{{ $sale->status }}</div></div>
    <div class="kpi"><div class="t">Terminal</div><div class="v">{{ $sale->terminal_name ?: '—' }}</div><div class="s">{{ $sale->session_number ?: 'no session' }}</div></div>
</div>

<section class="card">
    <div class="card-h"><h2>Customer & Location</h2><span class="badge b-info">sale context</span></div>
    <div style="padding:16px" class="form-grid">
        <div><div class="sub">Customer</div><strong>{{ $sale->customer_name ?: 'Walk-in customer' }}</strong><div class="sub">{{ $sale->customer_email ?: $sale->customer_phone }}</div></div>
        <div><div class="sub">Warehouse</div><strong>{{ $sale->warehouse_name ?: ('#'.$sale->warehouse_id) }}</strong></div>
        <div><div class="sub">Subtotal</div><strong class="tnum">{{ number_format((float) $sale->subtotal, 2) }}</strong></div>
        <div><div class="sub">Tax / Discount</div><strong class="tnum">{{ number_format((float) $sale->tax_amount, 2) }} / {{ number_format((float) $sale->discount_amount, 2) }}</strong></div>
    </div>
</section>

<div class="grid split stack-gap">
    <section class="card">
        <div class="card-h"><h2>Items</h2><span class="badge b-info">{{ number_format($items->count()) }} rows</span></div>
        <div class="scroll-x">
            <table class="tbl">
                <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>
                <tbody>
                @forelse($items as $item)
                    <tr><td>{{ $item->product_name ?: ('#'.$item->product_id) }}</td><td class="mono">{{ $item->product_sku }}</td><td class="tnum">{{ number_format((float) $item->quantity, 3) }}</td><td class="tnum">{{ number_format((float) $item->unit_price, 2) }}</td><td class="tnum">{{ number_format((float) $item->total_amount, 2) }}</td></tr>
                @empty
                    <tr><td colspan="5"><div class="empty"><h3>No line items</h3></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <div class="card-h"><h2>Payments</h2><span class="badge b-info">local records</span></div>
        <div class="scroll-x">
            <table class="tbl">
                <thead><tr><th>Method</th><th>Amount</th><th>Status</th><th>Reference</th></tr></thead>
                <tbody>
                @forelse($payments as $payment)
                    <tr><td>{{ $payment->payment_method }}</td><td class="tnum">{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency_code }}</td><td><span class="badge b-muted">{{ $payment->status }}</span></td><td class="mono">{{ $payment->payment_reference ?: '—' }}</td></tr>
                @empty
                    <tr><td colspan="4"><div class="empty"><h3>No payment rows</h3></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<section class="card stack-gap">
    <div class="card-h"><h2>Refunds</h2><span class="badge b-muted">gateway not contacted</span></div>
    <div class="scroll-x">
        <table class="tbl">
            <thead><tr><th>Amount</th><th>Method</th><th>Status</th><th>Reason</th><th>Processed</th></tr></thead>
            <tbody>
            @forelse($refunds as $refund)
                <tr><td class="tnum">{{ number_format((float) $refund->amount, 2) }} {{ $refund->currency_code }}</td><td>{{ $refund->refund_method }}</td><td><span class="badge b-muted">{{ $refund->status }}</span></td><td>{{ $refund->reason }}</td><td>{{ $refund->processed_at }}</td></tr>
            @empty
                <tr><td colspan="5"><div class="empty"><h3>No refunds recorded</h3></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
