@extends('admin.layout')
@section('title', 'Invoices')
@section('crumb', 'Orders / Invoices')

@section('content')
<div class="page-head">
    <div>
        <h2>Invoices</h2>
        <p>Manage invoices with QR verification and PDF generation.</p>
    </div>
</div>

@if(session('status'))
    <div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>
@endif

<div class="card">
    <div class="card-h">
        <h2>Invoices ({{ $invoices->total() }})</h2>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <input class="control" name="search" value="{{ request('search') }}" placeholder="Search invoices..." style="width:220px">
            <select class="control" name="status" style="width:140px">
                <option value="">All Status</option>
                @foreach(['issued','pending','paid','cancelled','credit_note'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Issued</th>
                    <th>Due</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                <tr>
                    <td>
                        <a href="/admin/invoices/{{ $inv->id }}" style="font-weight:600;color:var(--fg)">{{ $inv->invoice_number }}</a>
                    </td>
                    <td>
                        {{ $inv->billing_name ?? '—' }}
                        @if($inv->billing_email)
                            <br><span style="color:var(--muted);font-size:.82rem">{{ $inv->billing_email }}</span>
                        @endif
                    </td>
                    <td class="num" style="font-weight:600">{{ $inv->currency_code }} {{ number_format($inv->total_amount, 2) }}</td>
                    <td>
                        @php
                            $badgeClass = match($inv->status) {
                                'paid' => 'b-ok',
                                'cancelled' => 'b-danger',
                                'credit_note' => 'b-warn',
                                default => 'b-muted',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ str_replace('_',' ',$inv->status) }}</span>
                    </td>
                    <td>{{ $inv->issued_at?->format('M d, Y') ?? '—' }}</td>
                    <td>{{ $inv->due_at?->format('M d, Y') ?? '—' }}</td>
                    <td style="white-space:nowrap">
                        <a href="/admin/invoices/{{ $inv->id }}" class="btn btn-ghost btn-sm">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="empty">
                        <p>No invoices yet.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $invoices->withQueryString()->links() }}
</div>
@endsection
