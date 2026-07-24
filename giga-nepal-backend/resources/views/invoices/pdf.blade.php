<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 11px; color: #333; line-height: 1.5; }
        .invoice-box { max-width: 800px; margin: 0 auto; padding: 30px; }
        table { width: 100%; border-collapse: collapse; }
        .header { background: #1a1a2e; color: #ffffff; padding: 24px 30px; margin: -30px -30px 30px; }
        .header h1 { font-size: 22px; font-weight: 700; margin: 0; }
        .header .subtitle { font-size: 11px; color: #aaa; margin-top: 4px; }
        .meta-table td { padding: 6px 0; vertical-align: top; }
        .meta-table .label { font-weight: 600; color: #666; width: 120px; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 10px; font-weight: 600; text-transform: uppercase; }
        .status-issued { background: #dbeafe; color: #1e40af; }
        .status-paid { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-credit_note { background: #fee2e2; color: #991b1b; }
        .items-table { margin: 24px 0; }
        .items-table th { background: #f5f5f5; padding: 10px 12px; text-align: left; font-weight: 600; font-size: 10px; text-transform: uppercase; color: #666; border-bottom: 2px solid #ddd; }
        .items-table td { padding: 10px 12px; border-bottom: 1px solid #eee; }
        .items-table .text-right { text-align: right; }
        .totals { margin-top: 20px; }
        .totals table { width: 300px; margin-left: auto; }
        .totals td { padding: 6px 12px; }
        .totals .label { text-align: right; color: #666; }
        .totals .value { text-align: right; font-weight: 600; }
        .totals .total-row td { border-top: 2px solid #1a1a2e; font-size: 14px; font-weight: 700; padding-top: 10px; }
        .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 10px; color: #888; }
        .footer .notes { margin-bottom: 16px; }
        .verification { margin-top: 16px; padding: 12px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; font-size: 10px; }
        .verification strong { color: #1a1a2e; }
        .credit-note-banner { background: #fee2e2; color: #991b1b; padding: 12px; text-align: center; font-weight: 600; font-size: 13px; margin-bottom: 20px; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        {{-- Header --}}
        <table class="header">
            <tr>
                <td>
                    <h1>{{ $marketplace->name ?? 'NeoGiga' }}</h1>
                    <div class="subtitle">Global Electronics Engineering Marketplace</div>
                </td>
                <td style="text-align:right;vertical-align:top">
                    <div style="font-size:24px;font-weight:700;color:#e94560">INVOICE</div>
                    @if($invoice->status === 'credit_note')
                        <div style="font-size:12px;color:#fca5a5;margin-top:4px">CREDIT NOTE</div>
                    @endif
                </td>
            </tr>
        </table>

        @if($invoice->status === 'credit_note')
            <div class="credit-note-banner">
                CREDIT NOTE — Original: {{ $invoice->creditNote?->invoice_number ?? 'N/A' }}
            </div>
        @endif

        {{-- Invoice Meta --}}
        <table class="meta-table">
            <tr>
                <td style="width:50%">
                    <table>
                        <tr>
                            <td class="label">Invoice Number</td>
                            <td><strong>{{ $invoice->invoice_number }}</strong></td>
                        </tr>
                        <tr>
                            <td class="label">Status</td>
                            <td>
                                <span class="status-badge status-{{ $invoice->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $invoice->status)) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Issue Date</td>
                            <td>{{ $invoice->issued_at?->format('M d, Y') ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Due Date</td>
                            <td>{{ $invoice->due_at?->format('M d, Y') ?? '—' }}</td>
                        </tr>
                        @if($invoice->paid_at)
                        <tr>
                            <td class="label">Paid Date</td>
                            <td>{{ $invoice->paid_at->format('M d, Y') }}</td>
                        </tr>
                        @endif
                    </table>
                </td>
                <td style="width:50%;text-align:right">
                    <table style="margin-left:auto">
                        @if($vendor)
                        <tr>
                            <td class="label" style="text-align:right">Vendor</td>
                            <td style="text-align:left">{{ $vendor->name ?? '—' }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="label" style="text-align:right">Currency</td>
                            <td style="text-align:left">{{ $invoice->currency_code }}</td>
                        </tr>
                        @if($invoice->order_id)
                        <tr>
                            <td class="label" style="text-align:right">Order</td>
                            <td style="text-align:left">#{{ $invoice->order_id }}</td>
                        </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        {{-- Billing & Shipping --}}
        <table style="margin-top:24px">
            <tr>
                <td style="width:50%;vertical-align:top">
                    <div style="font-weight:600;color:#666;font-size:10px;text-transform:uppercase;margin-bottom:8px">Bill To</div>
                    <div><strong>{{ $invoice->billing_name ?? '—' }}</strong></div>
                    @if($invoice->billing_email)
                        <div>{{ $invoice->billing_email }}</div>
                    @endif
                    @if($invoice->billing_address)
                        <div style="color:#666">{{ nl2br(e($invoice->billing_address)) }}</div>
                    @endif
                </td>
                <td style="width:50%;vertical-align:top">
                    <div style="font-weight:600;color:#666;font-size:10px;text-transform:uppercase;margin-bottom:8px">Ship To</div>
                    <div><strong>{{ $invoice->shipping_name ?? '—' }}</strong></div>
                    @if($invoice->shipping_address)
                        <div style="color:#666">{{ nl2br(e($invoice->shipping_address)) }}</div>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Items Table --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:5%">#</th>
                    <th style="width:40%">Description</th>
                    <th style="width:10%" class="text-right">Qty</th>
                    <th style="width:15%" class="text-right">Unit Price</th>
                    <th style="width:15%" class="text-right">Tax</th>
                    <th style="width:15%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if($item->product_sku)
                            <br><span style="color:#888;font-size:10px">SKU: {{ $item->product_sku }}</span>
                        @endif
                    </td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ number_format($item->tax_amount, 2) }}</td>
                    <td class="text-right"><strong>{{ number_format($item->total_amount, 2) }}</strong></td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;color:#888;padding:20px">No items</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Totals --}}
        <div class="totals">
            <table>
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="value">{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                @if($invoice->tax_amount > 0)
                <tr>
                    <td class="label">Tax (13%)</td>
                    <td class="value">{{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                @endif
                @if($invoice->shipping_amount > 0)
                <tr>
                    <td class="label">Shipping</td>
                    <td class="value">{{ number_format($invoice->shipping_amount, 2) }}</td>
                </tr>
                @endif
                @if($invoice->discount_amount > 0)
                <tr>
                    <td class="label">Discount</td>
                    <td class="value" style="color:#16a34a">-{{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td class="label">Total ({{ $invoice->currency_code }})</td>
                    <td class="value">{{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
            </table>
        </div>

        {{-- Footer --}}
        <div class="footer">
            @if($invoice->notes)
                <div class="notes">
                    <strong>Notes:</strong> {{ $invoice->notes }}
                </div>
            @endif

            <div class="verification">
                <strong>Verify this invoice:</strong> Visit <em>{{ $verificationUrl }}</em>
                <br>Scan the QR code or use invoice number <strong>{{ $invoice->invoice_number }}</strong> to verify authenticity.
                <br>Verification is privacy-safe — only status, date, and total are shown.
            </div>

            <div style="margin-top:16px;text-align:center;color:#aaa">
                &copy; {{ date('Y') }} {{ $marketplace->name ?? 'NeoGiga' }}. All rights reserved.
                <br>This invoice was generated electronically and is valid without signature.
            </div>
        </div>
    </div>
</body>
</html>
