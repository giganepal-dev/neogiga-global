<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Invoice {{ $order->order_number }} — NeoGiga</title>
<meta name="robots" content="noindex">
<style>
    :root{--navy:#0F172A;--cyan:#19D3F5;--gold:#EAB308;--gray:#64748B;--line:#E2E8F0}
    *{box-sizing:border-box}
    body{font:14px/1.5 -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#0F172A;margin:0;background:#F8FAFC}
    .sheet{max-width:800px;margin:24px auto;background:#fff;border:1px solid var(--line);border-radius:12px;padding:40px}
    .head{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid var(--navy);padding-bottom:20px;margin-bottom:24px}
    .brand{font-size:1.6rem;font-weight:800;color:var(--navy)}.brand span{color:var(--cyan)}
    .brand small{display:block;font-size:.75rem;font-weight:400;color:var(--gray)}
    .inv-meta{text-align:right}.inv-meta h1{margin:0;font-size:1.2rem;color:var(--navy)}
    .inv-meta .mono{font-family:ui-monospace,Menlo,monospace}
    .parties{display:flex;gap:40px;margin-bottom:24px}.parties .sub{color:var(--gray);font-size:.8rem;text-transform:uppercase;letter-spacing:.05em}
    table{width:100%;border-collapse:collapse;margin-bottom:24px}
    th{background:var(--navy);color:#fff;text-align:left;padding:10px 12px;font-size:.8rem;text-transform:uppercase;letter-spacing:.04em}
    td{padding:10px 12px;border-bottom:1px solid var(--line)}
    .num{text-align:right}
    tfoot td{border:none;padding:6px 12px}.grand td{border-top:2px solid var(--navy);font-weight:800;font-size:1.05rem}
    .badge{display:inline-block;padding:3px 10px;border-radius:999px;font-size:.75rem;font-weight:600}
    .paid{background:#ECFDF5;color:#065F46}.unpaid{background:#FEF9C3;color:#854D0E}
    .foot{color:var(--gray);font-size:.8rem;border-top:1px solid var(--line);padding-top:16px}
    .print-hint{max-width:800px;margin:16px auto 0;text-align:right;color:var(--gray);font-size:.85rem}
    @media print{.print-hint{display:none}.sheet{border:none;margin:0;border-radius:0}body{background:#fff}}
</style>
</head>
<body>
<div class="print-hint">Press ⌘P / Ctrl+P to print or save this invoice as PDF.</div>
<div class="sheet">
    <div class="head">
        <div class="brand">Neo<span>Giga</span><small>Global Engineering Marketplace · neogiga.com</small></div>
        <div class="inv-meta">
            <h1>INVOICE</h1>
            <div class="mono">{{ $order->order_number }}</div>
            <div>{{ $order->created_at?->format('Y-m-d') }}</div>
            <span class="badge {{ $order->payment_status === 'paid' ? 'paid' : 'unpaid' }}">{{ strtoupper($order->payment_status) }}</span>
        </div>
    </div>

    <div class="parties">
        <div>
            <div class="sub">Billed to</div>
            <strong>{{ $order->user->name ?? 'Customer' }}</strong><br>
            {{ $order->user->email ?? '' }}
        </div>
        <div>
            <div class="sub">Marketplace</div>
            {{ $order->marketplace->name ?? 'NeoGiga Global' }}<br>
            Currency: {{ $order->currency_code }}
        </div>
        <div>
            <div class="sub">Order status</div>
            {{ ucfirst($order->status) }}<br>
            @if($order->tracking_number)Tracking: {{ $order->tracking_number }}@endif
        </div>
    </div>

    <table>
        <thead><tr><th>Item</th><th>SKU</th><th class="num">Qty</th><th class="num">Unit price</th><th class="num">Total</th></tr></thead>
        <tbody>
        @foreach ($order->items as $it)
            <tr>
                <td>{{ $it->product_name }}</td>
                <td>{{ $it->product_sku ?? '—' }}</td>
                <td class="num">{{ number_format($it->quantity) }}</td>
                <td class="num">{{ number_format($it->unit_price, 2) }}</td>
                <td class="num">{{ number_format($it->total_price, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr><td colspan="4" class="num">Subtotal</td><td class="num">{{ number_format($order->subtotal, 2) }}</td></tr>
            @if((float)$order->discount_total > 0)<tr><td colspan="4" class="num">Discount</td><td class="num">-{{ number_format($order->discount_total, 2) }}</td></tr>@endif
            <tr><td colspan="4" class="num">Tax</td><td class="num">{{ number_format($order->tax_total, 2) }}</td></tr>
            <tr><td colspan="4" class="num">Shipping</td><td class="num">{{ number_format($order->shipping_total, 2) }}</td></tr>
            <tr class="grand"><td colspan="4" class="num">Grand total</td><td class="num">{{ number_format($order->grand_total, 2) }} {{ $order->currency_code }}</td></tr>
            <tr><td colspan="4" class="num">Amount paid</td><td class="num">{{ number_format($order->amount_paid, 2) }}</td></tr>
            <tr><td colspan="4" class="num">Amount due</td><td class="num">{{ number_format($order->amount_due, 2) }}</td></tr>
        </tfoot>
    </table>

    <div class="foot">
        Thank you for your business. · NeoGiga — semiconductors, electronics, IoT, robotics &amp; engineering tools.<br>
        This document was generated from order {{ $order->order_number }} on {{ now()->format('Y-m-d H:i') }} UTC.
    </div>
</div>
</body>
</html>
