<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $sale->sale_reference ?? '' }}</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f5f5f5; margin: 0; padding: 24px; }
        .receipt { max-width: 420px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        h1 { font-size: 1.1rem; margin: 0 0 8px; text-align: center; }
        .meta { color: #666; font-size: .85rem; text-align: center; margin-bottom: 16px; }
        .line { display: flex; justify-content: space-between; margin: 6px 0; font-size: .9rem; }
        .total { border-top: 1px dashed #ccc; margin-top: 12px; padding-top: 12px; font-weight: 700; }
        .qr { text-align: center; margin-top: 20px; }
        .qr img { max-width: 180px; }
    </style>
</head>
<body>
<div class="receipt">
    <h1>NeoGiga POS Receipt</h1>
    <div class="meta">{{ $sale->sale_reference ?? 'Sale' }} · {{ $sale->completed_at ?? $sale->created_at ?? now() }}</div>
    @foreach($sale->items ?? [] as $item)
    <div class="line"><span>{{ $item->product_name }} × {{ $item->quantity }}</span><span>${{ number_format($item->total_amount ?? 0, 2) }}</span></div>
    @endforeach
    <div class="line total"><span>Total</span><span>${{ number_format($sale->total_amount ?? 0, 2) }}</span></div>
    <div class="line"><span>Payment</span><span>{{ ucfirst($sale->payment_status ?? 'pending') }}</span></div>
    <div class="qr">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode(url('/pos/receipt/'.$token)) }}" alt="Receipt QR">
        <p style="font-size:.75rem;color:#666;margin-top:8px">Scan to verify receipt</p>
    </div>
</div>
</body>
</html>
