<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Invoice — NeoGiga</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f0f23; color: #e0e0e0; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 480px; width: 100%; padding: 24px; }
        .card { background: #1a1a2e; border: 1px solid #2a2a4a; border-radius: 12px; padding: 32px; text-align: center; }
        .logo { font-size: 24px; font-weight: 700; color: #e94560; margin-bottom: 24px; }
        .icon { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 32px; }
        .icon.valid { background: #065f46; color: #34d399; }
        .icon.invalid { background: #7f1d1d; color: #f87171; }
        h1 { font-size: 20px; margin-bottom: 8px; }
        .subtitle { color: #888; font-size: 14px; margin-bottom: 24px; }
        .details { text-align: left; background: #0f0f23; border-radius: 8px; padding: 16px; margin-top: 20px; }
        .detail-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #2a2a4a; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #888; font-size: 13px; }
        .detail-value { font-weight: 600; font-size: 13px; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-valid { background: #065f46; color: #34d399; }
        .badge-invalid { background: #7f1d1d; color: #f87171; }
        .badge-paid { background: #065f46; color: #34d399; }
        .badge-pending { background: #78350f; color: #fbbf24; }
        .badge-issued { background: #1e3a5f; color: #60a5fa; }
        .note { color: #666; font-size: 12px; margin-top: 20px; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">NeoGiga</div>

            @if($valid)
                <div class="icon valid">&#10003;</div>
                <h1 style="color:#34d399">Invoice Verified</h1>
                <p class="subtitle">This invoice is authentic and was issued by NeoGiga.</p>

                <div class="details">
                    <div class="detail-row">
                        <span class="detail-label">Invoice Number</span>
                        <span class="detail-value">{{ $invoice_number }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">
                            <span class="badge badge-{{ $status }}">{{ ucfirst($status) }}</span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Issue Date</span>
                        <span class="detail-value">{{ $issued_at?->format('M d, Y') ?? '—' }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total Amount</span>
                        <span class="detail-value">{{ $currency_code }} {{ number_format($total_amount, 2) }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Marketplace</span>
                        <span class="detail-value">{{ $marketplace }}</span>
                    </div>
                </div>
            @else
                <div class="icon invalid">&#10007;</div>
                <h1 style="color:#f87171">Verification Failed</h1>
                <p class="subtitle">{{ $reason }}</p>

                <div class="note">
                    If you believe this is an error, please contact NeoGiga support with invoice number: <strong>{{ $invoiceNumber }}</strong>
                </div>
            @endif

            <div class="note">
                Verification is privacy-safe — only status, date, and total are shown.
                <br>Personal details are never exposed through public verification.
            </div>
        </div>
    </div>
</body>
</html>
