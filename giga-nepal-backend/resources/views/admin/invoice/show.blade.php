@extends('admin.layout')
@section('title', 'Invoice ' . $invoice->invoice_number)
@section('crumb', 'Orders / Invoices / ' . $invoice->invoice_number)

@section('content')
<div class="page-head">
    <div>
        <h2>Invoice {{ $invoice->invoice_number }}</h2>
        <p style="color:var(--muted)">
            @php
                $badgeClass = match($invoice->status) {
                    'paid' => 'b-ok',
                    'cancelled' => 'b-danger',
                    'credit_note' => 'b-warn',
                    default => 'b-muted',
                };
            @endphp
            <span class="badge {{ $badgeClass }}">{{ str_replace('_',' ',$invoice->status) }}</span>
            &nbsp;&middot;&nbsp; {{ $invoice->currency_code }} {{ number_format($invoice->total_amount, 2) }}
        </p>
    </div>
    <div class="page-actions" style="display:flex;gap:8px">
        <a href="/admin/invoices/{{ $invoice->id }}/pdf" class="btn btn-primary">Download PDF</a>
        <a href="/admin/invoices/{{ $invoice->id }}/stream" class="btn btn-ghost">Preview PDF</a>
        @if($invoice->status !== 'paid' && $invoice->status !== 'credit_note' && $invoice->status !== 'cancelled')
            <form method="POST" action="/admin/invoices/{{ $invoice->id }}/mark-paid" style="display:inline">
                @csrf
                <button class="btn btn-ghost" style="color:var(--ok)">Mark Paid</button>
            </form>
        @endif
        @if($invoice->status !== 'credit_note')
            <form method="POST" action="/admin/invoices/{{ $invoice->id }}/credit-note" style="display:inline" onsubmit="return prompt('Reason for credit note:')">
                @csrf
                <input type="hidden" name="reason" value="">
                <button class="btn btn-ghost" onclick="this.form.reason.value=prompt('Reason for credit note:')||''">Credit Note</button>
            </form>
        @endif
    </div>
</div>

