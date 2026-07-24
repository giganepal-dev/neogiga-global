@extends('admin.layout')
@section('title', 'Batch Inventory')
@section('crumb', 'Inventory / Batches')

@section('content')
<div class="page-head">
    <div>
        <h2>Batch Inventory</h2>
        <p>Track inventory batches for expiry, warranty, and quality control.</p>
    </div>
    <div class="page-actions">
        <a href="/admin/inventory/serials" class="btn btn-ghost">Serial Numbers</a>
    </div>
</div>

@if(session('status'))
    <div class="note" style="background:#dcfce7;border-color:#86efac;color:#166534">{{ session('status') }}</div>
@endif

<div class="kpis">
    <div class="kpi">
        <div class="t">Total Batches</div>
        <div class="v">{{ number_format($stats['total']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Active</div>
        <div class="v" style="color:var(--ok)">{{ number_format($stats['active']) }}</div>
    </div>
    <div class="kpi">
        <div class="t">Expiring Soon</div>
        <div class="v" style="color:var(--warn)">{{ number_format($stats['expiring_soon']) }}</div>
        <div class="s">within 30 days</div>
    </div>
    <div class="kpi">
        <div class="t">Expired</div>
        <div class="v" style="color:var(--danger)">{{ number_format($stats['expired']) }}</div>
    </div>
</div>

<div class="card" style="margin-top:20px">
    <div class="card-h">
        <h2>Batches ({{ $batches->total() }})</h2>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <input class="control" name="search" value="{{ request('search') }}" placeholder="Search batches..." style="width:200px">
            <select class="control" name="status" style="width:120px">
                <option value="">All Status</option>
                @foreach(['active','quarantined','expired','recalled','depleted'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button class="btn btn-ghost" type="submit">Filter</button>
        </form>
    </div>
    <div class="scroll-x">
        <table class="tbl">
            <thead>
                <tr>
                    <th>Batch #</th>
                    <th>Product</th>
                    <th>Warehouse</th>
                    <th class="num">Qty</th>
                    <th class="num">Available</th>
                    <th>Status</th>
                    <th>Expiry</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $b)
                @php
                    $available = ($b->current_quantity ?? 0) - ($b->reserved_quantity ?? 0) - ($b->damaged_quantity ?? 0);
                    $expiringSoon = $b->expiry_date && \Carbon\Carbon::parse($b->expiry_date)->diffInDays(now()) <= 30 && \Carbon\Carbon::parse($b->expiry_date)->isFuture();
                    $expired = $b->expiry_date && \Carbon\Carbon::parse($b->expiry_date)->isPast();
                @endphp
                <tr>
                    <td class="mono" style="font-size:.82rem">{{ $b->batch_number ?? '—' }}</td>
                    <td>
                        <span style="font-weight:600">{{ $b->product_name ?? '—' }}</span>
                        @if($b->mpn)
                            <br><span style="color:var(--muted);font-size:.78rem">{{ $b->mpn }}</span>
                        @endif
                    </td>
                    <td>{{ $b->warehouse_name ?? '—' }}</td>
                    <td class="num">{{ $b->current_quantity ?? 0 }}</td>
                    <td class="num" style="color:{{ $available > 0 ? 'var(--ok)' : 'var(--danger)' }}">{{ $available }}</td>
                    <td>
                        @php
                            $badge = match($b->status) {
                                'active' => 'b-ok',
                                'quarantined' => 'b-warn',
                                'expired' => 'b-danger',
                                'recalled' => 'b-danger',
                                default => 'b-muted',
                            };
                        @endphp
                        <span class="badge {{ $badge }}">{{ ucfirst($b->status ?? '—') }}</span>
                    </td>
                    <td style="color:{{ $expired ? 'var(--danger)' : ($expiringSoon ? 'var(--warn)' : 'var(--muted)') }}">
                        {{ $b->expiry_date ? \Carbon\Carbon::parse($b->expiry_date)->format('M d, Y') : '—' }}
                    </td>
                    <td>
                        <a href="/admin/inventory/batches/{{ $b->id }}" class="btn btn-ghost btn-sm">View</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="empty">
                        <p>No batches found.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $batches->withQueryString()->links() }}
</div>
@endsection
