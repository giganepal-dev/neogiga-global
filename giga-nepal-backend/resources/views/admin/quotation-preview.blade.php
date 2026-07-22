<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $quote->quote_number }} · NeoGiga Quotation</title>
    <style nonce="{{ $csp_nonce ?? '' }}">
        body{font-family:Arial,sans-serif;color:#0f172a;margin:32px;background:#fff}
        .head{display:flex;justify-content:space-between;gap:24px;border-bottom:2px solid #0f172a;padding-bottom:18px}
        h1{margin:0;font-size:28px}.muted{color:#64748b}.mono{font-family:Menlo,Consolas,monospace}
        table{width:100%;border-collapse:collapse;margin-top:24px}th,td{padding:10px;border-bottom:1px solid #e2e8f0;text-align:left}th{background:#f8fafc;font-size:12px;text-transform:uppercase;color:#64748b}.num{text-align:right}
        .totals{margin-left:auto;width:320px;margin-top:18px}.totals div{display:flex;justify-content:space-between;padding:6px 0}.grand{font-weight:700;border-top:2px solid #0f172a}
        .note{margin-top:24px;background:#eff6ff;border:1px solid #dbeafe;padding:12px;color:#1e3a8a}
        @media print{button{display:none}body{margin:18px}}
    </style>
</head>
<body>
    <button onclick="window.print()">Print / Save PDF</button>
    <div class="head">
        <div><h1>NeoGiga Quotation</h1><div class="muted">B2B marketplace quotation preview</div></div>
        <div>
            <div class="mono"><strong>{{ $quote->quote_number }}</strong></div>
            <div>Status: {{ $quote->status }}</div>
            <div>Valid until: {{ $quote->valid_until ?? '—' }}</div>
            <div>RFQ: {{ $rfq->rfq_number ?? '—' }}</div>
        </div>
    </div>
    <div style="margin-top:18px">
        <strong>Customer / Company</strong>
        <div>{{ $rfq->company_name ?? '—' }}</div>
        <div class="muted">{{ $rfq->contact_name ?? '' }} {{ $rfq->contact_email ?? '' }} {{ $rfq->contact_phone ?? '' }}</div>
    </div>
    <table>
        <thead><tr><th>Item</th><th>SKU</th><th class="num">Qty</th><th class="num">Unit</th><th class="num">Tax</th><th class="num">Line Total</th></tr></thead>
        <tbody>
        @forelse($items as $item)
            <tr><td>{{ $item->name }}</td><td class="mono">{{ $item->sku ?? '—' }}</td><td class="num">{{ number_format((float)$item->quantity, 3) }}</td><td class="num">{{ number_format((float)$item->unit_price, 4) }}</td><td class="num">{{ number_format((float)$item->tax_amount, 2) }}</td><td class="num">{{ number_format((float)$item->line_total, 2) }}</td></tr>
        @empty
            <tr><td colspan="6">No line items.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="totals">
        <div><span>Subtotal</span><span>{{ number_format((float)$quote->subtotal, 2) }} {{ $quote->currency }}</span></div>
        <div><span>Tax</span><span>{{ number_format((float)$quote->tax_total, 2) }} {{ $quote->currency }}</span></div>
        <div><span>Shipping</span><span>{{ number_format((float)$quote->shipping_total, 2) }} {{ $quote->currency }}</span></div>
        <div class="grand"><span>Grand Total</span><span>{{ number_format((float)$quote->grand_total, 2) }} {{ $quote->currency }}</span></div>
    </div>
    <div class="note">This preview is generated from admin quotation data. Verify prices, tax, stock availability, and validity before sending. Advisory only.</div>
</body>
</html>