@if(session('status'))
    <div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start">
    <div>
        {{-- Invoice Details --}}
        <div class="card">
            <div class="card-h"><h2>Invoice Details</h2></div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                    <div>
                        <div class="sub">Invoice Number</div>
                        <div style="font-weight:600">{{ $invoice->invoice_number }}</div>
                    </div>
                    <div>
                        <div class="sub">Status</div>
                        <div><span class="badge {{ $badgeClass }}">{{ str_replace('_',' ',$invoice->status) }}</span></div>
                    </div>
                    <div>
                        <div class="sub">Issue Date</div>
                        <div>{{ $invoice->issued_at?->format('M d, Y') ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Due Date</div>
                        <div>{{ $invoice->due_at?->format('M d, Y') ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Order</div>
                        <div>{{ $invoice->order_id ? '#' . $invoice->order_id : '—' }}</div>
                    </div>
                    <div>
                        <div class="sub">Currency</div>
                        <div>{{ $invoice->currency_code }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Items --}}
        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>Line Items</h2></div>
            <div class="scroll-x">
                <table class="tbl">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th class="num">Qty</th>
                            <th class="num">Unit Price</th>
                            <th class="num">Tax</th>
                            <th class="num">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->items as $item)
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td class="mono" style="font-size:.82rem">{{ $item->product_sku ?? '—' }}</td>
                            <td class="num">{{ $item->quantity }}</td>
                            <td class="num">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="num">{{ number_format($item->tax_amount, 2) }}</td>
                            <td class="num" style="font-weight:600">{{ number_format($item->total_amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="empty">No items</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Totals --}}
        <div class="card" style="margin-top:16px">
            <div class="card-body">
                <table style="width:300px;margin-left:auto">
                    <tr>
                        <td style="padding:6px 12px;text-align:right;color:var(--muted)">Subtotal</td>
                        <td style="padding:6px 12px;text-align:right;font-weight:600">{{ number_format($invoice->subtotal, 2) }}</td>
                    </tr>
                    @if($invoice->tax_amount > 0)
                    <tr>
                        <td style="padding:6px 12px;text-align:right;color:var(--muted)">Tax</td>
                        <td style="padding:6px 12px;text-align:right">{{ number_format($invoice->tax_amount, 2) }}</td>
                    </tr>
                    @endif
                    @if($invoice->shipping_amount > 0)
                    <tr>
                        <td style="padding:6px 12px;text-align:right;color:var(--muted)">Shipping</td>
                        <td style="padding:6px 12px;text-align:right">{{ number_format($invoice->shipping_amount, 2) }}</td>
                    </tr>
                    @endif
                    @if($invoice->discount_amount > 0)
                    <tr>
                        <td style="padding:6px 12px;text-align:right;color:var(--muted)">Discount</td>
                        <td style="padding:6px 12px;text-align:right;color:var(--ok)">-{{ number_format($invoice->discount_amount, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td style="padding:10px 12px;text-align:right;font-weight:700;border-top:2px solid var(--border)">Total</td>
                        <td style="padding:10px 12px;text-align:right;font-weight:700;font-size:16px">{{ $invoice->currency_code }} {{ number_format($invoice->total_amount, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div>
        {{-- Billing --}}
        <div class="card">
            <div class="card-h"><h2>Billing</h2></div>
            <div class="card-body">
                <div style="font-weight:600">{{ $invoice->billing_name ?? '—' }}</div>
                @if($invoice->billing_email)
                    <div style="color:var(--muted)">{{ $invoice->billing_email }}</div>
                @endif
                @if($invoice->billing_address)
                    <div style="color:var(--muted);margin-top:8px">{{ nl2br(e($invoice->billing_address)) }}</div>
                @endif
            </div>
        </div>

        {{-- Shipping --}}
        @if($invoice->shipping_name || $invoice->shipping_address)
        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>Shipping</h2></div>
            <div class="card-body">
                <div style="font-weight:600">{{ $invoice->shipping_name ?? '—' }}</div>
                @if($invoice->shipping_address)
                    <div style="color:var(--muted);margin-top:8px">{{ nl2br(e($invoice->shipping_address)) }}</div>
                @endif
            </div>
        </div>
        @endif

        {{-- QR Verification --}}
        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>QR Verification</h2></div>
            <div class="card-body">
                <div style="background:var(--bg);padding:12px;border-radius:6px;text-align:center;margin-bottom:12px">
                    <div style="font-size:12px;color:var(--muted);margin-bottom:8px">Verification URL</div>
                    <div style="font-size:11px;word-break:break-all;color:var(--fg)">{{ url('/verify/invoice/' . $invoice->invoice_number . '?token=' . $invoice->qr_token) }}</div>
                </div>
                <div style="font-size:12px;color:var(--muted)">
                    <p>Scan or visit the URL to verify this invoice's authenticity.</p>
                    <p style="margin-top:8px">Verification shows only: status, date, total, marketplace.</p>
                </div>
            </div>
        </div>

        {{-- Notes --}}
        @if($invoice->notes)
        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>Notes</h2></div>
            <div class="card-body">
                <p style="color:var(--muted)">{{ $invoice->notes }}</p>
            </div>
        </div>
        @endif

        {{-- Credit Note --}}
        @if($invoice->credit_note_id)
        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>Credit Note</h2></div>
            <div class="card-body">
                <a href="/admin/invoices/{{ $invoice->credit_note_id }}" style="font-weight:600;color:var(--fg)">{{ $invoice->creditNote?->invoice_number ?? '#' . $invoice->credit_note_id }}</a>
                @if($invoice->credit_note_reason)
                    <p style="color:var(--muted);margin-top:8px">{{ $invoice->credit_note_reason }}</p>
                @endif
            </div>
        </div>
        @endif

        {{-- PDF Status --}}
        <div class="card" style="margin-top:16px">
            <div class="card-h"><h2>PDF</h2></div>
            <div class="card-body">
                @if($invoice->pdf_path)
                    <div style="color:var(--ok);font-size:13px">PDF generated {{ $invoice->pdf_generated_at?->diffForHumans() ?? '' }}</div>
                @else
                    <div style="color:var(--muted);font-size:13px">PDF not yet generated. Click "Download PDF" to generate.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
